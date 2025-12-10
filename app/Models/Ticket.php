<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAssociations;

class Ticket extends Model
{
    use HasFactory, HasAssociations;

    protected $table = 'inmo_tickets';

    protected $fillable = [
        'title',
        'description',
        'type',
        'priority',
        'status',
        'pipeline_id',
        'stage_id',
        'owner_id',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
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
