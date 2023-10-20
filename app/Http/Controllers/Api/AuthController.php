<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\SendPasswordResetRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

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
                                'email' => [__($status)],
                            ],
                        ], 422);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->setRememberToken(Str::random(60));
                event(new PasswordReset($user));
            }
        );

        return $status == Password::PASSWORD_RESET
                ? response(['status' => __($status)], 200)
                : response([
                    'errors' => [
                        'email' => [__($status)],
                    ],
                ], 200);
    }

    public function isAuthenticated(Request $request)
    {
        return $request->user() ? response(null, 200) : response(null, 401);
    }
}
