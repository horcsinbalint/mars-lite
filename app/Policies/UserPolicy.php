<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\RoleObject;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * We let admins do anything here.
     */
    public function before(User $user)
    {
        if ($user->isAdmin()) {
            return true;
        }
    }


    /**
     * @param User $user
     * @return bool
     */
    public function viewAll(User $user): bool
    {
        return $user->isCollegeMaintainer() ||
               $user->isStudentCouncilSecretary() ||
               $user->isStudentCouncilMember();
    }

    /**
     * @param User $user
     * @return bool
     */
    public function viewSome(User $user): bool
    {
        return $this->viewAll($user)
            || $user->isWorkshopAdministrator()
            || $user->isWorkshopLeader();
    }

    /**
     * @param User $user
     * @return bool
     *
     * @deprecated use viewAll or viewSome instead
     */
    public function viewAny(User $user): bool
    {
        return $this->viewSome($user);
    }

    /**
     * @param User $user
     * @return bool
     */
    public function viewSemesterEvaluation(User $user): bool
    {
        return $user->isCollegeLeader() ||
               $user->isWorkshopLeader() ||
               $user->isStudentCouncilSecretary() ||
               $user->isStudentCouncilLeader();
    }

    /**
     * @param User $user
     * @param User $target
     * @return bool
     */
    public function view(User $user, User $target): bool
    {
        if ($user->id == $target->id) {
            return true;
        }
        if ($target->isCollegist()) {
            return $user->isCollegeLeader() ||
                     $user->isStudentCouncilMember() ||
                     $user->isStudentCouncilSecretary() ||
                     $target->workshops
                    ->intersect($user->roleWorkshops)
                    ->count() > 0;
        } elseif ($target->isTenant()) {
            return $user->isStaff() || $user->isStudentCouncilLeader();
        }
        return false;
    }


    /** Permission related policies */

    /**
     * @param User $user
     * @param User $target
     * @param Role|null $role
     * @return bool
     */
    public function updateAnyPermission(User $user): bool
    {
        return $user->isCollegeMaintainer() ||
                $user->isStudentCouncilMember() ||
                $user->isStudentCouncilSecretary() ||
                $user->isWorkshopAdministrator() ||
                $user->isWorkshopLeader();
    }

    /**
     * @param User $user
     * @param User $target
     * @param Role $role
     * @param RoleObject|Workshop|null $object
     * @return bool
     */
    public function updatePermission(User $user, User $target, Role $role, Workshop|RoleObject $object = null): bool
    {
        if ($role->name == Role::TENANT) {
            return $user->isStaff();
        }

        if ($role->name == Role::COLLEGIST || $role->name == Role::ALUMNI || $role->name == Role::SENIOR) {
            return $user->isCollegeLeader() || $user->isStudentCouncilLeader();
        }

        if ($role->name == Role::APPLICATION_COMMITTEE_MEMBER) {
            return $user->roleWorkshops->contains($object->id)
                    || $user->isStudentCouncilLeader();
        }

        if ($role->name == Role::AGGREGATED_APPLICATION_COMMITTEE_MEMBER) {
            return $user->isStudentCouncilLeader() || $user->isStudentCouncilSecretary();
        }

        if ($role->name == Role::WORKSHOP_LEADER) {
            return $user->isCollegeLeader();
        }

        if ($role->name == Role::WORKSHOP_ADMINISTRATOR) {
            return ($user->isWorkshopLeader()
                    && $user->roleWorkshops->contains($object->id)
            ) || $user->isCollegeLeader() ||
            $user->isStudentCouncilLeader() ||
            $user->isStudentCouncilSecretary();
        }

        if ($role->name == Role::STUDENT_COUNCIL_SECRETARY ||
            $role->name == Role::BOARD_OF_TRUSTEES_MEMBER ||
            $role->name == Role::ETHICS_COMMISSIONER) {
            return $user->isStudentCouncilSecretary();
        }

        if ($role->name == Role::STUDENT_COUNCIL) {
            if ($user->isStudentCouncilSecretary()) {
                return true;
            }
            if ($object?->name == Role::PRESIDENT) {
                return false;
            }
            if ($user->isStudentCouncilLeader()) {
                return true;
            }
            if (in_array($object?->name, Role::COMMITTEE_MEMBERS) || in_array($object?->name, Role::COMMITTEE_REFERENTS)) {
                $committee = preg_split("~-~", $object->name)[0];
                return $user->hasRole([Role::STUDENT_COUNCIL => $committee . "-leader"]);
            }
        }
        return false;
    }

    /**
     * @param User $user
     * @param User $target
     * @return bool
     */
    public function updateStatus(User $user, User $target): bool
    {
        if (!$target->isCollegist()) {
            return false;
        }
        if ($user->isCollegeLeader() ||
            $user->isStudentCouncilLeader()) {
            return true;
        }
        return $user->roleWorkshops->intersect($target->workshops)->count() > 0;
    }

    /**
     * @param User $user
     * @param User $target
     * @param Workshop $workshop
     * @return bool
     */
    public function updateWorkshop(User $user, User $target, Workshop $workshop): bool
    {
        if ($user->isCollegeLeader() ||
            $user->isStudentCouncilLeader()) {
            return true;
        }
        return $user->roleWorkshops->has($workshop->id);
    }

    /**
     * @param User $user
     * @return bool
     */
    public function handleGuests(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * @param User $user
     * @return bool
     */
    public function invite(User $user): bool
    {
        return $user->isCollegeLeader();
    }
}
