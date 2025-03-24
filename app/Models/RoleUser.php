<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

use Illuminate\Support\Facades\Log;

/**
 * RoleUser pivot model. Represents a role assigned to a user with a roleObject or Workshop in the pivot.
 *
 * @property Role $role
 * @property RoleObject $object
 * @property Workshop $workshop
 * @property User $user
 * @property string $translatedName of the roleObject or workshop
 * @property integer|null $object_id
 * @property integer|null $workshop_id
 * @property int $user_id
 * @property int $role_id
 * @property-read string $translated_name
 * @method static \Illuminate\Database\Eloquent\Builder|RoleUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RoleUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RoleUser query()
 * @method static \Illuminate\Database\Eloquent\Builder|RoleUser whereObjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RoleUser whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RoleUser whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RoleUser whereWorkshopId($value)
 * @mixin \Eloquent
 */
class RoleUser extends Pivot
{
    protected $table = 'role_users';

    protected $fillable = ['workshop_id', 'object_id', 'user_id', 'role_id'];

    /**
     * Always eager load workshop and object relations.
     */
    protected $with = ['workshop', 'object'];

    /**
     * Get the belonging workshop.
     */
    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    /**
     * Get the belonging RoleObject.
     */
    public function object(): BelongsTo
    {
        return $this->belongsTo(RoleObject::class);
    }

    /**
     * Get the belonging user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the belonging role.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    private static function getTranslatedName($object_id, $workshop_id) : string {
        $getLambda1 = function() use ($object_id){
            Log::debug($object_id);
            Log::debug(gettype($object_id));
            return RoleObject::find($object_id)->translatedName;
        };
        $getLambda2 = function() use ($workshop_id){
            Log::debug($workshop_id);
            Log::debug(gettype($workshop_id));
            return Workshop::find($workshop_id)->name;
        };
        if($object_id){
            return once($getLambda1);
        }
        if($workshop_id){
            return once($getLambda2);
        }
        return '';
    }

    /**
     * Get the role object's translated_name attribute.
     *
     * @return Attribute
     */
    public function translatedName(): Attribute
    {
        $translatedName = RoleUser::getTranslatedName($this->object_id, $this->workshop_id);
        return Attribute::make(
            get: function () use ($translatedName): string {
                return $translatedName;
            }
        );
    }
}
