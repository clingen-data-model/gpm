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
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['groups-manage', 'ep-applications-manage']);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Document $document): bool
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
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Document $document): bool
    {
        return $this->canEdit($user, $document);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Document $document): bool
    {
        return $this->canEdit($user, $document);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Document $document): bool
    {
        return $this->canEdit($user, $document);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Document $document): bool
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
