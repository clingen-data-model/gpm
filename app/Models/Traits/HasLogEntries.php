<?php
namespace App\Models\Traits;

/**
 * Trait for models that have activity log entries
 */
trait HasLogEntries
{
    public function logEntries()
    {
        return $this->morphMany(config('activitylog.activity_model'), 'subject');
    }

    public function latestLogEntry()
    {
        return $this->morphOne(config('activitylog.activity_model'), 'subject')
                ->where('description', 'not like', 'Added next action:%')
                ->orderBy('created_at', 'desc');
    }
}
