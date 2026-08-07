<?php

namespace App\Jobs;

use App\Models\GenerationJob;
use App\Services\FalService;
use App\Services\GenerationResultProcessor;
use App\Services\ReferenceResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180;

    public function __construct(public int $generationJobId) {}

    public function handle(
        FalService $falService,
        ReferenceResolver $referenceResolver,
        GenerationResultProcessor $processor
    ): void {
        $job = GenerationJob::with(['project', 'page', 'batch'])->find($this->generationJobId);
        if (!$job || $job->status === 'completed' || $job->status === 'cancelled') {
            return;
        }

        try {
            Log::info("Executing ProcessGenerationJob for Job {$job->uuid}");
            $job->page->update(['status' => 'generating']);
            $job->update(['status' => 'generating']);

            $maskPath = $job->input_metadata['mask_path'] ?? null;
            $references = $referenceResolver->resolve($job->project, $job->page, $job->mode, $maskPath);

            $result = $falService->submitJob($job, $references);

            // If sync_mode was true or result completed immediately
            if (isset($result['status']) && $result['status'] === 'COMPLETED') {
                $processor->process($job, $result);
            }
        } catch (\Throwable $e) {
            Log::error("ProcessGenerationJob exception for Job {$job->uuid}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $processor->handleFailure($job, $e->getMessage());
        }
    }
}
