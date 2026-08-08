<?php

namespace App\Services;

use App\Models\AssetToken;
use Illuminate\Support\Str;

class AssetTokenService
{
    /**
     * Generate an expiring temporary public URL for Fal to fetch an asset safely.
     */
    public function generateUrl(string $filePath, int $expirationMinutes = 240): string
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
     * Resolve valid asset path by token safely.
     */
    public function resolveToken(string $token): ?string
    {
        $record = AssetToken::where('token', $token)->first();

        if (!$record) {
            return null;
        }

        // Allow up to 12 hours grace period to protect against server/DB timezone offsets
        if ($record->expires_at && $record->expires_at->lt(now()->subHours(12))) {
            return null;
        }

        return $record->file_path;
    }
}
