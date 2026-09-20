<?php

namespace App\Actions\Auth;

use Throwable;
use Illuminate\Http\Request;
use App\Services\Idp\IdpUser;
use Illuminate\Http\JsonResponse;
use App\Services\Idp\IdpIdentity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Providers\IdpServiceProvider;
use App\Services\Idp\Contracts\IdpClient;
use Lorisleiva\Actions\Concerns\AsController;
use App\Services\Idp\Contracts\TokenVerifier;
use App\Modules\User\Actions\UserIdpProfileSync;
use App\Modules\User\Actions\UserFindByIdpIdentity;

/**
 * Exchange a verified IdP session token for a normal Laravel session.
 *
 * This is the one place an IdP token is verified: once per sign-in rather
 * than per request. Everything afterwards authenticates through the `web`
 * session guard exactly as a local password login does.
 */
class IdpSessionLogin
{
    use AsController;

    public function __construct(
        private TokenVerifier $verifier,
        private IdpClient $client,
        private UserFindByIdpIdentity $findUser,
        private UserIdpProfileSync $syncProfile,
    ) {
    }

    public function asController(Request $request): JsonResponse
    {
        if (! IdpServiceProvider::enabled()) {
            abort(404);
        }

        $token = $request->bearerToken();
        $identity = $token ? $this->verifier->verify($token) : null;

        if (! $identity) {
            return response()->json(['message' => 'A valid identity provider session is required.'], 401);
        }

        $idpUser = $this->fetchIdpUser($identity);
        $user = $this->findUser->handle($identity, $idpUser);

        if (! $user) {
            return response()->json([
                'message' => 'This account is not linked to a GPM user. Ask a coordinator for an invitation.',
            ], 403);
        }

        if ($idpUser && config('idp.sync_profile_on_login', true)) {
            $this->syncProfile->handle($user, $idpUser);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return response()->json(['user_id' => $user->id]);
    }

    /**
     * The token only carries the subject; the directory record supplies
     * external_id, email and name. An unreachable IdP must not block an
     * already-linked user from signing in, so failures degrade to null.
     */
    private function fetchIdpUser(IdpIdentity $identity): ?IdpUser
    {
        try {
            return $this->client->getUser($identity->subject);
        } catch (Throwable $e) {
            Log::warning('Could not fetch IdP user record during session login.', [
                'subject' => $identity->subject,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
