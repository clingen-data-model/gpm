<?php

namespace App\Modules\Funding\Policies;

use App\Modules\Funding\Models\FundingSource;
use App\Modules\User\Models\User;

class FundingSourcePolicy
{
    protected function isSuper(User $user): bool
    {
        return $user->hasAnyRole(['super-user', 'super-admin']);
    }

    public function viewAny(User $user): bool
    {
        return $this->isSuper($user);
    }

    public function view(User $user, FundingSource $fundingSource): bool
    {
        return $this->isSuper($user);
    }

    public function create(User $user): bool
    {
        return $this->isSuper($user);
    }

    public function update(User $user, FundingSource $fundingSource): bool
    {
        return $this->isSuper($user);
    }

    public function delete(User $user, FundingSource $fundingSource): bool
    {
        return $this->isSuper($user);
    }
}
