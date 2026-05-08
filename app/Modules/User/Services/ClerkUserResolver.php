<?php

namespace App\Modules\User\Services;

use App\Modules\User\Models\User;

class ClerkUserResolver
{
    /**
     * Find the local active user for the given Clerk user ID.
     *
     * Returns null if no local record exists or the user is inactive.
     * The caller is responsible for distinguishing these two cases if needed;
     * the middleware maps both to a 403 (authenticated to Clerk, not authorized here).
     */
    public function resolve(string $clerkUserId): ?User
    {
        return User::where('clerk_user_id', $clerkUserId)
            ->where('active', true)
            ->first();
    }
}
