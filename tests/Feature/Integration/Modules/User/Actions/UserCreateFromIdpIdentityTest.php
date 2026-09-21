<?php

namespace Tests\Feature\Integration\Modules\User\Actions;

use Tests\TestCase;
use App\Modules\User\Models\User;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Models\Person;
use App\Services\Idp\Fake\FakeIdpStore;
use PHPUnit\Framework\Attributes\Test;
use App\Services\Idp\Fake\FakeIdpClient;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\User\Actions\UserCreateFromIdpIdentity;

class UserCreateFromIdpIdentityTest extends TestCase
{
    use RefreshDatabase;

    private FakeIdpClient $client;
    private FakeIdpStore $store;
    private Person $person;

    public function setup(): void
    {
        parent::setup();
        $this->client = app(FakeIdpClient::class);
        $this->store = $this->client->store();
        $this->person = Person::factory()->create(['first_name' => 'Pam', 'last_name' => 'Poovey', 'email' => 'pam@example.com', 'user_id' => null]);
    }

    private function identity(array $attrs = []): array
    {
        return $this->store->put(array_merge([
            'id' => 'user_fake_pam',
            'email' => 'pam@example.com',
            'emails' => ['pam.poovey@other.org'],
            'first_name' => 'Pamela',
            'last_name' => 'Poovey',
        ], $attrs));
    }

    private function link(?string $email = null, ?Person $person = null): User
    {
        return UserCreateFromIdpIdentity::run($person ?? $this->person, $this->client->getUser('user_fake_pam'), $email);
    }

    #[Test]
    public function creates_a_linked_user_for_the_person_without_mirroring()
    {
        $this->identity();

        $user = $this->link();

        $this->assertSame('pam@example.com', $user->email);
        $this->assertSame('Pamela Poovey', $user->name);
        $this->assertSame('user_fake_pam', $user->idp_id);
        $this->assertSame('clerk', $user->idp_provider);
        $this->assertSame($user->id, $this->person->fresh()->user_id);
        $this->assertCount(1, $this->store->all(), 'no second identity is created at the IdP');
    }

    #[Test]
    public function can_use_a_secondary_address_on_the_identity()
    {
        $this->identity();

        $user = $this->link('Pam.Poovey@other.org');

        $this->assertSame('Pam.Poovey@other.org', $user->email);
    }

    #[Test]
    public function redeems_a_pending_invite_for_the_person()
    {
        $this->identity();
        $invite = Invite::factory()->create(['person_id' => $this->person->id, 'redeemed_at' => null]);

        $user = $this->link();

        $this->assertNotNull($invite->fresh()->redeemed_at);
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Person::class,
            'subject_id' => $this->person->id,
            'activity_type' => 'invite-redeemed',
        ]);
        $this->assertSame($user->id, $this->person->fresh()->user_id);
    }

    #[Test]
    public function backfills_an_empty_external_id_with_the_person_uuid()
    {
        $this->identity(['external_id' => null]);

        $this->link();

        $this->assertSame($this->person->uuid, $this->store->find('user_fake_pam')['external_id']);
    }

    #[Test]
    public function leaves_a_different_external_id_alone()
    {
        $this->identity(['external_id' => 'someone-elses-uuid']);

        $this->link();

        $this->assertSame('someone-elses-uuid', $this->store->find('user_fake_pam')['external_id']);
    }

    #[Test]
    public function still_creates_the_user_when_the_backfill_fails()
    {
        $this->identity();
        $idpUser = $this->client->getUser('user_fake_pam');
        $this->client->failNext();

        $user = UserCreateFromIdpIdentity::run($this->person, $idpUser);

        $this->assertSame('user_fake_pam', $user->fresh()->idp_id);
        $this->assertNull($this->store->find('user_fake_pam')['external_id'] ?? null);
    }

    #[Test]
    public function rejects_an_identity_linked_to_another_user()
    {
        $this->identity();
        User::factory()->linkedToIdp('user_fake_pam')->create();

        $this->expectException(ValidationException::class);
        $this->link();
    }

    #[Test]
    public function rejects_an_address_that_is_not_on_the_identity()
    {
        $this->identity();

        try {
            $this->link('other@example.com');
            $this->fail('expected a validation error');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email', $e->errors());
        }
        $this->assertDatabaseMissing('users', ['idp_id' => 'user_fake_pam']);
    }

    #[Test]
    public function rejects_an_address_owned_by_another_user()
    {
        $this->identity();
        User::factory()->create(['email' => 'PAM@example.com']);

        $this->expectException(ValidationException::class);
        $this->link();
    }

    #[Test]
    public function rejects_a_person_who_already_has_an_account()
    {
        $this->identity();
        $user = User::factory()->create();
        $this->person->user()->associate($user)->save();

        $this->expectException(ValidationException::class);
        $this->link();
    }
}
