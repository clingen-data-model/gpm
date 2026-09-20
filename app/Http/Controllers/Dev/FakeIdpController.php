<?php

namespace App\Http\Controllers\Dev;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Modules\User\Models\User;
use App\Http\Controllers\Controller;
use App\Services\Idp\Fake\FakeIdpStore;
use App\Services\Idp\Fake\FakeTokenIssuer;
use Illuminate\Validation\ValidationException;

/**
 * Stand-in for the identity provider's hosted sign-in, for local development
 * with IDP_DRIVER=fake. The SPA picks a user here, receives a fake session
 * token, and then performs the same exchange it would with a real IdP.
 */
class FakeIdpController extends Controller
{
    public function __construct(
        private FakeIdpStore $store,
        private FakeTokenIssuer $issuer,
    ) {
    }

    /**
     * Identities known to the fake IdP plus local users that could sign in
     * through it (a record is created for them on first token request).
     */
    public function users(): JsonResponse
    {
        $known = collect($this->store->all())->map(fn ($u) => [
            'id' => $u['id'],
            'email' => $u['email'] ?? null,
            'name' => trim(($u['first_name'] ?? '').' '.($u['last_name'] ?? '')) ?: null,
            'source' => 'idp',
        ]);

        $knownEmails = $known->pluck('email')->filter()->map(fn ($e) => mb_strtolower($e));

        $local = User::query()
            ->orderBy('name')
            ->limit(200)
            ->get()
            ->reject(fn (User $u) => $knownEmails->contains(mb_strtolower($u->email)))
            ->map(fn (User $u) => ['id' => $u->idp_id, 'email' => $u->email, 'name' => $u->name, 'source' => 'gpm']);

        return response()->json(['data' => $known->values()->concat($local)->values()]);
    }

    /**
     * Mint a session token for the given email, creating the fake identity
     * from the matching local user when the IdP does not know it yet.
     */
    public function token(Request $request): JsonResponse
    {
        $email = mb_strtolower((string) $request->validate(['email' => 'required|email'])['email']);

        $record = $this->store->findByEmail($email);

        if (! $record) {
            $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->with('person')->first();
            if (! $user) {
                throw ValidationException::withMessages(['email' => 'No fake IdP identity or GPM user has that email.']);
            }
            $record = $this->store->put([
                'id' => $user->idp_id ?? $this->store->nextId(),
                'email' => $user->email,
                'first_name' => $user->person?->first_name,
                'last_name' => $user->person?->last_name,
                'external_id' => $user->person?->uuid,
            ]);
        }

        return response()->json([
            'token' => $this->issuer->issue($record['id'], ['email' => $record['email']]),
            'idp_id' => $record['id'],
        ]);
    }
}
