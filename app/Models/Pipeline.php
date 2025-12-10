<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pipeline extends Model
{
    use HasFactory;
    protected $table = 'inmo_pipelines';

    protected $fillable = ['name', 'entity_type'];

    public function stages()
    {
        return $this->hasMany(PipelineStage::class)->orderBy('position');
    }
}
