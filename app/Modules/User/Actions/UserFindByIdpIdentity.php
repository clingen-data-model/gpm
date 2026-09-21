<?php

namespace App\Modules\User\Actions;

use App\Services\Idp\IdpUser;
use App\Services\Idp\IdpIdentity;
use App\Modules\User\Models\User;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Resolve the local User behind a verified IdP identity, linking lazily.
 *
 * Resolution order:
 *   1. users.idp_provider / users.idp_id (already linked)
 *   2. the IdP record's external_id, which GPM sets to people.uuid
 *   3. the IdP record's primary email (or an email claim in the token)
 * On 2 and 3 the match is persisted so later logins take path 1.
 */
class UserFindByIdpIdentity
{
    use AsObject;

    public function handle(IdpIdentity $identity, ?IdpUser $idpUser = null): ?User
    {
        $linked = User::linkedToIdp($identity->subject, $identity->provider)->first();
        if ($linked) {
            return $linked;
        }

        $user = $this->matchByExternalId($idpUser) ?? $this->matchByEmail($identity, $idpUser);

        if (! $user) {
            return null;
        }

        if ($user->isLinkedToIdp()) {
            // Linked to some other IdP identity already; refuse to re-point it silently.
            Log::warning('IdP identity matched a user already linked to a different identity.', [
                'user_id' => $user->id,
                'provider' => $identity->provider,
                'subject' => $identity->subject,
            ]);

            return null;
        }

        $user->linkIdp($identity->subject, $identity->provider);

        return $user;
    }

    private function matchByExternalId(?IdpUser $idpUser): ?User
    {
        if (! $idpUser?->externalId) {
            return null;
        }

        return Person::where('uuid', $idpUser->externalId)->first()?->user;
    }

    /**
     * Try the token's email claim, then every usable address on the IdP
     * record (primary first).
     */
    private function matchByEmail(IdpIdentity $identity, ?IdpUser $idpUser): ?User
    {
        $candidates = IdpUser::normalizeEmails($identity->email(), $idpUser?->emails ?? [$idpUser?->email]);

        foreach ($candidates as $email) {
            if ($user = User::whereEmailInsensitive($email)->first()) {
                return $user;
            }
        }

        return null;
    }
}
