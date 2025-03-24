<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;
use App\Models\Role;
use Illuminate\Auth\Access\HandlesAuthorization;

class RoomPolicy
{
    use HandlesAuthorization;

    /**
     * bypass for admins
     *
     * @param User $user
     * @return bool|void
     */
    public function before(User $user)
    {
        if ($user->isDirector() || $user->isCollegeLeader() ||
        $user->isStaff() || $user->isAdmin()) {
            return true;
        }
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->isCollegist();
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function updateAny(User $user)
    {
        return $user->isStudentCouncilLeader();
    }
}
