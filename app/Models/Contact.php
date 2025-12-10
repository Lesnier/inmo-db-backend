<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasFactory;
    protected $table = 'inmo_contacts';

    use \App\Traits\HasAssociations;
    
    /**
     * @OA\Schema(
     *     schema="Contact",
     *     type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="first_name", type="string"),
     *     @OA\Property(property="last_name", type="string"),
     *     @OA\Property(property="email", type="string"),
     *     @OA\Property(property="lead_status", type="string"),
     *     @OA\Property(property="owner_id", type="integer"),
     *     @OA\Property(property="created_at", type="string", format="date-time"),
     *     @OA\Property(property="updated_at", type="string", format="date-time")
     * )
     */
    protected $fillable = [
        'user_id',
        'owner_id', // Explicit owner
        'first_name',
        'last_name',
        'email',
        'phone',
        'mobile',
        'lifecycle_stage',
        'lead_status',
        'country',
        'state',
        'city',
        'address',
        'zip_code',
        'last_activity_at',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // Helpers
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
