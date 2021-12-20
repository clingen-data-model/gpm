<?php

namespace App\Modules\Person\Policies;

use App\Modules\User\Models\User;
use App\Modules\Person\Models\Person;
use Illuminate\Auth\Access\HandlesAuthorization;

class PersonPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Models\Person  $person
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Person $person)
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->hasPermissionTo('person-create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Models\Person  $person
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Person $person)
    {
        if ($user->hasPermissionTo('people-manage')) {
            return true;
        }

        if ($this->userIsPerson($user, $person)) {
            return true;
        }

        // if ($user->person && $user->person->hasGroupPermissionTo('update-member-profiles', $person->groups)) {
        //     return true;
        // }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Models\Person  $person
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Person $person)
    {
        return $user->hasPermissionTo('person-delete');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Models\Person  $person
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Person $person)
    {
        return $user->hasPermissionTo('person-restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Models\Person  $person
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Person $person)
    {
        return $user->hasPermissionTo('force-person-delete');
    }

    private function userIsPerson(User $user, Person $person)
    {
        return $person->user_id === $user->id;
    }
}
