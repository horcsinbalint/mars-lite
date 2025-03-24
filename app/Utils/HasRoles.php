<?php

namespace App\Utils;

use App\Models\Role;
use App\Models\RoleObject;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Support\Facades\Log;

/**
 * Helper class for role getters/setters.
 */
trait HasRoles
{
    abstract public function roles(): BelongsToMany;
    abstract public static function getById(int $id);
    abstract public function getId(): int;
    abstract public function roleUsers(): HasMany;

    /**
     * Scope a query to only include users with the given role.
     * Usage: ->withRole(...)
     * See also: hasRole(...) getter for User models.
     *
     * @param Builder $query
     * @param Role|int|string $role
     * @param Workshop|RoleObject|string|null $object
     * @return Builder
     */
    public function scopeWithRole(Builder $query, Role|int|string $role, Workshop|RoleObject|string $object = null): Builder
    {
        $role = Role::get($role);
        if ($object) {
            $object = $role->getObject($object);
        }
        if ($object instanceof RoleObject) {
            return $query->whereHas('roles', function ($q) use ($role, $object) {
                $q->where('role_users.role_id', $role->id)
                    ->where('role_users.object_id', $object->id);
            });
        }
        if ($object instanceof Workshop) {
            return $query->whereHas('roles', function ($q) use ($role, $object) {
                $q->where('role_users.role_id', $role->id)
                    ->where('role_users.workshop_id', $object->id);
            });
        }
        return $query->whereHas('roles', function ($q) use ($role) {
            $q->where('role_users.role_id', $role->id);
        });
    }

    /**
     * Scope a query to only include users with all the given roles.
     * Usage: ->withAllRoles(...)
     *
     * @param Builder $query
     * @param Role[]|int[]|string[] $allRoles a homogeneous array of Role objects, role IDs or role names
     * @return Builder
     */
    public function scopeWithAllRoles(Builder $query, array $allRoles): Builder
    {
        // Empty array => nothing to filter, nothing to do
        if (empty($allRoles)) {
            return $query;
        }

        // Input is an array of role names => filter based on names
        foreach ($allRoles as $key => $value) {
            $role = $value;
            if(! $value instanceof Role) {
                $role = Role::get($role);
            }
            $query->whereHas('roles', function (Builder $query) use ($role) {
                $query->where('name', $role->name);
            });
        }
        return $query;
    }

    private $_currentRoles = null;

    /**
     * Decides if the user has any of the given roles.
     * See also: withRole(...) scope for query builders.
     *
     * If a base_role => [possible_objects] array is given, it will check if the user has the base_role with any of the possible_objects.
     *
     * Example usage:
     * hasRole(Role::COLLEGIST)
     * hasRole(Role::collegist()))
     * hasRole([Role::COLLEGIST => Role::EXTERN])
     * hasRole([Role::COLLEGIST => 4, Role::get(Role::WORKSHOP_LEADER)])
     * hasRole([Role::STUDENT_COUNCIL => [Role::PRESIDENT, Role::SCIENCE_VICE_PRESIDENT]]])
     *
     *
     * @param $roles array|int|string|Role|[Role|name|id|[Role|name => RoleObject|Workshop|name|id]]
     * @return bool
     */
    public function hasRole(array|int|string|Role $roles): bool
    {
        if($this->_currentRoles == null){
            $this->_currentRoles = $this->roleUsers->all();
        }
        $user_roles = $this->_currentRoles;
        $hasRoleLambda = function () use($user_roles, $roles) {
            if (!is_array($roles)) {
                $roles = [$roles];
            }
            foreach ($roles as $key => $value) {
                if (is_integer($key)) {
                    $role = Role::get($value);
                    foreach ($user_roles as $user_role) {
                        if ($user_role->role_id == $role->id) {
                            return true;
                        }
                    }
                } else {
                    $role = Role::get($key);
                    if (!is_array($value)) {
                        $value = [$value];
                    }
                    foreach($value as $object){
                        $object = $role->getObject($object);
                        foreach ($user_roles as $user_role) {
                            if ($user_role->role_id == $role->id && ($user_role->object_id == $object->id || $user_role->workshop_id == $object->id)) {
                                return true;
                            }
                        }
                    }
                }
            }
            return false;
        };
        return once($hasRoleLambda);
    }


    /**
     * Attach a role to the user.
     * @param Role $role
     * @param RoleObject|Workshop|null $object
     * @return bool
     */
    public function addRole(Role $role, Workshop|RoleObject $object = null): bool
    {
        if (!$role->isValid($object)) {
            return false;
        }

        if ($role->has_objects) {
            //if adding a collegist role to a collegist
            if ($role->name == Role::COLLEGIST) {
                //delete other object, if exists
                if($this->hasRole(Role::COLLEGIST)) {
                    $this->roles()->detach($role->id);
                }
                $this->roles()->attach($role->id, ['object_id' => $object->id]);
            } elseif ($this->roles()->where('id', $role->id)->wherePivot('object_id', $object->id)->doesntExist()) {
                $this->roles()->attach($role->id, ['object_id' => $object->id]);
            }
        } elseif ($role->has_workshops) {
            if ($this->roles()->where('id', $role->id)->wherePivot('workshop_id', $object->id)->doesntExist()) {
                $this->roles()->attach($role->id, ['workshop_id' => $object->id]);
            }
        } else {
            if ($this->roles()->where('id', $role->id)->doesntExist()) {
                $this->roles()->attach($role->id);
            }
        }
        return true;
    }

