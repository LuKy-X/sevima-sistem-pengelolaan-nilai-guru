<?php

namespace App\Policies;

use App\Models\AssessmentColumn;
use App\Models\Rubric;
use App\Models\User;

class RubricPolicy
{
    /**
     * Determine whether the user can view the rubric.
     */
    public function view(User $user, Rubric $rubric): bool
    {
        return $user->id === $rubric->assessmentColumn->gradebook->user_id;
    }

    /**
     * Determine whether the user can create a rubric for the assessment column.
     */
    public function create(User $user, AssessmentColumn $assessmentColumn): bool
    {
        return $user->id === $assessmentColumn->gradebook->user_id;
    }

    /**
     * Determine whether the user can update the rubric.
     */
    public function update(User $user, Rubric $rubric): bool
    {
        return $user->id === $rubric->assessmentColumn->gradebook->user_id;
    }

    /**
     * Determine whether the user can delete the rubric.
     */
    public function delete(User $user, Rubric $rubric): bool
    {
        return $user->id === $rubric->assessmentColumn->gradebook->user_id;
    }
}
