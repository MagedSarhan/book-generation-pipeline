<?php

namespace App\Http\Controllers;

use App\Models\GenerationJob;
use App\Services\GenerationResultProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FalWebhookController extends Controller
{
    public function __construct(
        protected GenerationResultProcessor $processor
    ) {}

    public function handle(Request $request, string $job_uuid)
    {
        Log::info("Fal Webhook received for job_uuid {$job_uuid}", ['body' => $request->all()]);

        $job = GenerationJob::where('uuid', $job_uuid)->first();
        if (!$job) {
            $requestId = $request->input('request_id');
            if ($requestId) {
                $job = GenerationJob::where('fal_request_id', $requestId)->first();
            }
        }

        if (!$job) {
            Log::warning("Fal Webhook: Job not found for UUID {$job_uuid}");
            return response()->json(['status' => 'ignored', 'reason' => 'job_not_found'], 404);
        }

        if ($job->status === 'completed') {
            return response()->json(['status' => 'ignored', 'reason' => 'already_completed']);
        }

        $payload = $request->all();
        $status = strtoupper($payload['status'] ?? 'OK');

        if ($status === 'OK' || $status === 'COMPLETED' || isset($payload['images']) || isset($payload['payload']['images'])) {
            $this->processor->process($job, $payload);
        } else {
            $errorMsg = $payload['error'] ?? $payload['payload']['error'] ?? 'Fal generation error.';
            $this->processor->handleFailure($job, (string)$errorMsg);
        }

        return response()->json(['status' => 'success']);
    }
}
