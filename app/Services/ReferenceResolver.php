<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectPage;
use App\Models\ProjectReference;
use App\Models\PageVersion;
use Illuminate\Support\Facades\Storage;

class ReferenceResolver
{
    public function __construct(
        protected AssetTokenService $assetTokenService
    ) {}

    /**
     * Resolve reference URLs for Fal request in strict deterministic order.
     * Enforces maximum 16 images limit.
     */
    public function resolve(
        Project $project,
        ProjectPage $page,
        string $mode = 'redesign',
        ?string $maskPath = null
    ): array {
        $assetUrls = [];
        $rawPaths = [];

        // 1. Source Page (Image 1)
        if ($mode === 'edit' && $page->selectedVersion) {
            $sourcePath = $page->selectedVersion->image_path;
        } else {
            $sourcePath = $page->source_image_path;
        }
        $rawPaths['source_page'] = $sourcePath;
        $assetUrls[] = $this->assetTokenService->generateUrl($sourcePath);

        // 2. Master Style Reference (Image 2)
        $masterStyle = $project->references()
            ->where('role', 'master_style')
            ->where('is_active', true)
            ->first();

        $stylePath = $masterStyle ? $masterStyle->image_path : null;
        if ($stylePath) {
            $rawPaths['style_reference'] = $stylePath;
            $assetUrls[] = $this->assetTokenService->generateUrl($stylePath);
        }

        // 3. Previous Page Selected Version (Image 3 - Sequential Continuity)
        $previousPageUrl = null;
        if ($project->continuation_mode && $page->page_number > 1) {
            $prevPage = ProjectPage::where('project_id', $project->id)
                ->where('page_number', $page->page_number - 1)
                ->with('selectedVersion')
                ->first();

            if ($prevPage && $prevPage->selectedVersion) {
                $prevPath = $prevPage->selectedVersion->image_path;
                $rawPaths['previous_page'] = $prevPath;
                $assetUrls[] = $this->assetTokenService->generateUrl($prevPath);
            }
        }

        // 4. Extra References (Images 4+)
        $extraRefs = $project->references()
            ->where('role', '!=', 'master_style')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $extraUrls = [];
        foreach ($extraRefs as $idx => $extra) {
            if (count($assetUrls) >= 16) break; // Hard provider limit
            $url = $this->assetTokenService->generateUrl($extra->image_path);
            $assetUrls[] = $url;
            $extraUrls[] = $url;
            $rawPaths["extra_reference_" . ($idx + 1)] = $extra->image_path;
        }

        // Optional Mask Image
        $maskUrl = null;
        if ($maskPath) {
            $maskUrl = $this->assetTokenService->generateUrl($maskPath);
            $rawPaths['mask_image'] = $maskPath;
        }

        return [
            'image_urls' => $assetUrls,
            'source_page' => $assetUrls[0] ?? '',
            'style_reference' => $assetUrls[1] ?? '',
            'previous_page' => $assetUrls[2] ?? '',
            'extra_reference_1' => $extraUrls[0] ?? '',
            'extra_reference_2' => $extraUrls[1] ?? '',
            'mask_url' => $maskUrl,
            'raw_paths' => $rawPaths,
        ];
    }
}
