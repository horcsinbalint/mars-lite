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
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        foreach ($roles as $key => $value) {
            if (is_integer($key)) {
                $role = Role::get($value);
                foreach ($this->roleUsers->all() as $user_role) {
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
                    foreach ($this->roleUsers->all() as $user_role) {
                        if ($user_role->role_id == $role->id && ($user_role->object_id == $object->id || $user_role->workshop_id == $object->id)) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
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

    /**
     * Determine if the user is a collegist (including alumni).
     * @return boolean
     */
    public function isCollegist($alumni = true): bool
    {
        if($alumni){
            return $this->hasRole([Role::COLLEGIST, Role::ALUMNI]);
        } else {
            return $this->hasRole(Role::COLLEGIST);
        }
    }

    /**
     * Determine if the user has role that is associated with the student council
     * Please note that CCT handlers, committee members are also associated with the
     * student council. Therefore using this function is not recommended.
     * It is only implemented to signal a warning.
     * Checkout isStudentCouncilOfficial if you want to check for elected members
     * of the student council.
     * 
     * @return boolean
     */
    public function isStudentCouncil(bool $force): bool
    {
        if(!$force){
            throw new Exception("isStudentCouncil is not recommended to use. Please check the source code for further information.");
        }
        return $this->hasRole(Role::STUDENT_COUNCIL);
    }

    /*
    Is the user the secretary or the director
    */
    public function isCollegeLeader(): bool
    {
        return $this->isDirector() || $this->isSecretary();
    }
    /*
    Is the user the secretary, the director or a staff
    */
    public function isCollegeMaintainer(): bool
    {
        return $this->isCollegeLeader() || $this->isStaff();
    }

    /*
    Are they an official of the student council?
    */
    public function isStudentCouncilOfficial(): bool {
        return $this->hasRole([
            Role::STUDENT_COUNCIL => array_merge(Role::STUDENT_COUNCIL_LEADERS, Role::COMMITTEE_LEADERS)
        ]);
    }

    public function isStudentCouncilLeader(): bool {
        return $this->hasRole([
            Role::STUDENT_COUNCIL => Role::STUDENT_COUNCIL_LEADERS
        ]);
    }

    /*
    Simple role checkers
    */
    public function isAdmin(): bool {return $this->hasRole(Role::SYS_ADMIN);}
    //isCollegist() implemented above
    public function isTenant(): bool {return $this->hasRole(Role::TENANT);}
    public function isWorkshopAdministrator(): bool {return $this->hasRole(Role::WORKSHOP_ADMINISTRATOR);}
    public function isWorkshopLeader(): bool { return $this->hasRole(Role::WORKSHOP_LEADER); }
    public function isApplicationCommitteeMember(): bool {return $this->hasRole(Role::APPLICATION_COMMITTEE_MEMBER);}
    public function isAggregatedApplicationCommitteeMember(): bool {return $this->hasRole(Role::AGGREGATED_APPLICATION_COMMITTEE_MEMBER);}
    public function isSecretary(): bool {return $this->hasRole(Role::SECRETARY);}
    public function isDirector(): bool { return $this->hasRole(Role::DIRECTOR);}
    public function isStaff(): bool {return $this->hasRole(Role::STAFF);}
    //isStudentCouncil() implemented above
    public function isStudentCouncilSecretary(): bool {return $this->hasRole(Role::STUDENT_COUNCIL_SECRETARY);}
    public function isBoardOfTrusteeMember(): bool {return $this->hasRole(Role::BOARD_OF_TRUSTEES_MEMBER);}
    public function isEthicsCommissioner(): bool {return $this->hasRole(Role::ETHICS_COMMISSIONER);}
    public function isAlumni(): bool {return $this->hasRole(Role::ALUMNI);}
    public function isReceptionist(): bool {return $this->hasRole(Role::RECEPTIONIST);}
    public function isSenior(): bool {return $this->hasRole(Role::SENIOR);}
    /*
    Simple student council role checkers
    */
    public function isPresident(): bool {return $this->hasRole([Role::STUDENT_COUNCIL => Role::PRESIDENT]);}
    public function isEconomicVicePresident(): bool {return $this->hasRole([Role::STUDENT_COUNCIL => Role::ECONOMIC_VICE_PRESIDENT]);}
    public function isScienceVicePresident(): bool {return $this->hasRole([Role::STUDENT_COUNCIL => Role::SCIENCE_VICE_PRESIDENT]);}
    public function isCulturalLeader(): bool {return $this->hasRole([Role::STUDENT_COUNCIL => Role::CULTURAL_LEADER]);}
    public function isCulturalReferent(): bool {return $this->hasRole([Role::STUDENT_COUNCIL => Role::CULTURAL_REFERENT]);}
    public function isCulturalMember(): bool {return $this->hasRole([Role::STUDENT_COUNCIL => Role::CULTURAL_MEMBER]);}
    public function isKKTHandler(): bool {return $this->hasRole([Role::STUDENT_COUNCIL => Role::KKT_HANDLER]);}
    public function isCommunityLeader(): bool {return $this->hasRole([Role::STUDENT_COUNCIL => Role::COMMUNITY_LEADER]);}
    public function isCommunityReferent(): bool {return $this->hasRole([Role::STUDENT_COUNCIL => Role::COMMUNITY_REFERENT]);}
    public function isCommunityMember(): bool {return $this->hasRole([Role::STUDENT_COUNCIL => Role::COMMUNITY_MEMBER]);}
    public function isCommunicationLeader(): bool {return $this->hasRole([Role::STUDENT_COUNCIL => Role::COMMUNICATION_LEADER]);}
    public function isCommunicationReferent(): bool {return $this->hasRole([Role::STUDENT_COUNCIL => Role::COMMUNICATION_REFERENT]);}
    public function isCommunicationMember(): bool {return $this->hasRole([Role::STUDENT_COUNCIL => Role::COMMUNICATION_MEMBER]);}
    public function isSportLeader(): bool {return $this->hasRole([Role::STUDENT_COUNCIL => Role::SPORT_LEADER]);}
    public function isSportReferent(): bool {return $this->hasRole([Role::STUDENT_COUNCIL => Role::SPORT_REFERENT]);}
    public function isSportMember(): bool {return $this->hasRole([Role::STUDENT_COUNCIL => Role::SPORT_MEMBER]);}
    /*
    Simple collegist residential status checkers
    */
    public function isResident(): bool {return $this->hasRole([Role::COLLEGIST => Role::RESIDENT]);}
    public function isExtern(): bool {return $this->hasRole([Role::COLLEGIST => Role::EXTERN]);}

    
}
