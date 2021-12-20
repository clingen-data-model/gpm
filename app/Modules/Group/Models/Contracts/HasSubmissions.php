<?php

namespace App\Modules\Group\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

interface HasSubmissions
{
    public function submissions():HasMany;
    public function latestPendingSubmission():HasOne;
    public function approvedSubmission():HasOne;
    public function hasApprovedSubmissionFor():bool;
}