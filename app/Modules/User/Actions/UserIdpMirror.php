<?php

namespace App\Modules\User\Actions;

use Throwable;
use App\Services\Idp\IdpUser;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Log;
use App\Providers\IdpServiceProvider;
use App\Services\Idp\IdpUserPayload;
use Lorisleiva\Actions\Concerns\AsObject;
use App\Services\Idp\Contracts\IdpClient;

/**
 * Create (or find and link) the IdP identity for a locally created user so
 * they can sign in through the identity provider straight away.
 *
 * Never throws: the local account is the source of truth and must be
 * created even when the IdP is unreachable. An unlinked user is picked up
 * later by lazy linking at session login or by idp:import-users.
 */
class UserIdpMirror
{
    use AsObject;

    public function __construct(private IdpClient $client)
    {
    }

    public function handle(User $user, ?string $plainPassword = null): ?IdpUser
    {
        if (! IdpServiceProvider::enabled() || ! config('idp.mirror_new_users', true)) {
            return null;
        }
        if ($user->isLinkedToIdp()) {
            return null;
        }

        try {
            $idpUser = $this->findExisting($user) ?? $this->client->createUser(IdpUserPayload::forUser($user, $plainPassword));
        } catch (Throwable $e) {
            Log::warning('Could not mirror user to the identity provider; leaving unlinked.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            report($e);

            return null;
        }

        $user->linkIdp($idpUser->id);

        return $idpUser;
    }

    private function findExisting(User $user): ?IdpUser
    {
        $externalId = $user->person?->uuid;
        if ($externalId && ($found = $this->client->findUserByExternalId($externalId))) {
            return $found;
        }

        return $this->client->findUserByEmail($user->email);
    }
}
