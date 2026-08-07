<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectPage;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    public function __construct(
        protected ExportService $exportService
    ) {}

    public function downloadPage(ProjectPage $page)
    {
        $this->authorize('view', $page->project);

        $version = $page->selectedVersion;
        if ($version && Storage::disk('local')->exists($version->image_path)) {
            $path = Storage::disk('local')->path($version->image_path);
            return response()->download($path, "page_{$page->page_number}_v{$version->version_number}." . pathinfo($path, PATHINFO_EXTENSION));
        }

        if (Storage::disk('local')->exists($page->source_image_path)) {
            $path = Storage::disk('local')->path($page->source_image_path);
            return response()->download($path, "page_{$page->page_number}_source." . pathinfo($path, PATHINFO_EXTENSION));
        }

        abort(404, 'Page image not found.');
    }

    public function zipExport(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $pageIds = $request->input('page_ids');
        if (is_string($pageIds)) {
            $pageIds = explode(',', $pageIds);
        }

        $zipRelativePath = $this->exportService->createZipExport($project, $pageIds);
        $fullPath = Storage::disk('local')->path($zipRelativePath);

        return response()->download($fullPath, "project_{$project->id}_pages.zip")->deleteFileAfterSend(true);
    }
}
