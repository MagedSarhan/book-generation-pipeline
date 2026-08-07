<?php

namespace App\Services;

use App\Models\GenerationJob;
use App\Models\PageVersion;
use App\Models\ProjectPage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerationResultProcessor
{
    /**
     * Process completed payload from Fal API.
     */
    public function process(GenerationJob $job, array $payload): void
    {
        if ($job->status === 'completed') {
            Log::info("Job {$job->uuid} already marked completed, skipping redundant processing.");
            return;
        }

        Log::info("Processing Fal result for Job {$job->uuid}", ['payload' => $payload]);

        // Extract images from Fal response format
        // Could be $payload['images'] or $payload['payload']['images'] or $payload['output']['images']
        $images = $payload['images']
            ?? $payload['output']['images']
            ?? $payload['payload']['images']
            ?? [];

        if (empty($images) && isset($payload['image']['url'])) {
            $images = [$payload['image']];
        }

        if (empty($images)) {
            Log::error("No images found in Fal response for Job {$job->uuid}");
            $job->update([
                'status' => 'failed',
                'error_message' => 'No image output returned by Fal provider',
            ]);
            $this->updateBatchCounters($job, false);
            return;
        }

        $page = $job->page;
        $project = $job->project;
        $projectDir = "projects/{$project->uuid}/generated";
        Storage::disk('local')->makeDirectory($projectDir);

        // Determine next version number
        $currentMaxVersion = PageVersion::where('project_page_id', $page->id)->max('version_number') ?? 0;

        $createdVersions = [];
        foreach ($images as $idx => $img) {
            $remoteUrl = $img['url'] ?? null;
            if (!$remoteUrl) continue;

            $versionNumber = $currentMaxVersion + $idx + 1;
            $ext = strtolower($job->output_format) ?: 'png';
            $localFilename = "page_{$page->page_number}_v{$versionNumber}_" . time() . ".{$ext}";
            $localRelativePath = "{$projectDir}/{$localFilename}";

            // Download image to local VPS storage
            $imageContent = Http::timeout(60)->get($remoteUrl)->body();
            Storage::disk('local')->put($localRelativePath, $imageContent);

            $width = $img['width'] ?? $job->width;
            $height = $img['height'] ?? $job->height;
            $contentType = $img['content_type'] ?? "image/{$ext}";

            // Unselect older versions if selecting new
            PageVersion::where('project_page_id', $page->id)->update(['is_selected' => false]);

            $version = PageVersion::create([
                'project_page_id' => $page->id,
                'generation_job_id' => $job->id,
                'version_number' => $versionNumber,
                'image_path' => $localRelativePath,
                'provider_url' => $remoteUrl,
                'width' => $width,
                'height' => $height,
                'content_type' => $contentType,
                'is_selected' => true,
                'user_instruction' => $job->compiled_prompt,
            ]);

            $createdVersions[] = $version;
        }

        $lastVersion = end($createdVersions);
        if ($lastVersion) {
            $page->update([
                'status' => 'completed',
                'selected_version_id' => $lastVersion->id,
            ]);
        }

        $job->update([
            'status' => 'completed',
            'completed_at' => now(),
            'raw_provider_response' => $payload,
        ]);

        $this->updateBatchCounters($job, true);

        // Dispatch next pending job in batch/project to maintain concurrency capacity
        app(BatchManager::class)->dispatchNextPendingInProject($project);
    }

    public function handleFailure(GenerationJob $job, string $errorMessage): void
    {
        Log::error("Handling failure for Job {$job->uuid}: {$errorMessage}");
        $job->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);

        $job->page->update(['status' => 'failed']);
        $this->updateBatchCounters($job, false);

        // Dispatch next pending job
        app(BatchManager::class)->dispatchNextPendingInProject($job->project);
    }

    protected function updateBatchCounters(GenerationJob $job, bool $isSuccess): void
    {
        if ($batch = $job->batch) {
            if ($isSuccess) {
                $batch->increment('completed_jobs');
            } else {
                $batch->increment('failed_jobs');
            }
            if ($batch->pending_jobs > 0) {
                $batch->decrement('pending_jobs');
            }

            if (($batch->completed_jobs + $batch->failed_jobs) >= $batch->total_jobs) {
                $batch->update(['status' => 'completed']);
            }
        }
    }
}
