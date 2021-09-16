<?php
namespace App\Modules\Person\Actions;

use App\Modules\Person\Models\Invite;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\Concerns\AsController;

class InviteValidateCode
{
    use AsObject;
    use AsController;

    public function handle($code)
    {
        $invite = Invite::findByCode($code);
        if ($invite) {
            if ($invite->hasBeenRedeemed()) {
                throw ValidationException::withMessages(['code' => ['This invite has already been redeemed.']]);
            }
            return response(['status' => 'code is valid'], 200);
        }

        return response(['code' => ['The invite code is not valid.']], 404);
    }
}
