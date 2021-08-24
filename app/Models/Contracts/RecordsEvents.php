<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface RecordsEvents
{
    /**
     * Get all of the event for the RecordsEvents
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function events(): MorphMany;

    /**
     * Get the latest event for model
     * @return \Illumninate\Database\Eloquent\Relations\MorphMany
     */
    public function latestEvent(): MorphMany;
}
