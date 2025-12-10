<?php

namespace App\Policies;

use App\Models\Deal;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DealPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Allow list for auth users (controller filters by owner)
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Deal $deal): bool
    {
        return $user->id === $deal->owner_id || $user->role === 'admin';
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Deal $deal): bool
    {
        return $user->id === $deal->owner_id || $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Deal $deal): bool
    {
        return $user->id === $deal->owner_id || $user->role === 'admin';
    }
}
