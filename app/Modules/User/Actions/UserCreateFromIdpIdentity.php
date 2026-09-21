<?php

namespace App\Modules\User\Actions;

use Throwable;
use App\Services\Idp\IdpUser;
use Illuminate\Support\Facades\DB;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Log;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsObject;
use App\Services\Idp\Contracts\IdpClient;
use App\Modules\Person\Events\InviteRedeemed;
use Illuminate\Validation\ValidationException;

/**
 * Give a Person who has no GPM login a User bound to an existing identity
 * at the identity provider, instead of inviting them to create a password.
 *
 * Shared by the member-add flow (a coordinator picked a ClinGen account)
 * and by invite redemption with an IdP sign-in. Any pending invite for the
 * person is redeemed, which also resolves FollowActions queued against it.
 * The identity's external_id is back-filled with the person's uuid when it
 * is empty; an existing, different value is logged and left alone.
 */
class UserCreateFromIdpIdentity
{
    use AsObject;

    public function __construct(private IdpClient $client, private UserCreate $createUser)
    {
    }

    /**
     * @param  string|null  $email  Address to use for users.email; defaults to the identity's primary.
     *
     * @throws ValidationException
     */
    public function handle(Person $person, IdpUser $idpUser, ?string $email = null): User
    {
        $email = trim((string) ($email ?? $idpUser->email));
        $this->guard($person, $idpUser, $email);

        $redeemedInvite = null;
        $user = DB::transaction(function () use ($person, $idpUser, $email, &$redeemedInvite) {
            $user = $this->createUser->handle(
                name: $idpUser->name() ?? $person->first_name.' '.$person->last_name,
                email: $email,
                password: null,
                person: $person,
                idpUser: $idpUser,
            );

            $invite = $person->invite()->first();
            if ($invite && ! $invite->hasBeenRedeemed()) {
                $invite->markRedeemed()->save();
                $redeemedInvite = $invite;
            }
            $person->unsetRelation('invite');

            return $user;
        });

        if ($redeemedInvite) {
            Event::dispatch(new InviteRedeemed($redeemedInvite, $user));
        }

        $this->backfillExternalId($person, $idpUser);

        return $user;
    }

    private function guard(Person $person, IdpUser $idpUser, string $email): void
    {
        if ($person->user_id) {
            $this->reject('This person already has a GPM account.');
        }
        if (User::linkedToIdp($idpUser->id)->exists()) {
            $this->reject('This ClinGen account already belongs to a different GPM user. Sign in with that account, or ask a coordinator to merge the records.');
        }
        if ($email === '') {
            $this->reject('This ClinGen account has no email address.');
        }
        if (! $idpUser->hasEmail($email)) {
            $this->reject('That address is not a verified address on this ClinGen account.');
        }
        if (User::whereEmailInsensitive($email)->exists()) {
            $this->reject('That address already belongs to another GPM user.');
        }
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['email' => [$message]]);
    }

    private function backfillExternalId(Person $person, IdpUser $idpUser): void
    {
        if ($idpUser->externalId === $person->uuid) {
            return;
        }
        if ($idpUser->externalId) {
            Log::info('IdP identity carries a different external_id than the linked person; left unchanged.', [
                'idp_id' => $idpUser->id,
                'external_id' => $idpUser->externalId,
                'person_uuid' => $person->uuid,
            ]);

            return;
        }

        try {
            $this->client->updateUser($idpUser->id, ['external_id' => $person->uuid]);
        } catch (Throwable $e) {
            Log::warning('Could not back-fill external_id on the IdP identity.', [
                'idp_id' => $idpUser->id,
                'person_uuid' => $person->uuid,
                'error' => $e->getMessage(),
            ]);
            report($e);
        }
    }
}
