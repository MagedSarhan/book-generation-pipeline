<?php

namespace App\Services;

use App\Models\AssetToken;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class AssetTokenService
{
    /**
     * Generate an expiring temporary public URL for Fal to fetch an asset safely.
     */
    public function generateUrl(string $filePath, int $expirationMinutes = 120): string
    {
        $token = Str::random(40);
        AssetToken::create([
            'token' => $token,
            'file_path' => $filePath,
            'expires_at' => now()->addMinutes($expirationMinutes),
        ]);

        return route('asset.token.show', ['token' => $token]);
    }

    /**
     * Resolve valid asset path by token.
     */
    public function resolveToken(string $token): ?string
    {
        $record = AssetToken::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            return null;
        }

        return $record->file_path;
    }
}
