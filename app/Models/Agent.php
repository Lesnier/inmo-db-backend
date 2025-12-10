<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    use HasFactory;
    protected $table = 'inmo_agents';

    protected $fillable = [
        'user_id',
        'status',
        'onboarding_status',
        'plan_id',
        'data',
    ];

    protected $casts = [
        'data' => \App\Casts\AgentDataCast::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function properties(): HasMany
    {
        // Agent properties are typically those where they are the publisher (user_id)
        return $this->hasMany(Property::class, 'publisher_id', 'user_id');
    }

    // CRM Integration
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'owner_id', 'user_id');
    }

    public function leads(): HasMany
    {
        return $this->contacts()->where('lifecycle_stage', 'lead');
    }

    public function clients(): HasMany
    {
        return $this->contacts()->whereIn('lifecycle_stage', ['customer', 'client']);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'owner_id', 'user_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'owner_id', 'user_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'created_by', 'user_id');
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class, 'publisher_id', 'user_id');
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeOnboarded($query)
    {
        return $query->where('onboarding_status', 'complete');
    }
}
