<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Role;

class GeneralAssemblyPolicy
{

    /**
     * bypass for admins
     *
     * @param User $user
     * @return bool|void
     */
    public function before(User $user)
    {
        if ($user->isAdmin()) {
            return true;
        }
    }

    /**
     * Determine whether the user can view any general_assemblies.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->isCollegist(alumni: false) || $user->isCollegeLeader();
    }

    /**
     * Determine whether the user can administer votings (add general_assembly, add or change question etc.).
     */
    public function administer(User $user)
    {
        return $user->isStudentCouncilLeader() || $user->isStudentCouncilSecretary();
    }
}
