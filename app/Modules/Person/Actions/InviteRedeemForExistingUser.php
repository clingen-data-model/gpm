<?php

namespace App\Modules\Person\Actions;

use Illuminate\Support\Carbon;
use App\Modules\User\Models\User;
use App\Modules\Person\Models\Invite;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

/**
 * Redeems an invite for a user who is already session-authenticated (e.g. an
 * existing GPM member invited to a new group). No Clerk token handling needed
 * here — the session guard has already resolved $request->user().
 */
class InviteRedeemForExistingUser
{
    use AsController;

    public function handle(Invite $invite, ?User $user = null)
    {
        $invite->markRedeemed(Carbon::now())->save();

        // Link the (already-authenticated) existing user to the invited person
        // when the person is not yet associated with an account.
        if ($user && ! $invite->person->user_id) {
            $invite->person->user()->associate($user)->save();
        }

        return $invite;
    }

    public function asController(ActionRequest $request, $code)
    {
        $invite = Invite::findByCodeOrFail($code);

        if ($invite->hasBeenRedeemed()) {
            return $invite;
        }

        return $this->handle($invite, $request->user());
    }
}
