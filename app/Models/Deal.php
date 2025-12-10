<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAssociations;

class Deal extends Model
{
    use HasFactory, HasAssociations;

    protected $table = 'inmo_deals';
    
    /**
     * @OA\Schema(
     *     schema="Deal",
     *     type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="title", type="string"),
     *     @OA\Property(property="amount", type="number", format="float"),
     *     @OA\Property(property="currency", type="string"),
     *     @OA\Property(property="status", type="string"),
     *     @OA\Property(property="pipeline_id", type="integer"),
     *     @OA\Property(property="stage_id", type="integer"),
     *     @OA\Property(property="expected_close_date", type="string", format="date"),
     *     @OA\Property(property="created_at", type="string", format="date-time"),
     *     @OA\Property(property="updated_at", type="string", format="date-time")
     * )
     */
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
