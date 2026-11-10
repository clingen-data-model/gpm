<?php

namespace App\Actions\Auth;

use App\Auth\CILogon\CILogonIdentityResolver;
use App\Auth\CILogon\CILogonLoginRejected;
use App\Modules\Person\Models\Invite;
use App\Modules\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Lorisleiva\Actions\Concerns\AsController;

class CILogonCallback
{
    use AsController;

    public function __construct(private CILogonIdentityResolver $resolver)
    {
    }

    public static function reject(string $reason, string $to = '/login')
    {
        return redirect(static::withParam($to, 'cilogon_error', $reason));
    }

    private static function withParam(string $url, string $key, string $value): string
    {
        return $url.(str_contains($url, '?') ? '&' : '?').$key.'='.urlencode($value);
    }

    // Linking starts from the person's sign-in methods tab, so results go back there.
    public static function linkReturnUrl(User $user): string
    {
        $uuid = $user->person?->uuid;

        return $uuid ? '/people/'.$uuid.'?tab='.rawurlencode('sign-in methods') : '/';
    }

    public function asController(Request $request)
    {
        abort_unless(config('services.cilogon.client_id'), 404);

        $inviteCode = $request->session()->pull('cilogon.invite');
        $linking = $request->session()->pull('cilogon.link', false);

        $linkTo = $linking ? Auth::guard('web')->user() : null;
        if ($linking && ! $linkTo) {
            return static::reject('not_signed_in');
        }
        $failTo = $linkTo ? static::linkReturnUrl($linkTo) : '/login';

        try {
            $identity = Socialite::driver('cilogon')->user();
        } catch (\Exception $e) {
            Log::warning('CILogon callback failed', ['error' => $e->getMessage(), 'oauth_error' => $request->query('error')]);

            return static::reject('cilogon_failed', $failTo);
        }

        $invite = null;
        if ($inviteCode) {
            $invite = Invite::findByCode($inviteCode);
            if (! $invite || $invite->hasBeenRedeemed()) {
                return static::reject('invite_invalid');
            }
        }

        try {
            $user = $this->resolver->resolve($identity, $invite, $linkTo);
        } catch (CILogonLoginRejected $e) {
            Log::info('CILogon login rejected', ['reason' => $e->reason, 'sub' => $identity->getId(), 'idp' => $identity->getRaw()['idp'] ?? null]);

            return static::reject($e->reason, $failTo);
        }

        if ($linkTo) {
            return redirect(static::withParam(static::linkReturnUrl($linkTo), 'cilogon_linked', '1'));
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect('/');
    }
}
