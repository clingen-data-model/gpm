<?php

namespace App\Modules\Group\Models\Traits;

use App\Models\Submission;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Adds methods related to Submission models.
 */
trait HasSubmissions
{
    public function submissions():HasMany
    {
        return $this->hasMany(Submission::class);
    }
    
    public function latestPendingSubmission():HasOne
    {
        return $this->hasOne(Submission::class)
                ->ofMany(['created_at'=>'max'], function ($query) {
                    $query->where('submission_status_id', config('submissions.statuses.pending.id'));
                });
    }

    public function approvedSubmission(): HasOne
    {
        return $this->hasOne(Submission::class)
            ->ofMany(['created_at' => 'max'], function ($query) {
                $query->where('submission_status_id', config('submissions.statuses.approved.id'));
            });
    }

    public function hasApprovedSubmissionFor():bool
    {   
        return (bool)$this->approvedSubmission;
    }
}
