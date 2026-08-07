<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'name',
        'description',
        'master_prompt',
        'source_type',
        'source_file_path',
        'default_batch_size',
        'default_quality',
        'default_resolution',
        'custom_width',
        'custom_height',
        'default_output_format',
        'default_variants',
        'continuation_mode',
        'status',
    ];

    protected $casts = [
        'continuation_mode' => 'boolean',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pages()
    {
        return $this->hasMany(ProjectPage::class)->orderBy('page_number');
    }

    public function references()
    {
        return $this->hasMany(ProjectReference::class)->orderBy('sort_order');
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function batches()
    {
        return $this->hasMany(GenerationBatch::class);
    }

    public function jobs()
    {
        return $this->hasMany(GenerationJob::class);
    }

    public function masterStyleReference()
    {
        return $this->hasOne(ProjectReference::class)->where('role', 'master_style')->where('is_active', true);
    }
}
