<?php

namespace Tests\Feature\End2End\Auth;

use Tests\TestCase;
use Illuminate\Support\Once;
use Laravel\Passport\ClientRepository;
use PHPUnit\Framework\Attributes\Test;

class OAuthClientCommandsTest extends TestCase
{
    #[Test]
    public function passport_client_creates_a_client_credentials_client()
    {
        $this->artisan('passport:client', ['--client' => true, '--name' => 'GeneTracker'])
            ->expectsOutputToContain('Client ID')
            ->assertSuccessful();

        $this->assertDatabaseHas('oauth_clients', ['name' => 'GeneTracker', 'revoked' => false]);
    }

    #[Test]
    public function list_shows_active_clients_and_revoke_kills_client_and_tokens()
    {
        $client = app(ClientRepository::class)->createClientCredentialsGrantClient('GeneTracker');
        $token = $this->post('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->id,
            'client_secret' => $client->plainSecret,
            'scope' => 'reports:read',
        ])->assertOk()->json('access_token');

        $this->artisan('oauth-client:list')->expectsOutputToContain('GeneTracker')->assertSuccessful();

        $this->artisan('oauth-client:revoke', ['id' => $client->id])
            ->expectsOutputToContain('1 outstanding token(s)')
            ->assertSuccessful();
        Once::flush();

        $this->getJson('/api/report/people', ['Authorization' => 'Bearer '.$token])->assertStatus(401);
        $this->artisan('oauth-client:list')->expectsOutput('No clients.')->assertSuccessful();
        $this->artisan('oauth-client:list', ['--all' => true])->expectsOutputToContain('GeneTracker')->assertSuccessful();
        $this->artisan('oauth-client:revoke', ['id' => 'nope'])->assertFailed();
    }
}
