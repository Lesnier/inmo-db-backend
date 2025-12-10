<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DealStageHistory extends Model
{
    use HasFactory;

    protected $table = 'inmo_deal_stage_history';

    protected $fillable = [
        'deal_id',
        'stage_id',
        'pipeline_id',
        'entered_at',
        'exited_at',
        'duration_minutes'
    ];
    
    protected $casts = [
        'entered_at' => 'datetime',
        'exited_at' => 'datetime',
    ];

    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    public function stage()
    {
        return $this->belongsTo(PipelineStage::class);
    }
}
