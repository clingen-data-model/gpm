<?php

namespace App\Policies;

use App\Models\Document;
use App\Modules\Group\Models\Group;
use App\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentPolicy
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
        return $user->hasAnyPermission(['groups-manage', 'ep-applications-manage']);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Models\Document  $document
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Document $document)
    {
        if ($this->viewAny($user)) {
            return true;
        }

        if ($document->belongsToUser($user)) {
            return true;
        }

        if (get_class($document->owner) == Group::class && $user->person->inGroup($document->owner)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     *
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Models\Document  $document
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Document $document)
    {
        return $this->canEdit($user, $document);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Models\Document  $document
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Document $document)
    {
        return $this->canEdit($user, $document);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Models\Document  $document
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Document $document)
    {
        return $this->canEdit($user, $document);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Models\Document  $document
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Document $document)
    {
        return $this->canEdit($user, $document);
    }

    private function canEdit(User $user, Document $document): bool
    {
        if ($this->viewAny($user)) {
            return true;
        }

        if ($document->belongsToUser($user)) {
            return true;
        }

        if (
            $document->owner &&
            get_class($document->owner) == Group::class
            && $document
                ->owner
                ->coordinators
                ->pluck('person_id')
                ->contains($user->person->id)
        ) {
            return true;
        }
    }
}
