<?php

namespace App\Actions\Auth;

use App\Modules\Person\Models\Invite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Lorisleiva\Actions\Concerns\AsController;

class CILogonRedirect
{
    use AsController;

    public function asController(Request $request)
    {
        abort_unless(config('services.cilogon.client_id'), 404);

        $request->session()->forget(['cilogon.invite', 'cilogon.link']);

        if ($code = $request->query('invite')) {
            $invite = Invite::findByCode($code);
            if (! $invite || $invite->hasBeenRedeemed()) {
                return CILogonCallback::reject('invite_invalid');
            }
            $request->session()->put('cilogon.invite', $invite->code);
        }

        if ($request->boolean('link')) {
            if (! Auth::guard('web')->check()) {
                return CILogonCallback::reject('not_signed_in');
            }
            $request->session()->put('cilogon.link', true);
        }

        return Socialite::driver('cilogon')->redirect();
    }
}
