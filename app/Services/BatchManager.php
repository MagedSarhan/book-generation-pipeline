<?php

namespace App\Services;

use App\Models\GenerationBatch;
use App\Models\GenerationJob;
use App\Models\Project;
use App\Models\ProjectPage;
use App\Jobs\ProcessGenerationJob;
use Illuminate\Support\Facades\Log;

class BatchManager
{
    protected int $maxActiveRequests;

    public function __construct(
        protected PromptComposer $promptComposer,
        protected ReferenceResolver $referenceResolver,
        protected FalService $falService
    ) {
        $this->maxActiveRequests = (int) config('services.fal.max_active_requests', env('FAL_MAX_ACTIVE_REQUESTS', 3));
    }

    /**
     * Create generation batch for a set of pages.
     */
    public function createBatch(
        Project $project,
        array $pages,
        ?string $instruction = null,
        array $overrideParams = []
    ): GenerationBatch {
        $batch = GenerationBatch::create([
            'project_id' => $project->id,
            'title' => 'Batch ' . count($pages) . ' pages',
            'total_jobs' => count($pages),
            'completed_jobs' => 0,
            'failed_jobs' => 0,
            'pending_jobs' => count($pages),
            'status' => 'active',
            'instruction' => $instruction,
        ]);

        foreach ($pages as $page) {
            $quality = $overrideParams['quality'] ?? $project->default_quality;
            $resolution = $overrideParams['resolution'] ?? $project->default_resolution;
            $outputFormat = $overrideParams['output_format'] ?? $project->default_output_format;
            $numImages = $overrideParams['num_images'] ?? $project->default_variants;
            $mode = $overrideParams['mode'] ?? 'redesign';

            // Resolution dimensions mapping
            list($width, $height) = $this->resolveResolutionDimensions($resolution, $overrideParams, $project);
            $provider = ($resolution !== 'auto' || ($width && $height)) ? 'direct' : 'workflow';

            $compiledPrompt = $this->promptComposer->compose(
                $project,
                $page,
                $overrideParams['user_instruction'] ?? null,
                $instruction,
                $mode
            );

            $job = GenerationJob::create([
                'project_id' => $project->id,
                'project_page_id' => $page->id,
                'generation_batch_id' => $batch->id,
                'provider' => $provider,
                'mode' => $mode,
                'quality' => $quality,
                'width' => $width,
                'height' => $height,
                'output_format' => $outputFormat,
                'num_images' => $numImages,
                'compiled_prompt' => $compiledPrompt,
                'input_metadata' => $overrideParams,
                'status' => 'pending',
                'queued_at' => now(),
            ]);

            $page->update(['status' => 'queued']);
        }

        $this->dispatchNextPendingInProject($project);

        return $batch;
    }

    /**
     * Resolve dimensions based on resolution selection.
     */
    public function resolveResolutionDimensions(string $resolution, array $overrideParams, Project $project): array
    {
        return match ($resolution) {
            'a4_draft' => [1024, 1440],
            'a4_high' => [1664, 2352],
            'a4_max' => [2400, 3392],
            'custom' => [
                (int) ($overrideParams['custom_width'] ?? $project->custom_width ?? 1024),
                (int) ($overrideParams['custom_height'] ?? $project->custom_height ?? 1440),
            ],
            default => [null, null], // auto size
        };
    }

    /**
     * Dispatch next pending jobs while respecting active concurrency limit.
     */
    public function dispatchNextPendingInProject(Project $project): void
    {
        $activeCount = GenerationJob::where('project_id', $project->id)
            ->whereIn('status', ['submitted', 'generating'])
            ->count();

        $capacity = $this->maxActiveRequests - $activeCount;

        if ($capacity <= 0) {
            Log::info("Project {$project->uuid} active generation capacity reached ({$activeCount}/{$this->maxActiveRequests}).");
            return;
        }

        $pendingJobs = GenerationJob::where('project_id', $project->id)
            ->where('status', 'pending')
            ->whereHas('batch', fn($q) => $q->where('status', 'active'))
            ->orderBy('id', 'asc')
            ->take($capacity)
            ->get();

        foreach ($pendingJobs as $job) {
            ProcessGenerationJob::dispatch($job->id);
        }
    }

    public function pauseBatch(GenerationBatch $batch): void
    {
        $batch->update(['status' => 'paused']);
    }

    public function resumeBatch(GenerationBatch $batch): void
    {
        $batch->update(['status' => 'active']);
        $this->dispatchNextPendingInProject($batch->project);
    }

    public function retryJob(GenerationJob $job): void
    {
        $job->update([
            'status' => 'pending',
            'attempts' => $job->attempts + 1,
            'error_message' => null,
        ]);
        $job->page->update(['status' => 'queued']);
        $this->dispatchNextPendingInProject($job->project);
    }

    public function retryFailedBatch(GenerationBatch $batch): void
    {
        $failedJobs = $batch->jobs()->where('status', 'failed')->get();
        foreach ($failedJobs as $job) {
            $this->retryJob($job);
        }
        $batch->update(['status' => 'active']);
    }
}
