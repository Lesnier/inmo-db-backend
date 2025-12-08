<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Property extends Model
{
    protected $table = 'inmo_properties';

    protected $fillable = [
        'agent_id',
        'category_id',
        'building_id',
        'operation_type',
        'type_of_offer',
        'title',
        'slug',
        'price',
        'currency',
        'status',
        'published_at',
        'country',
        'state',
        'city',
        'district',
        'zip_code',
        'street_address',
        'lat',
        'lng',
        'data',
    ];

    protected $casts = [
        'data' => \App\Casts\PropertyDataCast::class,
        'published_at' => 'datetime',
        'price' => 'decimal:2',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class, 'building_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(PropertyMedia::class, 'property_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(PropertyContact::class, 'property_id');
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'inmo_favorites', 'property_id', 'user_id');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNotNull('published_at');
    }

    public function scopeByAgent($query, $agentId)
    {
        return $query->where('agent_id', $agentId);
    }
}
