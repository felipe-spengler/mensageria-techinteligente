<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\MessageLog;
use App\Models\User;

class MessageLogPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MessageLog $messageLog): bool
    {
        return $user->is_admin || ($messageLog->apiKey && $messageLog->apiKey->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return false; // API only
    }

    public function update(User $user, MessageLog $messageLog): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, MessageLog $messageLog): bool
    {
        return $user->is_admin;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MessageLog $messageLog): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MessageLog $messageLog): bool
    {
        return false;
    }
}
