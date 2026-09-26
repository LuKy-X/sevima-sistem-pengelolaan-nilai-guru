<?php

namespace App\Policies;

use App\Models\AssessmentColumn;
use App\Models\Gradebook;
use App\Models\User;

class AssessmentColumnPolicy
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
    public function view(User $user, AssessmentColumn $assessmentColumn): bool
    {
        return $user->id === $assessmentColumn->gradebook->user_id;
    }

    /**
     * Determine whether the user can create models in the given gradebook.
     */
    public function create(User $user, ?Gradebook $gradebook = null): bool
    {
        if ($gradebook === null) {
            return true;
        }

        return $user->id === $gradebook->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AssessmentColumn $assessmentColumn): bool
    {
        return $user->id === $assessmentColumn->gradebook->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AssessmentColumn $assessmentColumn): bool
    {
        return $user->id === $assessmentColumn->gradebook->user_id;
    }
}
