<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GenerationBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'project_id',
        'title',
        'total_jobs',
        'completed_jobs',
        'failed_jobs',
        'pending_jobs',
        'status',
        'instruction',
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

    public function jobs()
    {
        return $this->hasMany(GenerationJob::class);
    }
}
