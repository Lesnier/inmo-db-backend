<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAssociations;

class Meeting extends Model
{
    use HasFactory, HasAssociations;

    protected $table = 'inmo_meetings';
    
    /**
     * @OA\Schema(
     *     schema="Meeting",
     *     type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="subject", type="string"),
     *     @OA\Property(property="meeting_type", type="string"),
     *     @OA\Property(property="scheduled_at", type="string", format="date-time"),
     *     @OA\Property(property="duration_minutes", type="integer"),
     *     @OA\Property(property="created_at", type="string", format="date-time"),
     *     @OA\Property(property="updated_at", type="string", format="date-time")
     * )
     */
    protected $fillable = [
        'subject',
        'description',
        'meeting_type',
        'scheduled_at',
        'duration_minutes',
        'created_by',
        'host_id',
        'location',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'scheduled_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function host()
    {
        return $this->belongsTo(User::class, 'host_id');
    }
}
