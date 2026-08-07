<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GenerationJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'project_id',
        'project_page_id',
        'generation_batch_id',
        'fal_request_id',
        'provider',
        'mode',
        'quality',
        'width',
        'height',
        'output_format',
        'num_images',
        'compiled_prompt',
        'input_metadata',
        'status',
        'attempts',
        'error_message',
        'raw_provider_payload',
        'raw_provider_response',
        'queued_at',
        'submitted_at',
        'completed_at',
    ];

    protected $casts = [
        'input_metadata' => 'array',
        'raw_provider_payload' => 'array',
        'raw_provider_response' => 'array',
        'queued_at' => 'datetime',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
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

    public function page()
    {
        return $this->belongsTo(ProjectPage::class, 'project_page_id');
    }

    public function batch()
    {
        return $this->belongsTo(GenerationBatch::class, 'generation_batch_id');
    }

    public function versions()
    {
        return $this->hasMany(PageVersion::class, 'generation_job_id');
    }
}
