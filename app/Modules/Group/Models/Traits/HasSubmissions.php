<?php

namespace App\Modules\Group\Models\Traits;

use App\Modules\Group\Models\Submission;
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

    public function latestSubmission():HasOne
    {
        return $this->hasOne(Submission::class)
            ->ofMany(['created_at' => 'max']);
    }
    
    public function latestPendingSubmission():HasOne
    {
        return $this->hasOne(Submission::class)
                ->ofMany(['created_at'=>'max'], function ($query) {
                    $query->pending();
                });
    }

    public function approvedSubmission(): HasOne
    {
        return $this->hasOne(Submission::class)
            ->ofMany(['created_at' => 'max'], function ($query) {
                $query->where('submission_status_id', config('submissions.statuses.approved.id'));
            });
    }

    public function hasApprovedSubmissionFor(?int $submissionTypeId = null):bool
    {   
        $q = $this->submissions()->where('submission_status_id', config('submissions.statuses.approved.id'));

        if ($submissionTypeId !== null) {
            $q->where('submission_type_id', $submissionTypeId);
        }

        return $q->exists();
    }
}
