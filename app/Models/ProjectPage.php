<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProjectPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'project_id',
        'page_number',
        'source_image_path',
        'thumbnail_path',
        'extracted_text',
        'selected_version_id',
        'status',
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

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function versions()
    {
        return $this->hasMany(PageVersion::class)->orderBy('version_number', 'desc');
    }

    public function selectedVersion()
    {
        return $this->belongsTo(PageVersion::class, 'selected_version_id');
    }

    public function generationJobs()
    {
        return $this->hasMany(GenerationJob::class)->orderBy('created_at', 'desc');
    }

    public function latestJob()
    {
        return $this->hasOne(GenerationJob::class)->latestOfMany();
    }
}
