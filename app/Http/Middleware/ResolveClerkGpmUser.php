<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Modules\Person\Models\Person;

class ResolveClerkGpmUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $clerkUserId = $request->attributes->get('clerk_user_id');

        if (!$clerkUserId) {
            return response()->json(['message' => 'Missing Clerk identity.'], 401);
        }

        $person = Person::with('user')->where('clerk_user_id', $clerkUserId)->first();

        if (!$person || !$person->user) {
            return response()->json([
                'message' => 'Your Clerk account is not linked in GPM yet.',
            ], 403);
        }

        $request->attributes->set('person', $person);
        $request->setUserResolver(fn () => $person->user);

        return $next($request);
    }
}