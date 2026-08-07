<?php

namespace App\Jobs;

use App\Models\GenerationJob;
use App\Services\FalService;
use App\Services\GenerationResultProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ReconcileFalJobsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(FalService $falService, GenerationResultProcessor $processor): void
    {
        // Find jobs stuck in submitted/generating status for more than 30 seconds
        $jobs = GenerationJob::whereIn('status', ['submitted', 'generating'])
            ->whereNotNull('fal_request_id')
            ->where('updated_at', '<', now()->subSeconds(30))
            ->get();

        foreach ($jobs as $job) {
            try {
                Log::info("Reconciling Fal status for Job {$job->uuid}");
                $statusRes = $falService->checkStatus($job);

                if ($statusRes['status'] === 'COMPLETED') {
                    $processor->process($job, $statusRes['payload']);
                } elseif (in_array($statusRes['status'], ['ERROR', 'FAILED', 'CANCELLED'])) {
                    $errorMsg = $statusRes['payload']['error'] ?? 'Fal generation failed remotely.';
                    $processor->handleFailure($job, $errorMsg);
                }
            } catch (\Throwable $e) {
                Log::error("Reconciliation error for Job {$job->uuid}: " . $e->getMessage());
            }
        }
    }
}
