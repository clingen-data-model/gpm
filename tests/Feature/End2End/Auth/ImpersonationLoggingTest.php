<?php

namespace Tests\Feature\End2End\Auth;

use Tests\TestCase;
use App\Models\Activity;
use App\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;

class ImpersonationLoggingTest extends TestCase
{
    private User $admin;
    private User $target;

    public function setup(): void
    {
        parent::setup();
        $this->setupRoles(['super-user', 'super-admin', 'admin']);
        $this->admin = User::factory()->create(['name' => 'Ada Admin']);
        $this->admin->assignRole('super-user');
        $this->target = User::factory()->create(['name' => 'Tom Target']);
    }

    #[Test]
    public function taking_and_leaving_impersonation_are_recorded_against_the_target_with_the_admin_as_causer()
    {
        $this->actingAs($this->admin, 'web')->get('/impersonate/take/'.$this->target->id)->assertRedirect();
        $this->assertAuthenticatedAs($this->target, 'web');

        $started = Activity::where('subject_type', User::class)->where('subject_id', $this->target->id)
            ->where('description', 'like', 'Impersonation started%')->first();
        $this->assertNotNull($started);
        $this->assertSame($this->admin->id, (int) $started->causer_id);
        $this->assertSame($this->admin->id, $started->properties['impersonator_id']);
        $this->assertSame('user-impersonation-started', $started->properties['activity_type']);

        $this->get('/impersonate/leave')->assertRedirect();
        $this->assertAuthenticatedAs($this->admin, 'web');

        $ended = Activity::where('subject_type', User::class)->where('subject_id', $this->target->id)
            ->where('description', 'like', 'Impersonation ended%')->first();
        $this->assertNotNull($ended);
        $this->assertSame($this->admin->id, (int) $ended->causer_id);
    }

    #[Test]
    public function the_swap_is_not_recorded_as_a_login_or_logout_by_the_target()
    {
        $this->actingAs($this->admin, 'web')->get('/impersonate/take/'.$this->target->id);
        $this->get('/impersonate/leave');

        $this->assertDatabaseMissing('activity_log', ['subject_type' => User::class, 'subject_id' => $this->target->id, 'description' => 'Logged in.']);
        $this->assertDatabaseMissing('activity_log', ['subject_type' => User::class, 'subject_id' => $this->target->id, 'description' => 'Logged out.']);
    }

    #[Test]
    public function non_admins_cannot_impersonate()
    {
        $plain = User::factory()->create();

        $this->actingAs($plain, 'web')->get('/impersonate/take/'.$this->target->id)->assertStatus(403);
        $this->assertDatabaseMissing('activity_log', ['subject_id' => $this->target->id, 'subject_type' => User::class]);
    }
}
