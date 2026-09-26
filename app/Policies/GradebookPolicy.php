<?php

namespace App\Policies;

use App\Models\Gradebook;
use App\Models\User;

class GradebookPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Gradebook $gradebook): bool
    {
        return $user->id === $gradebook->user_id;
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
    public function update(User $user, Gradebook $gradebook): bool
    {
        return $user->id === $gradebook->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Gradebook $gradebook): bool
    {
        return $user->id === $gradebook->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Gradebook $gradebook): bool
    {
        return $user->id === $gradebook->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Gradebook $gradebook): bool
    {
        return $user->id === $gradebook->user_id;
    }
}
