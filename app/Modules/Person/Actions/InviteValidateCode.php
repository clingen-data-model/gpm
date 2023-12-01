<?php

namespace App\Modules\Person\Actions;

use App\Modules\Person\Http\Resources\InviteResource;
use App\Modules\Person\Models\Invite;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsObject;

class InviteValidateCode
{
    use AsObject;
    use AsController;

    public function handle($code)
    {
        $invite = Invite::findByCodeOrFail($code);
        if ($invite) {
            // if ($invite->hasBeenRedeemed()) {
            //     throw ValidationException::withMessages(['code' => ['It looks like this invite has already been redeemed. Please log in to access your account, update your profile and complete any COI disclosures.']]);
            // }
            $invite->load(['person', 'person.user', 'person.credentials', 'person.expertises', 'inviter']);

            return new InviteResource($invite);
        }

        return response(['code' => ['The invite code is not valid.']], 404);
    }
}
