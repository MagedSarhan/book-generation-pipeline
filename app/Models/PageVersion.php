<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PageVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'project_page_id',
        'generation_job_id',
        'version_number',
        'image_path',
        'provider_url',
        'width',
        'height',
        'content_type',
        'is_selected',
        'user_instruction',
    ];

    protected $casts = [
        'is_selected' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function page()
    {
        return $this->belongsTo(ProjectPage::class, 'project_page_id');
    }

    public function generationJob()
    {
        return $this->belongsTo(GenerationJob::class, 'generation_job_id');
    }
}
