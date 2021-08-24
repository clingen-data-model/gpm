<?php
namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 *
 */
trait RecordsEvents
{
    public function events(): MorphMany
    {
        return $this->morphMany(Event::class);
    }

    /**
     * Get the latest event for model
     * @return \Illumninate\Database\Eloquent\Relations\MorphMany
     */
    public function latestEvent(): MorphMany
    {
        return $this->morphMany(Event::class)->latestOfMany();
    }
}
