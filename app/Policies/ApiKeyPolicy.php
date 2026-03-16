<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\ApiKey;
use App\Models\User;

class ApiKeyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Everyone can see the list, but we use eloquent query to filter
    }

    public function view(User $user, ApiKey $apiKey): bool
    {
        return $user->is_admin || $apiKey->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, ApiKey $apiKey): bool
    {
        return $user->is_admin || $apiKey->user_id === $user->id;
    }

    public function delete(User $user, ApiKey $apiKey): bool
    {
        return $user->is_admin;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ApiKey $apiKey): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ApiKey $apiKey): bool
    {
        return false;
    }
}
