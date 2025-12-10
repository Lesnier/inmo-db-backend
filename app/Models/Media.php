<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    protected $table = 'inmo_media';

    protected $fillable = [
        'model_id',
        'model_type',
        'type', // image, video, doc, plan, 3d_view
        'url',
        'meta',
        'position',
    ];

    protected $casts = [
        'meta' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function model()
    {
        return $this->morphTo();
    }
}
