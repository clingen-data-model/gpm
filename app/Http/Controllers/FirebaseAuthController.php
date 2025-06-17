<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Auth as FirebaseAuth;
use Illuminate\Support\Facades\Auth;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;

class FirebaseAuthController extends Controller
{
    protected FirebaseAuth $firebaseAuth;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount(storage_path(env('FIREBASE_CREDENTIALS')));
        Log::warning(storage_path(env('FIREBASE_CREDENTIALS')));
        $this->firebaseAuth = $factory->createAuth();
    }

    public function handle(Request $request)
    {
        $idToken = $request->input('token');

        try {
            $verifiedIdToken = $this->firebaseAuth->verifyIdToken($idToken);
            $firebaseUserId = $verifiedIdToken->claims()->get('sub');
            $firebaseUser = $this->firebaseAuth->getUser($firebaseUserId);
            $email = $firebaseUser->email;

            // Try to find or create user
            $user = User::firstOrCreate(
                ['email' => $email],
                // ['name' => $firebaseUser->displayName ?? 'Firebase User']
            );

            // Login the user
            Auth::login($user);

            Log::warning($firebaseUserId . ": email:" . $email . " displayName:". $firebaseUser->displayName);
            Log::warning($user);

            return response()->json([
                'message' => 'Login successful',
                'user' => $user,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Invalid token or login failed',
                'details' => $e->getMessage(),
            ], 401);
        }
    }
}