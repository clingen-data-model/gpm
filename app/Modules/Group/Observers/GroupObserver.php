<?php

namespace App\Observers;

use App\Models\Group;

class GroupObserver
{
    /**
     * Handle the Group "created" event.
     */
    public function created(Group $group): void
    {
        //
    }

    /**
     * Handle the Group "updated" event.
     */
    public function updated(Group $group): void
    {
        //
    }

    /**
     * Handle the Group "deleted" event.
     */
    public function deleted(Group $group): void
    {
        //
    }

    /**
     * Handle the Group "restored" event.
     */
    public function restored(Group $group): void
    {
        //
    }

    /**
     * Handle the Group "force deleted" event.
     */
    public function forceDeleted(Group $group): void
    {
        //
    }
}
