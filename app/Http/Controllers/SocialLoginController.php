<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SocialLoginController extends Controller
{
    public function handleRedirect($provider): RedirectResponse {
        return Socialite::driver($provider)->setScopes(['/authenticate', 'openid'])->redirect();
    }

    public function handleCallback($provider): RedirectResponse {
        $user = Socialite::driver($provider)->user();
        Log::info("Socialite user is " . $user->getId());
        Auth::login($user);
        return redirect("/");
    }
}
