<?php

namespace App\Modules\Person\Actions;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Modules\Person\Models\Invite;
use App\Providers\IdpServiceProvider;
use App\Services\Idp\Contracts\IdpClient;
use Lorisleiva\Actions\Concerns\AsController;
use App\Services\Idp\Contracts\TokenVerifier;
use App\Services\Idp\Exceptions\IdpException;
use Illuminate\Validation\ValidationException;
use App\Modules\User\Actions\UserCreateFromIdpIdentity;

/**
 * Redeem an invitation with an existing ClinGen (IdP) account instead of a
 * new password: verify the IdP session token, link a GPM user to that
 * identity for the invited person and start a normal session.
 *
 * Lives beside IdpSessionLogin in the web group so the session can be
 * established in the same request.
 */
class InviteRedeemWithIdp
{
    use AsController;

    public function __construct(
        private TokenVerifier $verifier,
        private IdpClient $client,
        private UserCreateFromIdpIdentity $createUser,
    ) {
    }

    public function asController(Request $request, string $code): JsonResponse
    {
        abort_unless(IdpServiceProvider::enabled(), 404);

        $invite = Invite::findByCodeOrFail($code);
        if ($invite->hasBeenRedeemed()) {
            throw ValidationException::withMessages(['code' => ['This invite has already been redeemed. Please log in to access your account.']]);
        }

        $token = $request->bearerToken();
        $identity = $token ? $this->verifier->verify($token) : null;
        if (! $identity) {
            return response()->json(['message' => 'A valid identity provider session is required.'], 401);
        }

        try {
            $idpUser = $this->client->getUser($identity->subject);
        } catch (IdpException $e) {
            return response()->json(['message' => 'The identity provider could not be reached. Nothing was changed; please try again.'], 503);
        }
        if (! $idpUser) {
            return response()->json(['message' => 'Your ClinGen account could not be found at the identity provider.'], 401);
        }

        $person = $invite->person;
        if ($person->user_id) {
            throw ValidationException::withMessages(['code' => ['This invite has already been activated. Please sign in to access your account.']]);
        }
        if (User::linkedToIdp($identity->subject, $identity->provider)->exists()) {
            throw ValidationException::withMessages(['email' => [
                'This ClinGen account already belongs to a different GPM user. Sign in with that account, or ask your coordinator to add that person or merge the records.',
            ]]);
        }

        $user = $this->createUser->handle($person, $idpUser);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return response()->json(['user_id' => $user->id]);
    }
}
