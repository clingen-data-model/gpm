<?php

namespace App\Auth\CILogon;

use App\Modules\Person\Events\InviteRedeemed;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Models\Person;
use App\Modules\Person\Models\PersonIdentity;
use App\Modules\User\Actions\UserCreate;
use App\Modules\User\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Maps a CILogon identity to the GPM user to log in, linking it to a Person
 * when it is new. Never creates a Person: an unknown identity is rejected.
 */
class CILogonIdentityResolver
{
    public function __construct(private UserCreate $createUser)
    {
    }

    public function resolve(SocialiteUser $identity, ?Invite $invite = null, ?User $linkTo = null): User
    {
        $sub = $identity->getId();
        if (! $sub) {
            throw new CILogonLoginRejected('cilogon_failed');
        }

        $existing = PersonIdentity::where('issuer', CILogonProvider::issuer())
            ->where('sub', $sub)
            ->first();

        if ($existing) {
            return $this->loginExisting($existing, $identity, $invite, $linkTo);
        }

        $person = match (true) {
            $linkTo !== null => $linkTo->person,
            $invite !== null => $invite->person,
            default => $this->personByOrcid($identity) ?? $this->personByTrustedEmail($identity),
        };

        if (! $person) {
            throw new CILogonLoginRejected('no_account');
        }

        return DB::transaction(function () use ($person, $identity, $invite) {
            [$user, $created] = $this->ensureUser($person);
            $person->identities()->create(
                ['issuer' => CILogonProvider::issuer(), 'sub' => $identity->getId()] + $this->claims($identity)
            );
            if ($invite) {
                $this->redeem($invite, $user, $created);
            }

            return $user;
        });
    }

    private function loginExisting(PersonIdentity $existing, SocialiteUser $identity, ?Invite $invite, ?User $linkTo): User
    {
        $person = $existing->person;
        if (! $person) {
            throw new CILogonLoginRejected('no_account');
        }
        if ($linkTo && $person->user_id !== $linkTo->id) {
            throw new CILogonLoginRejected('already_linked');
        }
        if ($invite && $invite->person_id !== $person->id) {
            throw new CILogonLoginRejected('already_linked');
        }

        $existing->update($this->claims($identity));

        return DB::transaction(function () use ($person, $invite) {
            [$user, $created] = $this->ensureUser($person);
            if ($invite) {
                $this->redeem($invite, $user, $created);
            }

            return $user;
        });
    }

    /**
     * @return array{User, bool} the user and whether it was just created
     */
    private function ensureUser(Person $person): array
    {
        if ($person->user) {
            return [$person->user, false];
        }

        if (User::where('email', $person->email)->exists()) {
            throw new CILogonLoginRejected('account_conflict');
        }

        $user = $this->createUser->handle(
            name: $person->first_name.' '.$person->last_name,
            email: $person->email,
        );
        $person->user()->associate($user)->save();

        return [$user, true];
    }

    // Mirrors InviteRedeem / InviteRedeemForExistingUser: only a new account fires InviteRedeemed.
    private function redeem(Invite $invite, User $user, bool $userCreated): void
    {
        $invite->markRedeemed(Carbon::now())->save();
        if ($userCreated) {
            Event::dispatch(new InviteRedeemed($invite, $user));
        }
    }

    private function personByOrcid(SocialiteUser $identity): ?Person
    {
        $orcid = $this->orcidFrom($identity);
        if (! $orcid) {
            return null;
        }

        // orcid_id is free text (and cast to int on the model), so compare normalized raw values.
        $matches = Person::where('orcid_id', 'like', '%'.$orcid.'%')
            ->get()
            ->filter(fn (Person $p) => static::normalizeOrcid($p->getRawOriginal('orcid_id')) === $orcid);

        return $matches->count() === 1 ? $matches->first() : null;
    }

    // Only links people who already have an account; first-time access still goes through an invite.
    private function personByTrustedEmail(SocialiteUser $identity): ?Person
    {
        $idp = $identity->getRaw()['idp'] ?? null;
        $email = $identity->getEmail();
        if (! $email || ! in_array($idp, config('services.cilogon.email_trusted_idps', []), true)) {
            return null;
        }

        $matches = Person::whereRaw('lower(email) = ?', [strtolower($email)])->get();
        $person = $matches->count() === 1 ? $matches->first() : null;

        return $person?->user_id ? $person : null;
    }

    private function orcidFrom(SocialiteUser $identity): ?string
    {
        $raw = $identity->getRaw();
        if (! str_contains($raw['idp'] ?? '', 'orcid.org')) {
            return null;
        }

        return static::normalizeOrcid($raw['oidc'] ?? null);
    }

    public static function normalizeOrcid(?string $value): ?string
    {
        if ($value && preg_match('/(\d{4}-\d{4}-\d{4}-\d{3}[\dX])/i', $value, $m)) {
            return strtoupper($m[1]);
        }

        return null;
    }

    private function claims(SocialiteUser $identity): array
    {
        $raw = $identity->getRaw();

        return [
            'idp' => $raw['idp'] ?? null,
            'idp_name' => $raw['idp_name'] ?? null,
            'email' => $identity->getEmail(),
            'orcid' => $this->orcidFrom($identity),
            'last_login_at' => Carbon::now(),
        ];
    }
}
