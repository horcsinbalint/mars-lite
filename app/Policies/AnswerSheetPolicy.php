<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Role;

/**
 * Contains authorization policies
 * related to anonymous questions.
 */
class AnswerSheetPolicy
{
    /**
     * Whether the user can create questions,
     * access results etc.
     */
    public function administer(User $user): bool
    {
        return $user->isAdmin()
          || $user->hasRole(
            [
                Role::STUDENT_COUNCIL_SECRETARY,
                Role::STUDENT_COUNCIL => array_merge(Role::STUDENT_COUNCIL_LEADERS, Role::COMMITTEE_LEADERS)
            ]);
    }
}
