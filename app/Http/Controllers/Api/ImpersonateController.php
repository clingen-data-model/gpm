<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Resources\ImpersonatableUserResource;
use App\Services\Clerk\ImpersonationTokenService;

/**
 * Stateless impersonation.
 *
 * Clerk's built-in impersonation has very low usage limits, so impersonation
 * is handled here. An authorized admin requests an impersonation token for a
 * target user; the SPA then sends it as X-Impersonate-Token and the Clerk
 * guard resolves requests as the target user. "Leaving" is purely client-side
 * (the token is dropped); this endpoint exists for symmetry/auditing.
 */
class ImpersonateController extends Controller
{
    public function take(Request $request, ImpersonationTokenService $tokens, int $id)
    {
        $impersonator = Auth::user();

        if (! $impersonator->canImpersonate()) {
            abort(403, 'You are not allowed to impersonate users.');
        }

        $target = User::findOrFail($id);

        // canBeImpersonated() evaluates the rules against the current Auth::user()
        // (the impersonator), so this enforces the same role restrictions as the
        // legacy flow (e.g. no impersonating a super-user, no peer impersonation).
        if (! $target->canBeImpersonated()) {
            abort(403, 'This user cannot be impersonated.');
        }

        return response([
            'token' => $tokens->issue($impersonator->id, $target->id),
            'user' => new ImpersonatableUserResource($target),
        ]);
    }

    public function leave()
    {
        return response(null, 204);
    }
}
