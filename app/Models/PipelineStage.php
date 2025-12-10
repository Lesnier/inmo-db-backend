<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PipelineStage extends Model
{
    use HasFactory;
    
    /**
     * @OA\Schema(
     *     schema="PipelineStage",
     *     type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="pipeline_id", type="integer"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="position", type="integer"),
     *     @OA\Property(property="probability", type="integer"),
     *     @OA\Property(property="created_at", type="string", format="date-time"),
     *     @OA\Property(property="updated_at", type="string", format="date-time")
     * )
     */
    protected $table = 'inmo_pipeline_stages';

    protected $fillable = ['pipeline_id', 'name', 'position', 'probability'];

    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class);
    }
}
