<?php

namespace Tests\Feature\End2End\Auth;

use Tests\TestCase;
use App\Modules\User\Models\User;
use App\Modules\Person\Models\Person;
use App\Services\Idp\Fake\FakeIdpStore;
use PHPUnit\Framework\Attributes\Test;
use App\Services\Idp\Fake\FakeIdpClient;
use App\Services\Idp\Fake\FakeTokenIssuer;

class IdpSessionLoginTest extends TestCase
{
    private const URL = '/api/idp/session-login';

    private FakeIdpStore $store;
    private FakeTokenIssuer $issuer;

    public function setup(): void
    {
        parent::setup();
        $this->store = app(FakeIdpStore::class);
        $this->issuer = app(FakeTokenIssuer::class);
    }

    private function idpUser(array $attrs): array
    {
        return $this->store->put(array_merge(['id' => $this->store->nextId()], $attrs));
    }

    private function exchange(string $idpId, array $claims = [])
    {
        return $this->postJson(self::URL, [], ['Authorization' => 'Bearer '.$this->issuer->issue($idpId, $claims)]);
    }

    #[Test]
    public function rejects_a_missing_or_invalid_token()
    {
        $this->postJson(self::URL)->assertStatus(401);
        $this->postJson(self::URL, [], ['Authorization' => 'Bearer nope'])->assertStatus(401);
        $this->assertGuest();
    }

    #[Test]
    public function establishes_a_session_for_a_linked_user()
    {
        $user = User::factory()->linkedToIdp('user_fake_linked')->create();
        $this->idpUser(['id' => 'user_fake_linked', 'email' => $user->email]);

        $this->exchange('user_fake_linked')
            ->assertOk()
            ->assertJson(['user_id' => $user->id]);

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function the_session_alone_authenticates_later_requests()
    {
        $user = User::factory()->linkedToIdp('user_fake_linked')->create();
        $this->idpUser(['id' => 'user_fake_linked', 'email' => $user->email]);

        $this->exchange('user_fake_linked')->assertOk();

        $this->getJson('/api/current-user')->assertOk()->assertJsonPath('data.id', $user->id);
        $this->getJson('/api/authenticated')->assertOk();
    }

    #[Test]
    public function links_an_unlinked_user_by_external_id_matching_the_person_uuid()
    {
        $user = $this->setupUserWithPerson();
        $this->idpUser(['id' => 'user_fake_ext', 'email' => 'different@example.com', 'external_id' => $user->person->uuid]);

        $this->exchange('user_fake_ext')->assertOk();

        $this->assertSame('user_fake_ext', $user->fresh()->idp_id);
        $this->assertSame('clerk', $user->fresh()->idp_provider);
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function links_an_unlinked_user_by_email_case_insensitively()
    {
        $user = User::factory()->create(['email' => 'Jane.Doe@example.com']);
        $this->idpUser(['id' => 'user_fake_mail', 'email' => 'jane.doe@EXAMPLE.com']);

        $this->exchange('user_fake_mail')->assertOk();

        $this->assertSame('user_fake_mail', $user->fresh()->idp_id);
    }

    #[Test]
    public function links_by_an_email_claim_when_the_directory_is_unreachable()
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);
        app(FakeIdpClient::class)->failNext();

        $this->exchange('user_fake_claim', ['email' => 'jane@example.com'])->assertOk();

        $this->assertSame('user_fake_claim', $user->fresh()->idp_id);
    }

    #[Test]
    public function rejects_an_identity_that_matches_no_local_user()
    {
        $this->idpUser(['id' => 'user_fake_nobody', 'email' => 'nobody@example.com']);

        $this->exchange('user_fake_nobody')->assertStatus(403);
        $this->assertGuest();
    }

    #[Test]
    public function does_not_relink_a_user_already_bound_to_another_identity()
    {
        $user = User::factory()->linkedToIdp('user_fake_original')->create(['email' => 'jane@example.com']);
        $this->idpUser(['id' => 'user_fake_other', 'email' => 'jane@example.com']);

        $this->exchange('user_fake_other')->assertStatus(403);

        $this->assertSame('user_fake_original', $user->fresh()->idp_id);
        $this->assertGuest();
    }

    #[Test]
    public function syncs_name_and_email_from_the_idp_record()
    {
        $user = User::factory()->linkedToIdp('user_fake_sync')->create(['name' => 'Old Name', 'email' => 'old@example.com']);
        $this->idpUser(['id' => 'user_fake_sync', 'email' => 'new@example.com', 'first_name' => 'New', 'last_name' => 'Name']);

        $this->exchange('user_fake_sync')->assertOk();

        $fresh = $user->fresh();
        $this->assertSame('New Name', $fresh->name);
        $this->assertSame('new@example.com', $fresh->email);
        $this->assertDatabaseHas('activity_log', ['subject_id' => $user->id, 'subject_type' => User::class, 'description' => 'User updated']);
    }

    #[Test]
    public function skips_email_sync_when_another_user_owns_the_address()
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->linkedToIdp('user_fake_dup')->create(['email' => 'mine@example.com']);
        $this->idpUser(['id' => 'user_fake_dup', 'email' => 'taken@example.com']);

        $this->exchange('user_fake_dup')->assertOk();

        $this->assertSame('mine@example.com', $user->fresh()->email);
    }

    #[Test]
    public function can_be_disabled_per_config()
    {
        $user = User::factory()->linkedToIdp('user_fake_sync')->create(['name' => 'Old Name']);
        $this->idpUser(['id' => 'user_fake_sync', 'email' => $user->email, 'first_name' => 'New', 'last_name' => 'Name']);
        config(['idp.sync_profile_on_login' => false]);

        $this->exchange('user_fake_sync')->assertOk();

        $this->assertSame('Old Name', $user->fresh()->name);
    }

    #[Test]
    public function records_the_login_in_the_activity_log()
    {
        $user = User::factory()->linkedToIdp('user_fake_log')->create();
        $this->idpUser(['id' => 'user_fake_log', 'email' => $user->email]);

        $this->exchange('user_fake_log')->assertOk();

        $this->assertDatabaseHas('activity_log', ['subject_id' => $user->id, 'subject_type' => User::class, 'description' => 'Logged in.']);
    }

    #[Test]
    public function is_not_available_when_the_idp_driver_is_null()
    {
        config(['idp.driver' => 'null']);

        $this->exchange('user_fake_anything')->assertStatus(404);
    }

    #[Test]
    public function the_test_helper_logs_a_user_in_through_the_exchange()
    {
        $user = $this->setupUserWithPerson();

        $this->loginViaIdp($user);

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->fresh()->isLinkedToIdp());
        $this->getJson('/api/current-user')->assertOk();
    }
}
