<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAssociations;

class Deal extends Model
{
    use HasFactory, HasAssociations;

    protected $table = 'inmo_deals';

    protected $fillable = [
        'title',
        'amount',
        'currency',
        'status',
        'pipeline_id',
        'stage_id',
        'owner_id',
        'expected_close_date',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'amount' => 'decimal:2',
        'expected_close_date' => 'date',
    ];

    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function stage()
    {
        return $this->belongsTo(PipelineStage::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
