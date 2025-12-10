<?php

namespace App\Traits;

use App\Models\User;
use App\Models\Agent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasPublisher
{
    /**
     * Get the publisher user.
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publisher_id');
    }

    /**
     * Helper to get the Agent entity associated with the publisher User, if applicable.
     */
    public function getAgentAttribute()
    {
        if ($this->publisher_type === 'real_estate_agent' || $this->publisher_type === 'real_estate_agency') {
            return Agent::where('user_id', $this->publisher_id)->first();
        }
        return null;
    }

    /**
     * Scope a query to only include records by a specific publisher (User ID).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model|string|int  $publisherOrId User model or User ID
     * @param  string|null  $publisherType Optional role/type filter
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPublisher($query, $publisherOrId, $publisherType = null)
    {
        $userId = $publisherOrId instanceof Model ? $publisherOrId->getKey() : $publisherOrId;

        $query->where('publisher_id', $userId);

        if ($publisherType) {
            $query->where('publisher_type', $publisherType);
        }

        return $query;
    }
}
