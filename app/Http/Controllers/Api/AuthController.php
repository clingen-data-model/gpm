<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use App\Http\Requests\SendPasswordResetRequest;

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

    public function isAuthenticated()
    {
        return Auth::check() ? response(null, 200) : response(null, 401);
    }
    
    
}
