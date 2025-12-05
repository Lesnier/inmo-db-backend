<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Property extends Model
{
    protected $table = 'real_estate_properties';

    protected $fillable = [
        'agent_id',
        'category_id',
        'title',
        'slug',
        'price',
        'status',
        'data',
        'published_at',
    ];

    protected $casts = [
        'data' => \App\Casts\PropertyDataCast::class,
        'published_at' => 'datetime',
        'price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
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
        return $this->belongsToMany(User::class, 'property_favorites', 'property_id', 'user_id');
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