    /**
     * Detach a role from a user. Assumes a valid role-object pair.
     * @param Role $role
     * @param RoleObject|Workshop|null $object
     * @return void
     */
    public function removeRole(Role $role, Workshop|RoleObject $object = null): void
    {
        if ($role->has_objects && isset($object)) {
            $this->roles()->where('roles.id', $role->id)->wherePivot('object_id', $object->id)->detach($role->id);
        } elseif ($role->has_workshops && isset($object)) {
            $this->roles()->where('roles.id', $role->id)->wherePivot('workshop_id', $object->id)->detach($role->id);
        } else {
            $this->roles()->detach($role->id);
        }
    }


    public function isSenior(): bool
    {
        return $this->hasRole(Role::SENIOR);
    }

    /**
     * Determine if the user is a sys admin.
     * @return boolean
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(Role::SYS_ADMIN);
    }
    /**
     * Determine if the user is a collegist (including alumni).
     * @return boolean
     */
    public function isCollegist($alumni = true): bool
    {
        if ($this->verified == false) {
            return $this->roles()->where('role_id', Role::collegist()->id)->exists();
        }
        $accepted_roles = [Role::COLLEGIST];
        if($alumni){
            $accepted_roles[] = Role::ALUMNI;
        }
        return $this->hasRole($accepted_roles);
    }
    /**
     * Decides if the user is a resident collegist currently.
     *
     * @return bool
     */
    public function isResident(): bool
    {
        return $this->hasRole([Role::COLLEGIST => Role::RESIDENT]);
    }

    /**
     * Decides if the user is an extern collegist currently.
     *
     * @return bool
     */
    public function isExtern(): bool
    {
        if ($this->verified == false) {
            return $this->roles()
                ->where('role_id', Role::collegist()->id)
                ->where('object_id', RoleObject::firstWhere('name', Role::EXTERN)->id)
                ->exists();
        }
        return $this->hasRole([Role::COLLEGIST => Role::EXTERN]);
    }

    /**
     * Determine if the user has an alumni role.
     * @return boolean
     */
    public function isAlumni(): bool
    {
        return $this->hasRole(Role::ALUMNI);
    }

    /**
     * Determine if the user has a secretary role.
     * @return boolean
     */
    public function isSecretary(): bool
    {
        return $this->hasRole(Role::SECRETARY);
    }
    /**
     * Determine if the user has a secretary role.
     * @return boolean
     */
    public function isStudentCouncilSecretary(): bool
    {
        return $this->hasRole(Role::STUDENT_COUNCIL_SECRETARY);
    }

    /**
     * Determine if the user has a staff role.
     * @return boolean
     */
    public function isStaff(): bool
    {
        return $this->hasRole(Role::STAFF);
    }

    /**
     * Determine if the user has a tenant role.
     * @return boolean
     */
    public function isTenant(): bool
    {
        return $this->hasRole(Role::TENANT);
    }

    public function isCommunicationLeader(): bool {
        return $this->hasRole([Role::STUDENT_COUNCIL => Role::COMMUNICATION_LEADER]);
    }

    public function isCommunicationMember(): bool {
        return $this->hasRole([Role::STUDENT_COUNCIL => Role::COMMUNICATION_MEMBER]);
    }

    public function isCommunicationReferent(): bool {
        return $this->hasRole([Role::STUDENT_COUNCIL => Role::COMMUNICATION_REFERENT]);
    }

    public function isCommunityLeader(): bool {
        return $this->hasRole([Role::STUDENT_COUNCIL => Role::COMMUNITY_LEADER]);
    }

    public function isCommunityMember(): bool {
        return $this->hasRole([Role::STUDENT_COUNCIL => Role::COMMUNITY_MEMBER]);
    }

    public function isCommunityReferent(): bool {
        return $this->hasRole([Role::STUDENT_COUNCIL => Role::COMMUNITY_REFERENT]);
    }

    public function isEconomicVicePresident(): bool {
        return $this->hasRole([Role::STUDENT_COUNCIL => Role::ECONOMIC_VICE_PRESIDENT]);
    }

    public function isKKTHandler(): bool {
        return $this->hasRole([Role::STUDENT_COUNCIL => Role::KKT_HANDLER]);
    }

    /**
     * Determine if the user has role that is associated with the student council
     * @return boolean
     */
    public function isStudentCouncil(): bool
    {
        return $this->hasRole(Role::STUDENT_COUNCIL);
    }

    /**
     * Determine if the user has role that is associated with the student council
     * @return boolean
     */
    public function isDirector(): bool
    {
        return $this->hasRole(Role::DIRECTOR);
    }
    public function isWorkshopLeader(): bool
    {
        return $this->hasRole(Role::WORKSHOP_LEADER);
    }
    public function isApplicationCommitteeMember(): bool
    {
        return $this->hasRole(Role::APPLICATION_COMMITTEE_MEMBER);
    }
    public function isAggregatedApplicationCommitteeMember(): bool
    {
        return $this->hasRole(Role::AGGREGATED_APPLICATION_COMMITTEE_MEMBER);
    }
    public function isReceptionist(): bool
    {
        return $this->hasRole(Role::RECEPTIONIST);
    }
    public function isWorkshopAdministrator(): bool
    {
        return $this->hasRole(Role::WORKSHOP_ADMINISTRATOR);
    }

    public function isCollegeLeader(): bool
    {
        return $this->isDirector() || $this->isSecretary();
    }

    public function isCollegeMaintainer(): bool
    {
        return $this->isCollegeLeader() || $this->isStaff();
    }

    public function isStudentCouncilMember(): bool {
        return $this->hasRole([
            Role::STUDENT_COUNCIL => array_merge(Role::STUDENT_COUNCIL_LEADERS, Role::COMMITTEE_LEADERS)
        ]);
    }

    public function isStudentCouncilLeader(): bool {
        return $this->hasRole([
            Role::STUDENT_COUNCIL => Role::STUDENT_COUNCIL_LEADERS
        ]);
    }
}
