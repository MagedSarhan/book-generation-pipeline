<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectPage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class DocumentImporter
{
    /**
     * Import document or multiple image files into project pages.
     */
    public function import(Project $project, UploadedFile|array $files, string $sourceType): void
    {
        $projectDir = "projects/{$project->uuid}";
        Storage::disk('local')->makeDirectory("{$projectDir}/original");
        Storage::disk('local')->makeDirectory("{$projectDir}/pages");
        Storage::disk('local')->makeDirectory("{$projectDir}/thumbnails");

        if ($sourceType === 'pdf') {
            $this->processPdf($project, $files, $projectDir);
        } elseif ($sourceType === 'docx') {
            $this->processDocx($project, $files, $projectDir);
        } else {
            $this->processImages($project, is_array($files) ? $files : [$files], $projectDir);
        }
    }

    protected function processPdf(Project $project, UploadedFile $file, string $projectDir): void
    {
        $pdfPath = $file->storeAs("{$projectDir}/original", 'source_' . time() . '.pdf', 'local');
        $fullPdfPath = Storage::disk('local')->path($pdfPath);
        $project->update(['source_file_path' => $pdfPath]);

        $outputPrefix = Storage::disk('local')->path("{$projectDir}/pages/page");

        // Use pdftoppm to render pages as high quality PNGs
        $process = new Process(['pdftoppm', '-png', '-r', '150', $fullPdfPath, $outputPrefix]);
        $process->run();

        // Extract text if pdftotext is available
        $textPrefix = Storage::disk('local')->path("{$projectDir}/pages/page_text");

        $extractedPages = [];
        // Scan for generated images
        $pagesDir = Storage::disk('local')->path("{$projectDir}/pages");
        $files = glob("{$pagesDir}/page-*.png");
        natsort($files);

        $pageNumber = 1;
        foreach ($files as $filePath) {
            $relativePath = str_replace(Storage::disk('local')->path(''), '', $filePath);
            $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');

            // Thumbnail
            $thumbPath = $this->createThumbnail($filePath, "{$projectDir}/thumbnails/page_{$pageNumber}_thumb.jpg");
            $extractedText = $this->extractPdfPageText($fullPdfPath, $pageNumber);

            ProjectPage::create([
                'project_id' => $project->id,
                'page_number' => $pageNumber,
                'source_image_path' => $relativePath,
                'thumbnail_path' => $thumbPath,
                'extracted_text' => $extractedText,
                'status' => 'imported',
            ]);

            $pageNumber++;
        }
    }

    protected function processDocx(Project $project, UploadedFile $file, string $projectDir): void
    {
        $docxPath = $file->storeAs("{$projectDir}/original", 'source_' . time() . '.docx', 'local');
        $fullDocxPath = Storage::disk('local')->path($docxPath);
        $project->update(['source_file_path' => $docxPath]);

        // Convert DOCX to PDF using LibreOffice headless
        $outDir = Storage::disk('local')->path("{$projectDir}/original");
        $process = new Process(['libreoffice', '--headless', '--convert-to', 'pdf', '--outdir', $outDir, $fullDocxPath]);
        $process->run();

        $convertedPdf = glob("{$outDir}/*.pdf")[0] ?? null;
        if ($convertedPdf) {
            $fakeFile = new UploadedFile($convertedPdf, basename($convertedPdf));
            $this->processPdf($project, $fakeFile, $projectDir);
        }
    }

    protected function processImages(Project $project, array $files, string $projectDir): void
    {
        $pageNumber = 1;
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) continue;

            $ext = $file->getClientOriginalExtension() ?: 'png';
            $imagePath = $file->storeAs("{$projectDir}/pages", "page_{$pageNumber}_" . time() . ".{$ext}", 'local');
            $fullPath = Storage::disk('local')->path($imagePath);

            $thumbPath = $this->createThumbnail($fullPath, "{$projectDir}/thumbnails/page_{$pageNumber}_thumb.jpg");

            ProjectPage::create([
                'project_id' => $project->id,
                'page_number' => $pageNumber,
                'source_image_path' => $imagePath,
                'thumbnail_path' => $thumbPath,
                'extracted_text' => null,
                'status' => 'imported',
            ]);

            $pageNumber++;
        }
    }

    protected function extractPdfPageText(string $pdfPath, int $pageNumber): ?string
    {
        try {
            $process = new Process(['pdftotext', '-f', (string)$pageNumber, '-l', (string)$pageNumber, $pdfPath, '-']);
            $process->run();
            if ($process->isSuccessful()) {
                return trim($process->getOutput());
            }
        } catch (\Throwable $e) {
            // Non-critical if text extraction fails
        }
        return null;
    }

    protected function createThumbnail(string $sourcePath, string $targetRelativePath): string
    {
        $targetFullPath = Storage::disk('local')->path($targetRelativePath);
        $targetDir = dirname($targetFullPath);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Try GD thumbnail generation
        try {
            if (extension_loaded('gd') && file_exists($sourcePath)) {
                list($width, $height, $type) = getimagesize($sourcePath);
                $newWidth = 300;
                $newHeight = (int) ($height * ($newWidth / $width));

                $srcImg = match ($type) {
                    IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
                    IMAGETYPE_PNG => imagecreatefrompng($sourcePath),
                    IMAGETYPE_WEBP => imagecreatefromwebp($sourcePath),
                    default => null,
                };

                if ($srcImg) {
                    $dstImg = imagecreatetruecolor($newWidth, $newHeight);
                    imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagejpeg($dstImg, $targetFullPath, 80);
                    imagedestroy($srcImg);
                    imagedestroy($dstImg);
                    return $targetRelativePath;
                }
            }
        } catch (\Throwable $e) {
            // fallback copy source path if GD fails
        }

        // If thumbnail generation is skipped, return original path as thumbnail
        $relPath = str_replace(Storage::disk('local')->path(''), '', $sourcePath);
        return ltrim(str_replace('\\', '/', $relPath), '/');
    }
}
