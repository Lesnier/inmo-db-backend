<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    use HasFactory;

    protected $table = 'inmo_buildings';

    protected $fillable = [
        'agent_id',
        'user_id',
        'name',
        'slug',
        'address',
        'country',
        'state',
        'city',
        'district',
        'zip_code',
        'lat',
        'lng',
        'year_built',
        'floors',
        'data',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'year_built' => 'integer',
        'floors' => 'integer',
        'data' => 'array',
    ];

    /**
     * Agent who owns/manages this building
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    /**
     * User who owns this building
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Properties in this building
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'building_id');
    }
}
