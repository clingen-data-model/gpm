<?php

namespace App\Actions\Auth;

use App\Auth\CILogon\CILogonIdpDirectory;
use App\Modules\Person\Models\Invite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Lorisleiva\Actions\Concerns\AsController;

class CILogonRedirect
{
    use AsController;

    public function __construct(private CILogonIdpDirectory $directory)
    {
    }

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

        $driver = Socialite::driver('cilogon');

        // An unknown or stale idp falls back to CILogon's own picker rather than failing.
        $idp = $request->query('idp');
        if (is_string($idp) && $idp !== '' && $this->directory->isKnown($idp)) {
            $driver = $driver->with(['idphint' => $idp]);
        }

        return $driver->redirect();
    }
}
