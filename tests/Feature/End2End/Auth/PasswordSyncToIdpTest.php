<?php

namespace Tests\Feature\End2End\Auth;

use Tests\TestCase;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Password;
use App\Services\Idp\Fake\FakeIdpClient;
use App\Modules\User\Actions\UserPasswordChange;

class PasswordSyncToIdpTest extends TestCase
{
    private FakeIdpClient $client;

    public function setup(): void
    {
        parent::setup();
        $this->client = app(FakeIdpClient::class);
    }

    private function linkedUser(): User
    {
        $idpUser = $this->client->createUser(['email' => 'jane@example.com', 'password' => 'old-password']);

        return User::factory()->linkedToIdp($idpUser->id)->create([
            'email' => 'jane@example.com',
            'password' => Hash::make('old-password'),
        ]);
    }

    #[Test]
    public function profile_password_update_propagates_to_the_idp()
    {
        $user = $this->linkedUser();

        $this->actingAs($user)->putJson('/api/user/password', [
            'current_password' => 'old-password',
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertOk();

        $this->assertTrue(Hash::check('brand-new-password', $user->fresh()->password));
        $this->assertTrue($this->client->passwordMatches($user->idp_id, 'brand-new-password'));
    }

    #[Test]
    public function password_reset_propagates_to_the_idp()
    {
        $user = $this->linkedUser();
        $token = Password::createToken($user);

        $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'reset-password-1',
            'password_confirmation' => 'reset-password-1',
        ])->assertOk();

        $this->assertTrue($this->client->passwordMatches($user->idp_id, 'reset-password-1'));
    }

    #[Test]
    public function artisan_password_change_propagates_to_the_idp()
    {
        $user = $this->linkedUser();

        UserPasswordChange::run($user, 'from-the-console');

        $this->assertTrue($this->client->passwordMatches($user->idp_id, 'from-the-console'));
    }

    #[Test]
    public function unlinked_users_do_not_contact_the_idp()
    {
        $user = User::factory()->create(['password' => Hash::make('old-password')]);

        UserPasswordChange::run($user, 'new-password');

        $this->assertSame([], $this->client->calls());
    }

    #[Test]
    public function an_idp_failure_does_not_block_the_local_change()
    {
        $user = $this->linkedUser();
        $this->client->failNext();

        $this->actingAs($user)->putJson('/api/user/password', [
            'current_password' => 'old-password',
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertOk();

        $this->assertTrue(Hash::check('brand-new-password', $user->fresh()->password));
        $this->assertTrue($this->client->passwordMatches($user->idp_id, 'old-password'));
    }
}
