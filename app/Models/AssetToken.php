<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetToken extends Model
{
    protected $fillable = ['token', 'file_path', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
