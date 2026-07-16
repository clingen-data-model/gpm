<?php

namespace Tests\Feature\Clerk;

use Tests\TestCase;
use App\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Impersonation stays a stateless, app-signed token (X-Impersonate-Token)
 * regardless of whether the primary identity check is session- or bearer-
 * based — these tests confirm that holds under the session-bridge design.
 */
class ImpersonationFlowTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Referer', 'http://localhost:8013');
        $this->setupRoles(['super-user', 'admin']);
    }

    #[Test]
    public function an_admin_can_take_and_the_token_resolves_the_target_on_a_later_request()
    {
        $admin = User::factory()->create()->assignRole('admin');
        $target = User::factory()->create();

        $take = $this->actingAs($admin)
            ->postJson("/api/impersonate/take/{$target->id}")
            ->assertStatus(200)
            ->json();

        $this->assertArrayHasKey('token', $take);

        // A fresh request, authenticated only by the admin's session plus the
        // impersonation token header — no re-verification of Clerk involved.
        $this->getJson('/api/current-user', ['X-Impersonate-Token' => $take['token']])
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $target->id]);
    }

    #[Test]
    public function leaving_is_a_no_op_that_does_not_require_the_token()
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)
            ->postJson('/api/impersonate/leave')
            ->assertStatus(204);
    }

    #[Test]
    public function a_non_admin_cannot_take()
    {
        $user = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/impersonate/take/{$target->id}")
            ->assertStatus(403);
    }

    #[Test]
    public function a_super_user_cannot_be_impersonated()
    {
        $admin = User::factory()->create()->assignRole('admin');
        $superUser = User::factory()->create()->assignRole('super-user');

        $this->actingAs($admin)
            ->postJson("/api/impersonate/take/{$superUser->id}")
            ->assertStatus(403);
    }
}
