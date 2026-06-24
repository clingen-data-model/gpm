<?php
namespace App\Modules\Person\Actions;

use Carbon\Carbon;
use App\Modules\User\Models\User;
use App\Modules\Person\Models\Invite;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\ActionRequest;
use App\Services\Clerk\ClerkApiClient;
use App\Modules\User\Actions\UserCreate;
use App\Services\Clerk\ClerkTokenVerifier;
use App\Modules\Person\Events\InviteRedeemed;
use Lorisleiva\Actions\Concerns\AsController;

/**
 * Redeem an invite for a user who has just created (or already has) a Clerk
 * identity. Authentication is established by Clerk during sign-up; this links
 * that identity to the invited person, creating the local user record (without
 * a usable local password) when one does not already exist.
 *
 * The route is public because a brand-new Clerk user has no local account yet,
 * so the Clerk guard cannot resolve them. The Clerk session token is verified
 * directly here instead.
 */
class InviteRedeem
{
    use AsController;

    public function __construct(
        private UserCreate $createUser,
        private ClerkTokenVerifier $verifier,
        private ClerkApiClient $clerk,
    ) {
    }

    public function handle(Invite $invite, string $clerkId, string $email): Invite
    {
        $invite->markRedeemed(Carbon::now())->save();

        $user = User::where('clerk_id', $clerkId)->first()
            ?? User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();

        if ($user) {
            if ($user->clerk_id !== $clerkId) {
                $user->forceFill(['clerk_id' => $clerkId])->save();
            }
        } else {
            $user = $this->createUser->handle(
                name: $invite->person->first_name.' '.$invite->person->last_name,
                email: $email,
                clerkId: $clerkId,
            );
        }

        $invite->person
            ->user()
            ->associate($user)
            ->save();

        Event::dispatch(new InviteRedeemed($invite, $user));

        return $invite;
    }

    public function asController(ActionRequest $request, $code)
    {
        $invite = Invite::findByCodeOrFail($code);

        $token = $request->bearerToken();
        $claims = $token ? $this->verifier->verify($token) : null;

        if (! $claims || empty($claims['sub'])) {
            abort(401, 'A valid Clerk session is required to redeem an invite.');
        }

        return $this->handle(
            $invite,
            $claims['sub'],
            $this->resolveEmail($claims, $invite),
        );
    }

    private function resolveEmail(array $claims, Invite $invite): string
    {
        if (! empty($claims['email'])) {
            return $claims['email'];
        }

        $clerkUser = $this->clerk->getUser($claims['sub']);
        $email = $clerkUser ? ClerkApiClient::primaryEmail($clerkUser) : null;

        return $email ?: $invite->person->email;
    }
}
