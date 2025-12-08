<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Proposal extends Model
{
    protected $table = 'inmo_proposals';

    protected $fillable = [
        'agent_id',
        'client_id',
        'name',
        'description',
        'status',
        'share_token',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($proposal) {
            if (!$proposal->share_token) {
                $proposal->share_token = Str::random(32);
            }
        });
    }

    // Relationships
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'inmo_proposal_properties')
            ->withPivot('order', 'notes')
            ->withTimestamps()
            ->orderBy('inmo_proposal_properties.order');
    }

    // Scopes
    public function scopeByToken($query, $token)
    {
        return $query->where('share_token', $token);
    }
}
