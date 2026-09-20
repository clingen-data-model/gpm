<?php

namespace Tests\Feature\End2End\Auth;

use Tests\TestCase;
use Laravel\Passport\Client;
use Illuminate\Support\Once;
use Laravel\Passport\ClientRepository;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\RateLimiter;

class OAuthClientCredentialsTest extends TestCase
{
    private const REPORT = '/api/report/people';

    private Client $client;
    private string $secret;

    public function setup(): void
    {
        parent::setup();
        $this->client = app(ClientRepository::class)->createClientCredentialsGrantClient('GeneTracker test');
        $this->secret = $this->client->plainSecret;
    }

    private function token(string $scope = 'reports:read', ?string $secret = null)
    {
        return $this->post('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $this->client->id,
            'client_secret' => $secret ?? $this->secret,
            'scope' => $scope,
        ]);
    }

    #[Test]
    public function issues_a_short_lived_token_for_valid_client_credentials()
    {
        $response = $this->token()->assertOk();

        $this->assertSame('Bearer', $response->json('token_type'));
        $this->assertNotEmpty($response->json('access_token'));
        $this->assertEqualsWithDelta(600, $response->json('expires_in'), 5);
        $this->assertArrayNotHasKey('refresh_token', $response->json());
    }

    #[Test]
    public function rejects_a_wrong_secret_and_an_unknown_scope()
    {
        $this->token(secret: 'wrong')->assertStatus(401);
        $this->token(scope: 'admin:everything')->assertStatus(400);
    }

    #[Test]
    public function anonymous_requests_are_rejected()
    {
        $this->getJson(self::REPORT)->assertStatus(401);
    }

    #[Test]
    public function a_token_with_the_scope_reaches_report_endpoints()
    {
        $token = $this->token()->json('access_token');

        $this->getJson(self::REPORT, ['Authorization' => 'Bearer '.$token])->assertSuccessful();
    }

    #[Test]
    public function a_token_without_the_scope_is_forbidden()
    {
        $token = $this->token('people:read')->json('access_token');

        $this->getJson(self::REPORT, ['Authorization' => 'Bearer '.$token])->assertStatus(403);
    }

    #[Test]
    public function a_revoked_client_gets_no_new_tokens_and_revoked_tokens_stop_working()
    {
        $token = $this->token()->json('access_token');

        // Revoking the client alone stops issuance; outstanding tokens live on
        // until they expire (10 minutes) or are revoked themselves, which is
        // what the oauth-client:revoke command does.
        $this->client->forceFill(['revoked' => true])->save();
        Once::flush(); // ClientRepository::find() is memoized per process; real requests are separate processes.
        $this->token()->assertStatus(401);
        $this->getJson(self::REPORT, ['Authorization' => 'Bearer '.$token])->assertSuccessful();

        $this->client->tokens()->update(['revoked' => true]);
        $this->getJson(self::REPORT, ['Authorization' => 'Bearer '.$token])->assertStatus(401);
    }

    #[Test]
    public function garbage_bearer_tokens_are_rejected()
    {
        $this->getJson(self::REPORT, ['Authorization' => 'Bearer not.a.jwt'])->assertStatus(401);
    }

    #[Test]
    public function signed_in_users_still_reach_report_endpoints()
    {
        $this->login();

        $this->getJson(self::REPORT)->assertSuccessful();
    }

    #[Test]
    public function the_token_endpoint_is_rate_limited()
    {
        $this->assertNotNull(RateLimiter::limiter('oauth-token'));

        $this->token()->assertHeader('X-RateLimit-Limit', '20');
    }

    #[Test]
    public function only_the_token_route_is_exposed()
    {
        $this->post('/oauth/authorize')->assertStatus(404);
        $this->get('/oauth/tokens')->assertStatus(404);
    }
}
