<?php

namespace Tests\Feature\End2End\Auth;

use Tests\TestCase;
use App\Modules\User\Models\User;
use App\Services\Idp\Fake\FakeIdpStore;
use PHPUnit\Framework\Attributes\Test;

class FakeIdpDevEndpointsTest extends TestCase
{
    #[Test]
    public function lists_fake_identities_and_local_users()
    {
        app(FakeIdpStore::class)->put(['id' => 'user_fake_1', 'email' => 'idp@example.com', 'first_name' => 'I', 'last_name' => 'P']);
        $local = User::factory()->create(['email' => 'local@example.com']);

        $data = $this->getJson('/dev/idp/users')->assertOk()->json('data');

        $emails = array_column($data, 'email');
        $this->assertContains('idp@example.com', $emails);
        $this->assertContains('local@example.com', $emails);
    }

    #[Test]
    public function issues_a_token_for_a_local_user_and_creates_the_fake_identity()
    {
        $user = $this->setupUserWithPerson();

        $response = $this->postJson('/dev/idp/token', ['email' => $user->email])->assertOk();
        $token = $response->json('token');

        $record = app(FakeIdpStore::class)->find($response->json('idp_id'));
        $this->assertSame($user->person->uuid, $record['external_id']);

        // The token completes the normal exchange and links the user.
        $this->postJson('/api/idp/session-login', [], ['Authorization' => 'Bearer '.$token])->assertOk();
        $this->assertAuthenticatedAs($user);
        $this->assertSame($response->json('idp_id'), $user->fresh()->idp_id);
    }

    #[Test]
    public function reuses_an_existing_link_id_for_already_linked_users()
    {
        $user = User::factory()->linkedToIdp('user_fake_existing')->create();

        $this->postJson('/dev/idp/token', ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath('idp_id', 'user_fake_existing');
    }

    #[Test]
    public function rejects_unknown_emails()
    {
        $this->postJson('/dev/idp/token', ['email' => 'nobody@example.com'])->assertStatus(422);
    }

    #[Test]
    public function does_not_exist_outside_the_fake_driver_or_in_production()
    {
        config(['idp.driver' => 'clerk']);
        $this->getJson('/dev/idp/users')->assertStatus(404);
        $this->postJson('/dev/idp/token', ['email' => 'a@example.com'])->assertStatus(404);

        config(['idp.driver' => 'fake']);
        $this->app->detectEnvironment(fn () => 'production');
        $this->getJson('/dev/idp/users')->assertStatus(404);
    }
}
