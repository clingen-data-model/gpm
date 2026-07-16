<?php

namespace App\Actions\Auth;

use Illuminate\Http\Request;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsController;

/**
 * Exchanges a verified Clerk session token for a normal Laravel session.
 *
 * This is the one place a Clerk token is verified per sign-in rather than per
 * request — everything after this establishes identity via the `web` session
 * guard, same as the pre-Clerk Fortify login did.
 */
class ClerkSessionLogin
{
    use AsController;

    public function asController(Request $request)
    {
        $clerkId = $request->attributes->get('clerk_user_id');
        $claims = $request->attributes->get('clerk_claims', []);

        $user = $this->resolveFromClerkClaims($clerkId, $claims);

        if (! $user) {
            return response()->json([
                'message' => 'This Clerk account is not linked to a GPM user yet.',
            ], 403);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Logged in to GPM successfully.',
            'user_id' => $user->id,
        ]);
    }

    /**
     * Map verified Clerk claims to a local User, linking on first sight.
     *
     * Mirrors the resolution the stateless-bearer-token design performs on
     * every request; here it only needs to run once, at login.
     */
    private function resolveFromClerkClaims(?string $clerkId, array $claims): ?User
    {
        if (! $clerkId) {
            return null;
        }

        if ($user = User::where('clerk_id', $clerkId)->first()) {
            return $user;
        }

        $email = $claims['email'] ?? $this->lookupEmailFromClerk($clerkId);

        if (! $email) {
            return null;
        }

        $user = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();

        if ($user) {
            // Lazy-link: bind this local account to its Clerk identity the
            // first time it authenticates (covers stragglers / accounts
            // created during the transition window).
            $user->forceFill(['clerk_id' => $clerkId])->save();
        }

        return $user;
    }

    private function lookupEmailFromClerk(string $clerkId): ?string
    {
        $secret = config('clerk.secret_key');

        if (! $secret) {
            return null;
        }

        try {
            $response = Http::withToken($secret)
                ->acceptJson()
                ->get(rtrim((string) config('clerk.api_url'), '/').'/users/'.$clerkId);

            if (! $response->successful()) {
                return null;
            }

            $clerkUser = $response->json();
            $primaryId = $clerkUser['primary_email_address_id'] ?? null;

            foreach ($clerkUser['email_addresses'] ?? [] as $address) {
                if (($address['id'] ?? null) === $primaryId) {
                    return $address['email_address'] ?? null;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Clerk email lookup failed', ['clerk_id' => $clerkId, 'error' => $e->getMessage()]);
        }

        return null;
    }
}
