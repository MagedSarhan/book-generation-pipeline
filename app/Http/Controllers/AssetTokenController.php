<?php

namespace App\Http\Controllers;

use App\Services\AssetTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetTokenController extends Controller
{
    public function __construct(
        protected AssetTokenService $tokenService
    ) {}

    public function show(string $token)
    {
        $filePath = $this->tokenService->resolveToken($token);
        if (!$filePath || !Storage::disk('local')->exists($filePath)) {
            abort(404, 'Asset not found or expired.');
        }

        $fullPath = Storage::disk('local')->path($filePath);
        $mime = mime_content_type($fullPath) ?: 'image/png';

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    public function direct(Request $request)
    {
        $path = $request->query('path');
        if (!$path) {
            abort(404);
        }

        // Prevent path traversal
        $path = ltrim(str_replace(['..', '\\'], ['', '/'], $path), '/');

        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'File not found');
        }

        $fullPath = Storage::disk('local')->path($path);
        $mime = mime_content_type($fullPath) ?: 'image/png';

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
