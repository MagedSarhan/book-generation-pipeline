<?php

namespace App\Services;

use App\Models\Project;
use App\Models\PageVersion;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ExportService
{
    /**
     * Create ZIP archive containing selected final versions of all or filtered pages in project order.
     */
    public function createZipExport(Project $project, ?array $pageIds = null): string
    {
        $query = $project->pages()->with('selectedVersion');
        if ($pageIds) {
            $query->whereIn('id', $pageIds);
        }
        $pages = $query->get();

        $zipFilename = "export_project_{$project->id}_" . time() . ".zip";
        $exportDir = "projects/{$project->uuid}/exports";
        Storage::disk('local')->makeDirectory($exportDir);
        $zipPath = Storage::disk('local')->path("{$exportDir}/{$zipFilename}");

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Could not create ZIP archive at {$zipPath}");
        }

        foreach ($pages as $page) {
            if ($page->selectedVersion && Storage::disk('local')->exists($page->selectedVersion->image_path)) {
                $fileAbsPath = Storage::disk('local')->path($page->selectedVersion->image_path);
                $ext = pathinfo($fileAbsPath, PATHINFO_EXTENSION) ?: 'png';
                $entryName = sprintf("page_%03d_v%d.%s", $page->page_number, $page->selectedVersion->version_number, $ext);
                $zip->addFile($fileAbsPath, $entryName);
            } elseif (Storage::disk('local')->exists($page->source_image_path)) {
                // Fallback to original source page if no generated version selected
                $fileAbsPath = Storage::disk('local')->path($page->source_image_path);
                $ext = pathinfo($fileAbsPath, PATHINFO_EXTENSION) ?: 'png';
                $entryName = sprintf("page_%03d_original.%s", $page->page_number, $ext);
                $zip->addFile($fileAbsPath, $entryName);
            }
        }

        $zip->close();

        return "{$exportDir}/{$zipFilename}";
    }
}
