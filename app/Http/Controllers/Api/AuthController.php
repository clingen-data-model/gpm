<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use App\Http\Requests\SendPasswordResetRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;

class AuthController extends Controller
{
    public function sendResetPasswordLink(SendPasswordResetRequest $request)
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
                    ? response(['status' => __($status)], 200)
                    : response(
                        [
                            'message' => 'There was a problem with your submission.',
                            'errors' => [
                                'email' => [__($status)]
                            ]
                        ], 422);    
    }

    public function resetPassword(ResetPasswordRequest $request) 
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use ($request) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->setRememberToken(Str::random(60));
                event(new PasswordReset($user));
            }
        );
        
        return $status == Password::PASSWORD_RESET
                ? response(['status' => __($status)], 200)
                : response([
                        'errors' => [
                            'email' => [__($status)]
                        ]
                    ], 200);
    }

    public function isAuthenticated()
    {
        return Auth::check() ? response(null, 200) : response(null, 401);
    }
    
    
}
