<?php

namespace App\Modules\User\Models;

use App\Models\HasEmail;
use Laravel\Sanctum\HasApiTokens;
use Database\Factories\UserFactory;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Traits\HasRoles;
use App\Modules\User\Models\Preference;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;

class User extends Authenticatable implements CanResetPassword
{
    use HasFactory, Notifiable, CanResetPasswordTrait, HasApiTokens, HasEmail, HasRoles;

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

    // Factory support
    protected static function newFactory()
    {
        return new UserFactory();
    }
}
