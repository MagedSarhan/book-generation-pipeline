<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'conversation_id',
        'sender_type',
        'body',
        'attachments',
        'generation_parameters',
        'batch_id',
    ];

    protected $casts = [
        'attachments' => 'array',
        'generation_parameters' => 'array',
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

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function batch()
    {
        return $this->belongsTo(GenerationBatch::class, 'batch_id');
    }
}
