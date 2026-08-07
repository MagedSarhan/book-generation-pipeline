<?php

namespace App\Services;

use App\Models\GenerationJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FalService
{
    protected string $falKey;
    protected string $workflowId;
    protected string $directModelId;

    public function __construct()
    {
        $this->falKey = config('services.fal.key', env('FAL_KEY', ''));
        $this->workflowId = config('services.fal.workflow_id', 'workflows/magedsarhan773/book-generation-pipeline');
        $this->directModelId = config('services.fal.direct_model', 'openai/gpt-image-2/edit');
    }

    /**
     * Submit a generation job to Fal Queue API.
     */
    public function submitJob(GenerationJob $job, array $references): array
    {
        $useDirect = ($job->provider === 'direct') || ($job->width && $job->height);
        $endpoint = $useDirect ? $this->directModelId : $this->workflowId;
        $url = "https://queue.fal.run/{$endpoint}";

        $webhookUrl = route('fal.webhook', ['job_uuid' => $job->uuid]);

        if ($useDirect) {
            $payload = [
                'prompt' => $job->compiled_prompt,
                'image_urls' => array_values($references['image_urls']),
                'quality' => strtolower($job->quality),
                'num_images' => (int) $job->num_images,
                'output_format' => strtolower($job->output_format),
                'sync_mode' => false,
            ];

            if ($job->width && $job->height) {
                $payload['image_size'] = [
                    'width' => (int) $job->width,
                    'height' => (int) $job->height,
                ];
            }

            if (!empty($references['mask_url'])) {
                $payload['mask_url'] = $references['mask_url'];
            }
        } else {
            $payload = [
                'input' => [
                    'prompt' => $job->compiled_prompt,
                    'source_page' => $references['source_page'] ?? '',
                    'style_reference' => $references['style_reference'] ?? '',
                    'previous_page' => $references['previous_page'] ?? '',
                    'extra_reference_1' => $references['extra_reference_1'] ?? '',
                    'extra_reference_2' => $references['extra_reference_2'] ?? '',
                    'quality' => strtolower($job->quality),
                    'image_width' => $job->width ?: 1024,
                    'image_height' => $job->height ?: 1440,
                    'num_images' => (int) $job->num_images,
                    'output_format' => strtolower($job->output_format),
                    'sync_mode' => false,
                    'mask_image' => $references['mask_url'] ?? '',
                    'project_id' => (string) $job->project_id,
                    'page_number' => (int) $job->page->page_number,
                    'continuation_mode' => true,
                ]
            ];
        }

        Log::info("Submitting Fal Job {$job->uuid} to {$endpoint}", ['payload' => $payload]);

        $response = Http::withHeaders([
            'Authorization' => "Key {$this->falKey}",
            'Content-Type' => 'application/json',
            'x-fal-webhook-url' => $webhookUrl,
        ])->post($url, $payload);

        if (!$response->successful()) {
            $errorText = $response->body();
            Log::error("Fal API submission failed for Job {$job->uuid}: {$errorText}");
            throw new \RuntimeException("Fal API Error ({$response->status()}): {$errorText}");
        }

        $resData = $response->json();
        $requestId = $resData['request_id'] ?? null;

        $job->update([
            'fal_request_id' => $requestId,
            'status' => 'submitted',
            'submitted_at' => now(),
            'raw_provider_payload' => $payload,
            'raw_provider_response' => $resData,
        ]);

        return $resData;
    }

    /**
     * Poll status of a request from Fal Queue.
     */
    public function checkStatus(GenerationJob $job): array
    {
        if (!$job->fal_request_id) {
            throw new \InvalidArgumentException("Job {$job->uuid} missing fal_request_id");
        }

        $useDirect = ($job->provider === 'direct') || ($job->width && $job->height);
        $endpoint = $useDirect ? $this->directModelId : $this->workflowId;
        $statusUrl = "https://queue.fal.run/{$endpoint}/requests/{$job->fal_request_id}/status";

        $response = Http::withHeaders([
            'Authorization' => "Key {$this->falKey}",
        ])->get($statusUrl);

        if (!$response->successful()) {
            throw new \RuntimeException("Fal Status check failed ({$response->status()}): " . $response->body());
        }

        $statusData = $response->json();
        $status = $statusData['status'] ?? 'IN_QUEUE';

        if ($status === 'COMPLETED') {
            $resultUrl = "https://queue.fal.run/{$endpoint}/requests/{$job->fal_request_id}";
            $resultRes = Http::withHeaders([
                'Authorization' => "Key {$this->falKey}",
            ])->get($resultUrl);

            if ($resultRes->successful()) {
                return [
                    'status' => 'COMPLETED',
                    'payload' => $resultRes->json(),
                ];
            }
        }

        return [
            'status' => $status,
            'payload' => $statusData,
        ];
    }
}
