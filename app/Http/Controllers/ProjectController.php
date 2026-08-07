<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectReference;
use App\Services\DocumentImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    public function __construct(
        protected DocumentImporter $documentImporter
    ) {}

    public function create()
    {
        return view('projects.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'master_prompt' => 'nullable|string',
            'default_batch_size' => 'required|integer|min:1|max:100',
            'default_quality' => 'required|in:low,medium,high',
            'default_resolution' => 'required|string',
            'custom_width' => 'nullable|integer|min:256|max:3840',
            'custom_height' => 'nullable|integer|min:256|max:3840',
            'default_output_format' => 'required|in:png,jpeg,webp',
            'default_variants' => 'required|integer|min:1|max:4',
            'source_type' => 'required|in:pdf,docx,images',
            'source_file' => 'required_if:source_type,pdf,docx|file|max:51200', // 50MB
            'source_images.*' => 'required_if:source_type,images|image|max:20480',
            'master_style' => 'required|image|max:20480',
            'extra_references.*' => 'nullable|image|max:20480',
        ]);

        $project = Project::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'master_prompt' => $validated['master_prompt'] ?? null,
            'default_batch_size' => $validated['default_batch_size'],
            'default_quality' => $validated['default_quality'],
            'default_resolution' => $validated['default_resolution'],
            'custom_width' => $validated['custom_width'] ?? null,
            'custom_height' => $validated['custom_height'] ?? null,
            'default_output_format' => $validated['default_output_format'],
            'default_variants' => $validated['default_variants'],
            'source_type' => $validated['source_type'],
        ]);

        // Upload Master Style Reference
        $projectDir = "projects/{$project->uuid}/references";
        Storage::disk('local')->makeDirectory($projectDir);

        if ($request->hasFile('master_style')) {
            $path = $request->file('master_style')->store($projectDir, 'local');
            ProjectReference::create([
                'project_id' => $project->id,
                'title' => 'الهوية البصرية الرئيسية',
                'role' => 'master_style',
                'image_path' => $path,
                'is_active' => true,
                'sort_order' => 1,
            ]);
        }

        // Upload Extra References
        if ($request->hasFile('extra_references')) {
            foreach ($request->file('extra_references') as $idx => $extraFile) {
                $path = $extraFile->store($projectDir, 'local');
                ProjectReference::create([
                    'project_id' => $project->id,
                    'title' => 'مرجع إضافي ' . ($idx + 1),
                    'role' => 'extra_reference',
                    'image_path' => $path,
                    'is_active' => true,
                    'sort_order' => $idx + 2,
                ]);
            }
        }

        // Create main conversation
        $project->conversations()->create(['title' => 'المحادثة الرئيسية']);

        // Process Source Document/Images Import
        $sourceInput = match ($validated['source_type']) {
            'pdf', 'docx' => $request->file('source_file'),
            'images' => $request->file('source_images'),
        };

        $this->documentImporter->import($project, $sourceInput, $validated['source_type']);

        return redirect()->route('projects.show', $project->uuid)->with('success', 'تم إنشاء المشروع واستيراد الصفحات بنجاح.');
    }

    public function show(Project $project)
    {
        $this->authorize('view', $project);

        $project->load([
            'pages' => fn($q) => $q->with(['selectedVersion', 'versions'])->orderBy('page_number'),
            'references',
            'conversations.messages',
            'batches' => fn($q) => $q->latest()->take(5),
        ]);

        $conversation = $project->conversations()->firstOrCreate(['title' => 'المحادثة الرئيسية']);

        return view('projects.show', compact('project', 'conversation'));
    }

    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'master_prompt' => 'nullable|string',
            'default_batch_size' => 'required|integer|min:1|max:100',
            'default_quality' => 'required|in:low,medium,high',
            'default_resolution' => 'required|string',
            'default_output_format' => 'required|in:png,jpeg,webp',
            'default_variants' => 'required|integer|min:1|max:4',
            'continuation_mode' => 'nullable|boolean',
        ]);

        $project->update([
            'name' => $validated['name'],
            'master_prompt' => $validated['master_prompt'] ?? null,
            'default_batch_size' => $validated['default_batch_size'],
            'default_quality' => $validated['default_quality'],
            'default_resolution' => $validated['default_resolution'],
            'default_output_format' => $validated['default_output_format'],
            'default_variants' => $validated['default_variants'],
            'continuation_mode' => $request->boolean('continuation_mode', true),
        ]);

        return back()->with('success', 'تم تحديث إعدادات المشروع.');
    }
}
