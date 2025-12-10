<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use \App\Traits\HasAssociations;
    
    /**
     * @OA\Schema(
     *     schema="Activity",
     *     type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="type", type="string"),
     *     @OA\Property(property="content", type="string"),
     *     @OA\Property(property="scheduled_at", type="string", format="date-time"),
     *     @OA\Property(property="status", type="string"),
     *     @OA\Property(property="created_at", type="string", format="date-time"),
     *     @OA\Property(property="updated_at", type="string", format="date-time")
     * )
     */
    protected $table = 'inmo_activities';

    protected $fillable = [
        'type',
        'content',
        'scheduled_at',
        'completed_at',
        'status',
        'created_by',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'pending')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at');
    }
}
