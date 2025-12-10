<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pipeline extends Model
{
    use HasFactory;
    
    /**
     * @OA\Schema(
     *     schema="Pipeline",
     *     type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="entity_type", type="string"),
     *     @OA\Property(property="stages", type="array", @OA\Items(ref="#/components/schemas/PipelineStage")),
     *     @OA\Property(property="created_at", type="string", format="date-time"),
     *     @OA\Property(property="updated_at", type="string", format="date-time")
     * )
     */
    protected $table = 'inmo_pipelines';

    protected $fillable = ['name', 'entity_type'];

    public function stages()
    {
        return $this->hasMany(PipelineStage::class)->orderBy('position');
    }
}
