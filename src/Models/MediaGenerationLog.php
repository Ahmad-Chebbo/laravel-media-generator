<?php

namespace AhmadChebbo\LaravelMediaGenerator\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaGenerationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'model_type',
        'model_id',
        'collection_name',
        'image_source',
        'batch_id',
        'file_name',
        'file_size',
        'dimensions',
        'generation_time',
        'metadata',
    ];

    protected $casts = [
        'dimensions' => 'array',
        'metadata' => 'array',
        'generation_time' => 'float',
    ];

    public function model()
    {
        return $this->morphTo();
    }
}
