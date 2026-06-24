<?php

namespace App\Modules\Person\Actions;

use Illuminate\Support\Carbon;
use App\Modules\User\Models\User;
use App\Modules\Person\Models\Invite;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class InviteRedeemForExistingUser
{
    use AsController;

    public function handle(Invite $invite, ?User $user = null)
    {
        $invite->markRedeemed(Carbon::now())->save();

        // Link the (Clerk-authenticated) existing user to the invited person
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
