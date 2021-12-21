<?php

namespace App\Modules\Person\Actions;

use Illuminate\Support\Carbon;
use App\Modules\Person\Models\Invite;
use Lorisleiva\Actions\Concerns\AsController;

class InviteRedeemForExistingUser
{
    use AsController;
    
    public function handle(Invite $invite)
    {
        $invite->markRedeemed(Carbon::now())->save();

        return $invite;
    }

    public function asController($code)
    {
        $invite = Invite::findByCodeOrFail($code);

        if ($invite->hasBeenRedeemed()) {
            return $invite;
        }

        return $this->handle($invite);
    }
}
