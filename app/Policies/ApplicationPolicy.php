<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\Role;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApplicationPolicy
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
        if ($user->isAdmin()) {
            return true;
        }
    }

    /**
     * @param User $user
     * @param Application $target
     * @return bool
     */
    public function view(User $user, Application $target): bool
    {
        if ($user->id == $target->user_id || $user->can('viewAll', Application::class)) {
            return true;
        } else {
            return $target->appliedWorkshops
                ->intersect($user->applicationCommitteWorkshops)
                ->count() > 0
            || $target->appliedWorkshops
                ->intersect($user->roleWorkshops)
                ->count() > 0;
        }
    }

    /**
     * @param User $user
     * @return bool
     */
    public function viewSome(User $user): bool
    {
        return $user->isCollegeLeader() ||
        $user->isWorkshopAdministrator() ||
        $user->isWorkshopLeader() ||
        $user->isApplicationCommitteeMember() ||
        $user->isStudentCouncilLeader() ||
        $user->isAggregatedApplicationCommitteeMember();
    }

    /**
     * @param User $user
     * @return bool
     */
    public function editStatus(User $user, ?Workshop $workshop = null): bool
    {
        if($user->isCollegeLeader() || $user->isStudentCouncilLeader()) {
            return true;
        }
        if ($workshop) {
            if($user->isWorkshopLeader()) {
                return $user->roleWorkshops->contains($workshop);
            }
        }
        return false;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function viewAll(User $user): bool
    {
        return $user->isStudentCouncilLeader() ||
                $user->isCollegeLeader() ||
                $user->isWorkshopAdministrator() ||
                $user->isAggregatedApplicationCommitteeMember();
    }

    /**
     * @param User $user
     * @return bool
     */
    public function viewUnfinished(User $user): bool
    {
        return $user->isStudentCouncilLeader() ||
                $user->isCollegeLeader();
    }

    /**
     * Returns true if the user can finalize the application process.
     * @param User $user
     * @return bool
     */
    public function finalize(User $user): bool
    {
        return $user->isAdmin() || $user->isCollegeLeader();
    }

}
