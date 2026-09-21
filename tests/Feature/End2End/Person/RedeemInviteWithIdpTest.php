<?php

namespace Tests\Feature\End2End\Person;

use Tests\TestCase;
use App\Modules\User\Models\User;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Models\Person;
use App\Services\Idp\Fake\FakeIdpStore;
use PHPUnit\Framework\Attributes\Test;
use App\Services\Idp\Fake\FakeIdpClient;
use App\Services\Idp\Fake\FakeTokenIssuer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RedeemInviteWithIdpTest extends TestCase
{
    use RefreshDatabase;

    private FakeIdpStore $store;
    private FakeTokenIssuer $issuer;
    private Invite $invite;

    public function setup(): void
    {
        parent::setup();
        $this->store = app(FakeIdpStore::class);
        $this->issuer = app(FakeTokenIssuer::class);

        $person = Person::factory()->create(['first_name' => 'Pam', 'last_name' => 'Poovey', 'email' => 'pam@example.com', 'user_id' => null]);
        $this->invite = Invite::factory()->create(['person_id' => $person->id, 'redeemed_at' => null]);

        $this->store->put(['id' => 'user_fake_pam', 'email' => 'Pamela@Example.com', 'first_name' => 'Pamela', 'last_name' => 'Poovey']);
    }

    private function url(?string $code = null): string
    {
        return '/api/people/invites/'.($code ?? $this->invite->code).'/idp';
    }

    private function redeem(string $idpId = 'user_fake_pam', ?string $code = null)
    {
        return $this->putJson($this->url($code), [], ['Authorization' => 'Bearer '.$this->issuer->issue($idpId)]);
    }

    #[Test]
    public function rejects_a_missing_or_invalid_token()
    {
        $this->putJson($this->url())->assertStatus(401);
        $this->putJson($this->url(), [], ['Authorization' => 'Bearer nope'])->assertStatus(401);

        $this->assertGuest();
        $this->assertNull($this->invite->fresh()->redeemed_at);
    }

    #[Test]
    public function returns_404_for_an_unknown_code()
    {
        $this->redeem(code: 'gobbledy-guk')->assertStatus(404);
    }

    #[Test]
    public function creates_a_linked_user_for_the_invited_person_and_signs_them_in()
    {
        $response = $this->redeem()->assertOk();

        $person = $this->invite->person->fresh();
        $this->assertNotNull($person->user_id);
        $response->assertJson(['user_id' => $person->user_id]);
        $this->assertDatabaseHas('users', ['id' => $person->user_id, 'email' => 'Pamela@Example.com', 'idp_id' => 'user_fake_pam', 'idp_provider' => 'clerk']);
        $this->assertAuthenticatedAs(User::find($person->user_id));
        $this->assertCount(1, $this->store->all(), 'no second identity is created');
    }

    #[Test]
    public function marks_the_invite_redeemed_and_logs_it()
    {
        $this->redeem()->assertOk();

        $this->assertNotNull($this->invite->fresh()->redeemed_at);
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Person::class,
            'subject_id' => $this->invite->person_id,
            'activity_type' => 'invite-redeemed',
        ]);
    }

    #[Test]
    public function backfills_the_identity_external_id()
    {
        $this->redeem()->assertOk();

        $this->assertSame($this->invite->person->uuid, $this->store->find('user_fake_pam')['external_id']);
    }

    #[Test]
    public function rejects_an_already_redeemed_invite()
    {
        $this->invite->update(['redeemed_at' => '2021-09-15']);

        $this->redeem()->assertStatus(422)->assertJsonValidationErrors(['code']);
        $this->assertGuest();
    }

    #[Test]
    public function rejects_an_invite_whose_person_already_has_an_account()
    {
        $this->invite->person->user()->associate(User::factory()->create())->save();

        $this->redeem()->assertStatus(422)->assertJsonValidationErrors(['code']);
        $this->assertGuest();
    }

    #[Test]
    public function rejects_an_identity_linked_to_a_different_user()
    {
        $other = User::factory()->linkedToIdp('user_fake_pam')->create(['email' => 'other@example.com']);

        $this->redeem()->assertStatus(422)->assertJsonValidationErrors(['email']);

        $this->assertGuest();
        $this->assertSame('user_fake_pam', $other->fresh()->idp_id);
        $this->assertNull($this->invite->person->fresh()->user_id);
        $this->assertNull($this->invite->fresh()->redeemed_at);
    }

    #[Test]
    public function rejects_an_identity_whose_address_belongs_to_another_user()
    {
        User::factory()->create(['email' => 'pamela@example.com']);

        $this->redeem()->assertStatus(422)->assertJsonValidationErrors(['email']);
        $this->assertGuest();
    }

    #[Test]
    public function fails_fast_when_the_idp_is_unreachable()
    {
        app(FakeIdpClient::class)->failNext();

        $this->redeem()->assertStatus(503);

        $this->assertGuest();
        $this->assertNull($this->invite->fresh()->redeemed_at);
        $this->assertNull($this->invite->person->fresh()->user_id);
    }

    #[Test]
    public function is_not_available_when_the_idp_driver_is_null()
    {
        config(['idp.driver' => 'null']);

        $this->redeem()->assertStatus(404);
    }
}
