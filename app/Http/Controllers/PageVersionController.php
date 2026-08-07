<?php

namespace App\Http\Controllers;

use App\Models\PageVersion;
use App\Models\ProjectPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PageVersionController extends Controller
{
    public function selectVersion(PageVersion $version)
    {
        $this->authorize('update', $version->page->project);

        PageVersion::where('project_page_id', $version->project_page_id)->update(['is_selected' => false]);
        $version->update(['is_selected' => true]);

        $version->page->update(['selected_version_id' => $version->id]);

        return response()->json(['success' => true, 'version_id' => $version->id]);
    }

    public function downloadVersion(PageVersion $version)
    {
        $this->authorize('view', $version->page->project);

        if (!Storage::disk('local')->exists($version->image_path)) {
            abort(404, 'File not found');
        }

        $fullPath = Storage::disk('local')->path($version->image_path);
        $filename = sprintf("page_%03d_v%d.%s", $version->page->page_number, $version->version_number, pathinfo($fullPath, PATHINFO_EXTENSION));

        return response()->download($fullPath, $filename);
    }

    public function destroy(PageVersion $version)
    {
        $this->authorize('update', $version->page->project);

        $page = $version->page;
        if ($page->selected_version_id == $version->id) {
            $page->update(['selected_version_id' => null]);
        }

        if (Storage::disk('local')->exists($version->image_path)) {
            Storage::disk('local')->delete($version->image_path);
        }

        $version->delete();

        // Select latest version if available
        $latest = $page->versions()->first();
        if ($latest) {
            $latest->update(['is_selected' => true]);
            $page->update(['selected_version_id' => $latest->id]);
        }

        return response()->json(['success' => true]);
    }
}
