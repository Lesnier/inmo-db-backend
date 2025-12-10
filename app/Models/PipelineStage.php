<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PipelineStage extends Model
{
    use HasFactory;
    protected $table = 'inmo_pipeline_stages';

    protected $fillable = ['pipeline_id', 'name', 'position', 'probability'];

    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class);
    }
}
