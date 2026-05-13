<?php

namespace App\Actions\Auth;

use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;

class ClerkMe
{
    use AsController;

    public function asController(Request $request)
    {
        return response()->json([
            'message' => 'Clerk auth worked.',
            'clerk_user_id' => $request->attributes->get('clerk_user_id'),
            'clerk_auth' => $request->attributes->get('clerk_auth'),
        ]);
    }
}