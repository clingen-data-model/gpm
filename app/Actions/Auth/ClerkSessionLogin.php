<?php

namespace App\Actions\Auth;

use App\Modules\Person\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsController;

class ClerkSessionLogin
{
    use AsController;

    public function asController(Request $request)
    {
        $clerkUserId = $request->attributes->get('clerk_user_id');

        if (!$clerkUserId) {
            return response()->json([
                'message' => 'Missing Clerk user ID.',
            ], 401);
        }

        $person = Person::with('user')
            ->where('clerk_user_id', $clerkUserId)
            ->first();

        if (!$person) {
            return response()->json([
                'message' => 'This Clerk account is not linked to a GPM person.',
            ], 403);
        }

        if (!$person->user) {
            return response()->json([
                'message' => 'This linked person does not have a GPM user account yet.',
            ], 409);
        }

        Auth::guard('web')->login($person->user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Logged in to GPM successfully.',
            'user_id' => $person->user->id,
            'person_id' => $person->id,
            'person_uuid' => $person->uuid,
        ]);
    }
}