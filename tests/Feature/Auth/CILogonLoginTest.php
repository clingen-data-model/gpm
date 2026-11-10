<?php

namespace Tests\Feature\Auth;

use App\Modules\Person\Events\InviteRedeemed;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Models\Person;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CILogonLoginTest extends TestCase
{
    use RefreshDatabase;

    const ISSUER = 'https://cilogon.org';
    const ORCID_IDP = 'http://orcid.org/oauth/authorize';
    const GOOGLE_IDP = 'http://google.com/accounts/o8/id';

    public function setUp(): void
    {
        parent::setUp();
        config([
            'services.cilogon.client_id' => 'cilogon:/client_id/test',
            'services.cilogon.base_url' => self::ISSUER,
            'services.cilogon.email_trusted_idps' => [],
        ]);
    }

    private function identity(array $claims): SocialiteUser
    {
        $claims += ['sub' => 'http://cilogon.org/serverA/users/1', 'idp' => 'urn:mace:incommon:example.edu'];

        return (new SocialiteUser)->setRaw($claims)->map([
            'id' => $claims['sub'],
            'name' => $claims['name'] ?? null,
            'email' => $claims['email'] ?? null,
        ]);
    }

    private function fakeIdentity(array $claims): void
    {
        Socialite::shouldReceive('driver->user')->andReturn($this->identity($claims));
    }

    private function hitCallback(array $session = [])
    {
        return $this->withSession($session)->get('/auth/cilogon/callback?code=abc&state=xyz');
    }

    private function personWithUser(array $attrs = []): Person
    {
        $person = Person::factory()->create($attrs);
        $person->user()->associate(User::factory()->create(['email' => $person->email]))->save();

        return $person->fresh();
    }

    #[Test]
    public function routes_404_when_cilogon_is_not_configured()
    {
        config(['services.cilogon.client_id' => null]);

        $this->get('/auth/cilogon/redirect')->assertNotFound();
        $this->get('/auth/cilogon/callback')->assertNotFound();
    }

    #[Test]
    public function redirect_stashes_a_pending_invite_code()
    {
        $invite = Invite::factory()->create(['redeemed_at' => null]);
        Socialite::shouldReceive('driver->redirect')->andReturn(redirect(self::ISSUER.'/authorize'));

        $this->get('/auth/cilogon/redirect?invite='.$invite->code)
            ->assertRedirect(self::ISSUER.'/authorize')
            ->assertSessionHas('cilogon.invite', $invite->code);
    }

    #[Test]
    public function redirect_rejects_a_redeemed_invite()
    {
        $invite = Invite::factory()->create(['redeemed_at' => now()]);

        $this->get('/auth/cilogon/redirect?invite='.$invite->code)
            ->assertRedirect('/login?cilogon_error=invite_invalid');
    }

    #[Test]
    public function redirect_refuses_to_link_when_not_signed_in()
    {
        $this->get('/auth/cilogon/redirect?link=1')
            ->assertRedirect('/login?cilogon_error=not_signed_in');
    }

    #[Test]
    public function logs_in_a_linked_identity()
    {
        $person = $this->personWithUser();
        $person->identities()->create(['issuer' => self::ISSUER, 'sub' => 'http://cilogon.org/serverA/users/1']);
        $this->fakeIdentity(['idp_name' => 'Example University', 'email' => 'new@example.edu']);

        $this->hitCallback()->assertRedirect('/');

        $this->assertAuthenticatedAs($person->user, 'web');
        $this->assertDatabaseHas('person_identities', [
            'person_id' => $person->id,
            'idp_name' => 'Example University',
            'email' => 'new@example.edu',
        ]);
    }

    #[Test]
    public function rejects_an_unknown_identity_without_creating_anything()
    {
        $this->personWithUser(['email' => 'someone@example.edu']);
        $this->fakeIdentity(['email' => 'someone@example.edu']);

        $this->hitCallback()->assertRedirect('/login?cilogon_error=no_account');

        $this->assertGuest('web');
        $this->assertDatabaseCount('person_identities', 0);
    }

    #[Test]
    public function invite_links_identity_and_creates_the_account()
    {
        Event::fake([InviteRedeemed::class]);
        $invite = Invite::factory()->create(['redeemed_at' => null]);
        $this->fakeIdentity(['email' => 'different@elsewhere.org']);

        $this->hitCallback(['cilogon.invite' => $invite->code])->assertRedirect('/');

        $person = $invite->person->fresh();
        $this->assertNotNull($person->user_id);
        $this->assertSame($person->email, $person->user->email);
        $this->assertAuthenticatedAs($person->user, 'web');
        $this->assertNotNull($invite->fresh()->redeemed_at);
        $this->assertDatabaseHas('person_identities', ['person_id' => $person->id, 'email' => 'different@elsewhere.org']);
        Event::assertDispatched(InviteRedeemed::class);
    }

    #[Test]
    public function invite_for_an_existing_account_links_without_creating_a_user()
    {
        Event::fake([InviteRedeemed::class]);
        $person = $this->personWithUser();
        $invite = Invite::factory()->create(['redeemed_at' => null, 'person_id' => $person->id]);
        $this->fakeIdentity([]);

        $this->hitCallback(['cilogon.invite' => $invite->code])->assertRedirect('/');

        $this->assertAuthenticatedAs($person->user, 'web');
        $this->assertSame(1, User::count());
        $this->assertNotNull($invite->fresh()->redeemed_at);
        Event::assertNotDispatched(InviteRedeemed::class);
    }

    #[Test]
    public function invite_is_rejected_when_identity_belongs_to_someone_else()
    {
        $other = $this->personWithUser();
        $other->identities()->create(['issuer' => self::ISSUER, 'sub' => 'http://cilogon.org/serverA/users/1']);
        $invite = Invite::factory()->create(['redeemed_at' => null]);
        $this->fakeIdentity([]);

        $this->hitCallback(['cilogon.invite' => $invite->code])->assertRedirect('/login?cilogon_error=already_linked');

        $this->assertGuest('web');
        $this->assertNull($invite->fresh()->redeemed_at);
    }

    #[Test]
    public function invite_is_rejected_when_the_email_already_has_a_different_account()
    {
        $invite = Invite::factory()->create(['redeemed_at' => null]);
        User::factory()->create(['email' => $invite->person->email]);
        $this->fakeIdentity([]);

        $this->hitCallback(['cilogon.invite' => $invite->code])->assertRedirect('/login?cilogon_error=account_conflict');

        $this->assertDatabaseCount('person_identities', 0);
    }

    #[Test]
    public function orcid_login_links_by_matching_orcid_id()
    {
        $person = $this->personWithUser(['orcid_id' => 'https://orcid.org/0000-0002-1825-009x']);
        $this->fakeIdentity(['idp' => self::ORCID_IDP, 'oidc' => 'http://orcid.org/0000-0002-1825-009X']);

        $this->hitCallback()->assertRedirect('/');

        $this->assertAuthenticatedAs($person->user, 'web');
        $this->assertDatabaseHas('person_identities', ['person_id' => $person->id, 'orcid' => '0000-0002-1825-009X']);
    }

    #[Test]
    public function orcid_claim_from_a_non_orcid_idp_is_ignored()
    {
        $this->personWithUser(['orcid_id' => '0000-0002-1825-0097']);
        $this->fakeIdentity(['idp' => 'urn:mace:incommon:example.edu', 'oidc' => 'http://orcid.org/0000-0002-1825-0097']);

        $this->hitCallback()->assertRedirect('/login?cilogon_error=no_account');
    }

    #[Test]
    public function ambiguous_orcid_match_is_rejected()
    {
        $this->personWithUser(['orcid_id' => '0000-0002-1825-0097']);
        $this->personWithUser(['orcid_id' => 'https://orcid.org/0000-0002-1825-0097']);
        $this->fakeIdentity(['idp' => self::ORCID_IDP, 'oidc' => 'http://orcid.org/0000-0002-1825-0097']);

        $this->hitCallback()->assertRedirect('/login?cilogon_error=no_account');
    }

    #[Test]
    public function email_links_an_existing_account_only_from_a_trusted_idp()
    {
        $person = $this->personWithUser(['email' => 'Curator@Example.org']);
        $this->fakeIdentity(['idp' => self::GOOGLE_IDP, 'email' => 'curator@example.org']);

        $this->hitCallback()->assertRedirect('/login?cilogon_error=no_account');

        config(['services.cilogon.email_trusted_idps' => [self::GOOGLE_IDP]]);
        $this->hitCallback()->assertRedirect('/');
        $this->assertAuthenticatedAs($person->user, 'web');
    }

    #[Test]
    public function trusted_email_does_not_bypass_the_invite_for_a_person_without_an_account()
    {
        config(['services.cilogon.email_trusted_idps' => [self::GOOGLE_IDP]]);
        Person::factory()->create(['email' => 'curator@example.org']);
        $this->fakeIdentity(['idp' => self::GOOGLE_IDP, 'email' => 'curator@example.org']);

        $this->hitCallback()->assertRedirect('/login?cilogon_error=no_account');
        $this->assertSame(0, User::count());
    }

    #[Test]
    public function signed_in_user_can_link_another_identity()
    {
        $person = $this->personWithUser();
        $this->fakeIdentity([]);

        $this->actingAs($person->user, 'web')
            ->hitCallback(['cilogon.link' => true])
            ->assertRedirect('/people/'.$person->uuid.'?tab=sign-in%20methods&cilogon_linked=1');

        $this->assertDatabaseHas('person_identities', ['person_id' => $person->id]);
    }

    #[Test]
    public function identities_from_several_providers_all_log_in_the_same_user()
    {
        $person = $this->personWithUser();
        $person->identities()->create(['issuer' => self::ISSUER, 'sub' => 'http://cilogon.org/serverA/users/1', 'idp' => self::GOOGLE_IDP]);
        $google = $this->identity(['sub' => 'http://cilogon.org/serverA/users/1', 'idp' => self::GOOGLE_IDP]);
        $orcid = $this->identity(['sub' => 'http://cilogon.org/serverA/users/2', 'idp' => self::ORCID_IDP]);
        Socialite::shouldReceive('driver->user')->andReturn($orcid, $google, $orcid);

        $this->actingAs($person->user, 'web')->hitCallback(['cilogon.link' => true]);
        Auth::guard('web')->logout();

        $this->hitCallback()->assertRedirect('/');
        $this->assertAuthenticatedAs($person->user, 'web');
        Auth::guard('web')->logout();
        $this->hitCallback()->assertRedirect('/');
        $this->assertAuthenticatedAs($person->user, 'web');

        $identities = $person->identities()->get();
        $this->assertEqualsCanonicalizing([self::GOOGLE_IDP, self::ORCID_IDP], $identities->pluck('idp')->all());
        $this->assertTrue($identities->every(fn ($i) => $i->last_login_at !== null));
    }

    #[Test]
    public function cannot_link_an_identity_already_linked_to_someone_else()
    {
        $other = $this->personWithUser();
        $other->identities()->create(['issuer' => self::ISSUER, 'sub' => 'http://cilogon.org/serverA/users/1']);
        $me = $this->personWithUser();
        $this->fakeIdentity([]);

        $this->actingAs($me->user, 'web')
            ->hitCallback(['cilogon.link' => true])
            ->assertRedirect('/people/'.$me->uuid.'?tab=sign-in%20methods&cilogon_error=already_linked');
    }

    #[Test]
    public function provider_failure_is_reported_not_thrown()
    {
        Socialite::shouldReceive('driver->user')->andThrow(new \Laravel\Socialite\Two\InvalidStateException);

        $this->hitCallback()->assertRedirect('/login?cilogon_error=cilogon_failed');
    }

    #[Test]
    public function provider_builds_a_pkce_authorization_url_with_cilogon_scopes()
    {
        config(['services.cilogon.redirect' => 'https://gpm.test/auth/cilogon/callback']);

        $location = $this->get('/auth/cilogon/redirect')->headers->get('Location');

        $this->assertStringStartsWith(self::ISSUER.'/authorize?', $location);
        parse_str(parse_url($location, PHP_URL_QUERY), $q);
        $this->assertSame('openid email profile org.cilogon.userinfo', $q['scope']);
        $this->assertSame('S256', $q['code_challenge_method']);
        $this->assertSame('https://gpm.test/auth/cilogon/callback', $q['redirect_uri']);
    }
}
