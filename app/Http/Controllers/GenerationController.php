<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\GenerationBatch;
use App\Models\GenerationJob;
use App\Models\Message;
use App\Models\Project;
use App\Models\ProjectPage;
use App\Services\BatchManager;
use Illuminate\Http\Request;

class GenerationController extends Controller
{
    public function __construct(
        protected BatchManager $batchManager
    ) {}

    public function generate(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'prompt' => 'nullable|string',
            'action_type' => 'required|in:current,next_10,next_20,next_50,all_remaining,custom',
            'current_page_id' => 'nullable|exists:project_pages,id',
            'quality' => 'required|in:low,medium,high',
            'resolution' => 'required|string',
            'custom_width' => 'nullable|integer|min:256|max:3840',
            'custom_height' => 'nullable|integer|min:256|max:3840',
            'output_format' => 'required|in:png,jpeg,webp',
            'num_images' => 'required|integer|min:1|max:4',
            'mode' => 'nullable|in:redesign,edit',
        ]);

        $promptText = trim($request->input('prompt', ''));
        $action = $validated['action_type'];

        // Deterministic Arabic / English command parsing
        if (!empty($promptText)) {
            $norm = mb_strtolower($promptText, 'UTF-8');
            if (in_array($norm, ['كمل 10', 'اكمل 10', '10 التالية', 'next 10'])) {
                $action = 'next_10';
            } elseif (in_array($norm, ['كمل 20', 'اكمل 20', '20 التالية', 'next 20'])) {
                $action = 'next_20';
            } elseif (in_array($norm, ['كمل 50', 'اكمل 50', '50 التالية', 'next 50'])) {
                $action = 'next_50';
            } elseif (in_array($norm, ['كمل', 'اكمل', 'التالي', 'صمم الباقي', 'continue', 'all remaining', 'generate all'])) {
                $action = 'all_remaining';
            }
        }

        // Determine target pages
        $pages = collect();
        if ($action === 'current' && $validated['current_page_id']) {
            $pages = ProjectPage::where('id', $validated['current_page_id'])->get();
        } else {
            // Find pages not yet completed, or starting after current
            $startPageNumber = 1;
            if ($validated['current_page_id']) {
                $curr = ProjectPage::find($validated['current_page_id']);
                if ($curr) $startPageNumber = $curr->page_number;
            }

            $query = $project->pages()->where('page_number', '>=', $startPageNumber)
                ->where('status', '!=', 'completed')
                ->orderBy('page_number');

            $pages = match ($action) {
                'next_10' => $query->take(10)->get(),
                'next_20' => $query->take(20)->get(),
                'next_50' => $query->take(50)->get(),
                'all_remaining' => $query->get(),
                default => $query->take($project->default_batch_size)->get(),
            };
        }

        if ($pages->isEmpty()) {
            // Fallback: take next pages even if completed if explicitly requested
            $pages = $project->pages()->take(1)->get();
        }

        // Record message in conversation
        $conversation = $project->conversations()->firstOrCreate(['title' => 'المحادثة الرئيسية']);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'user',
            'body' => $promptText ?: "بدء توليد {$pages->count()} صفحة (الجودة: {$validated['quality']})",
            'generation_parameters' => [
                'quality' => $validated['quality'],
                'resolution' => $validated['resolution'],
                'output_format' => $validated['output_format'],
                'num_images' => $validated['num_images'],
                'page_count' => $pages->count(),
            ],
        ]);

        $batch = $this->batchManager->createBatch($project, $pages->all(), $promptText, [
            'quality' => $validated['quality'],
            'resolution' => $validated['resolution'],
            'custom_width' => $validated['custom_width'] ?? null,
            'custom_height' => $validated['custom_height'] ?? null,
            'output_format' => $validated['output_format'],
            'num_images' => $validated['num_images'],
            'user_instruction' => $promptText,
            'mode' => $validated['mode'] ?? 'redesign',
        ]);

        $message->update(['batch_id' => $batch->id]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء التوليد بنجاح.',
                'batch_id' => $batch->id,
                'job_count' => $pages->count(),
            ]);
        }

        return back()->with('success', "تم إرسال {$pages->count()} صفحة للتوليد.");
    }

    public function regeneratePage(Request $request, ProjectPage $page)
    {
        $this->authorize('update', $page->project);

        $batch = $this->batchManager->createBatch($page->project, [$page], $request->input('instruction'), [
            'quality' => $request->input('quality', $page->project->default_quality),
            'resolution' => $request->input('resolution', $page->project->default_resolution),
            'output_format' => $request->input('output_format', $page->project->default_output_format),
            'num_images' => $request->input('num_images', 1),
            'mode' => 'redesign',
            'user_instruction' => $request->input('instruction'),
        ]);

        return response()->json(['success' => true, 'batch_id' => $batch->id]);
    }

    public function editPage(Request $request, ProjectPage $page)
    {
        $this->authorize('update', $page->project);

        $instruction = $request->validate([
            'instruction' => 'required|string',
            'quality' => 'required|in:low,medium,high',
            'resolution' => 'required|string',
            'output_format' => 'required|in:png,jpeg,webp',
        ]);

        $batch = $this->batchManager->createBatch($page->project, [$page], $instruction['instruction'], [
            'quality' => $instruction['quality'],
            'resolution' => $instruction['resolution'],
            'output_format' => $instruction['output_format'],
            'num_images' => 1,
            'mode' => 'edit', // MODE B
            'user_instruction' => $instruction['instruction'],
        ]);

        return response()->json(['success' => true, 'batch_id' => $batch->id]);
    }

    public function pauseBatch(GenerationBatch $batch)
    {
        $this->authorize('update', $batch->project);
        $this->batchManager->pauseBatch($batch);
        return response()->json(['success' => true, 'status' => 'paused']);
    }

    public function resumeBatch(GenerationBatch $batch)
    {
        $this->authorize('update', $batch->project);
        $this->batchManager->resumeBatch($batch);
        return response()->json(['success' => true, 'status' => 'active']);
    }

    public function retryJob(GenerationJob $job)
    {
        $this->authorize('update', $job->project);
        $this->batchManager->retryJob($job);
        return response()->json(['success' => true]);
    }

    public function projectStatus(Project $project)
    {
        $this->authorize('view', $project);

        $pages = $project->pages()->with('selectedVersion')->get()->map(function ($p) {
            return [
                'id' => $p->id,
                'page_number' => $p->page_number,
                'status' => $p->status,
                'thumbnail_url' => $p->thumbnail_path ? route('asset.direct', ['path' => $p->thumbnail_path]) : null,
                'source_url' => route('asset.direct', ['path' => $p->source_image_path]),
                'selected_version' => $p->selectedVersion ? [
                    'id' => $p->selectedVersion->id,
                    'version_number' => $p->selectedVersion->version_number,
                    'image_url' => route('asset.direct', ['path' => $p->selectedVersion->image_path]),
                ] : null,
            ];
        });

        $total = $pages->count();
        $completed = $pages->where('status', 'completed')->count();
        $generating = $pages->whereIn('status', ['submitted', 'generating', 'queued'])->count();
        $failed = $pages->where('status', 'failed')->count();

        $activeBatch = $project->batches()->latest()->first();

        return response()->json([
            'total' => $total,
            'completed' => $completed,
            'generating' => $generating,
            'failed' => $failed,
            'progress' => $total > 0 ? round(($completed / $total) * 100) : 0,
            'pages' => $pages,
            'active_batch' => $activeBatch ? [
                'id' => $activeBatch->id,
                'status' => $activeBatch->status,
                'total' => $activeBatch->total_jobs,
                'completed' => $activeBatch->completed_jobs,
                'failed' => $activeBatch->failed_jobs,
            ] : null,
        ]);
    }
}
