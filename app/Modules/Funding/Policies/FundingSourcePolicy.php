<?php

namespace App\Modules\Funding\Policies;

use App\Modules\Funding\Models\FundingSource;
use App\Modules\User\Models\User;

class FundingSourcePolicy
{
    protected function isEligibleUser(User $user): bool
    {
        return $user->hasAnyRole(['super-user', 'super-admin']) || $user->hasAnyPermission(['funding-sources-manage']);
    }

    public function viewAny(User $user): bool
    {
        return $this->isEligebleUser($user);
    }

    public function view(User $user, FundingSource $fundingSource): bool
    {
        return $this->isEligebleUser($user);
    }

    public function create(User $user): bool
    {
        return $this->isEligebleUser($user);
    }

    public function update(User $user, FundingSource $fundingSource): bool
    {
        return $this->isEligebleUser($user);
    }

    public function delete(User $user, FundingSource $fundingSource): bool
    {
        return $this->isEligebleUser($user);
    }
}
