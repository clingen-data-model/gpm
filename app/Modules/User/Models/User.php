<?php

namespace App\Modules\User\Models;

use App\Models\Traits\HasEmail;
use Laravel\Sanctum\HasApiTokens;
use App\Modules\Group\Models\Group;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Auth;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Contracts\HasLogEntries;
use App\Modules\User\Models\Preference;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\CanResetPassword;
use Lab404\Impersonate\Services\ImpersonateManager;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Traits\HasLogEntries as HasLogEntriesTrait;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements CanResetPassword, HasLogEntries
{
    use HasFactory, Notifiable, CanResetPasswordTrait, HasApiTokens, HasEmail, HasRoles;
    use Impersonate;
    use HasLogEntriesTrait;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the person associated with the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function person()
    {
        return $this->hasOne(Person::class);
    }

    public function preferences()
    {
        return $this->belongsToMany(Preference::class, 'user_preference');
    }
    
    public function routeNotificationForSlack()
    {
        return config('logging.channels.slack.url');
    }

    public function hasPermissionTo($permString)
    {
        return $this->getAllPermissions()->contains('name', $permString);
    }

    public function getAllPermissions()
    {
        if (is_null($this->allPermissions)) {
            $this->allPermissions = Cache::remember('user-'.$this->id.'-allPermissions', 60 * 20, function () {
                $permissions = $this->permissions;

                if ($this->roles) {
                    $permissions = $permissions->merge($this->getPermissionsViaRoles());
                }

                return $permissions->sort()->values();
            });
        }

        return $this->allPermissions;
    }

    public function hasGroupPermissionTo($permission, Group $group): bool
    {
        return $this->person && $this->person->hasGroupPermissionTo($permission, $group);
    }
    

    /**
     * DOMAIN
     */

    public function isLinkedToPerson(): bool
    {
        return (bool)$this->person;
    }

    /**
     * IMPERSONATE
     */

    public function canImpersonate()
    {
        return $this->hasAnyRole('super-user', 'super-admin', 'admin');
    }

    public function canBeImpersonated()
    {
        // No one can impersonate a super-user
        if ($this->hasRole('super-user')) {
            return false;
        }

        // Super-user can impersonate anyone (except another super-user)
        if (Auth::user()->hasRole('super-user')) {
            return true;
        }

        // Admin can impersonate anyone except super-user, super-admin, or another admin
        if (Auth::user()->hasRole('admin') && $this->hasAnyRole(['super-user', 'super-admin', 'admin'])) {
            return false;
        }
        if (Auth::user()->roles->intersect($this->roles)->count() > 0) {
            return false;
        }

        return true;
    }

    public function getImpersonatedByAttribute()
    {
        if ($this->isImpersonated()) {
            return app(ImpersonateManager::class)->getImpersonator();
        }

        return null;
    }

    public function getIsImpersonatingAttribute()
    {
        return app(ImpersonateManager::class)->isImpersonating();
    }

    
    protected function getDefaultGuardName(): string { return 'web'; }


    // Factory support
    protected static function newFactory()
    {
        return new UserFactory();
    }
}
