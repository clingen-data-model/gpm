<?php

namespace Tests\Feature\Clerk;

use Tests\TestCase;
use Mockery;
use App\Modules\User\Models\User;
use App\Services\Clerk\ClerkTokenVerifier;
use PHPUnit\Framework\Attributes\Test;

class ClerkSessionLoginTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Sanctum only starts the session (and enforces CSRF) for requests it
        // recognises as coming from the SPA — matched by Origin/Referer
        // against SANCTUM_STATEFUL_DOMAINS (config/sanctum.php, driven by
        // APP_URL — localhost:8013 in this environment, not overridden by
        // phpunit.xml since that env var is already set at the OS level).
        // Without this header the request is treated as a plain stateless
        // API call and never gets a session store at all.
        $this->withHeader('Referer', 'http://localhost:8013');
    }

    #[Test]
    public function it_establishes_a_session_for_an_already_linked_user()
    {
        $user = User::factory()->create(['clerk_id' => 'clerk_1']);
        $this->fakeVerifier(['sub' => 'clerk_1']);

        $this->postJson('/api/auth/clerk/session-login', [], ['Authorization' => 'Bearer test-token'])
            ->assertStatus(200)
            ->assertJson(['user_id' => $user->id]);

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function the_session_alone_authenticates_a_later_request_with_no_bearer_token()
    {
        User::factory()->create(['clerk_id' => 'clerk_1']);
        $this->fakeVerifier(['sub' => 'clerk_1']);

        $this->postJson('/api/auth/clerk/session-login', [], ['Authorization' => 'Bearer test-token'])
            ->assertStatus(200);

        // No Authorization header this time — only the session cookie set above.
        $this->getJson('/api/system-info')->assertStatus(200);
    }

    #[Test]
    public function it_lazy_links_an_unlinked_user_by_verified_email()
    {
        $user = User::factory()->create(['clerk_id' => null, 'email' => 'jane@example.com']);
        $this->fakeVerifier(['sub' => 'clerk_2', 'email' => 'jane@example.com']);

        $this->postJson('/api/auth/clerk/session-login', [], ['Authorization' => 'Bearer test-token'])
            ->assertStatus(200);

        $this->assertSame('clerk_2', $user->fresh()->clerk_id);
    }

    #[Test]
    public function it_rejects_when_no_local_user_matches()
    {
        $this->fakeVerifier(['sub' => 'clerk_nobody', 'email' => 'nobody@example.com']);

        $this->postJson('/api/auth/clerk/session-login', [], ['Authorization' => 'Bearer test-token'])
            ->assertStatus(403);

        $this->assertGuest();
    }

    #[Test]
    public function it_rejects_an_invalid_clerk_token()
    {
        $this->fakeVerifier(null);

        $this->postJson('/api/auth/clerk/session-login', [], ['Authorization' => 'Bearer bad-token'])
            ->assertStatus(401);
    }

    private function fakeVerifier(?array $claims): void
    {
        $verifier = Mockery::mock(ClerkTokenVerifier::class);
        $verifier->shouldReceive('verify')->andReturn($claims);
        $this->app->instance(ClerkTokenVerifier::class, $verifier);
    }
}
