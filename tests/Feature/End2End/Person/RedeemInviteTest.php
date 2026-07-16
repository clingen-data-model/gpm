<?php

namespace Tests\Feature\End2End\Person;

use Mockery;
use Carbon\Carbon;
use Tests\TestCase;
use App\Modules\User\Models\User;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Models\Person;
use App\Services\Clerk\ClerkTokenVerifier;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class RedeemInviteTest extends TestCase
{
    use RefreshDatabase;

    const URL = '/api/people/invites';
    public function setup():void
    {
        parent::setup();

        $this->invite = Invite::factory()->create(['redeemed_at' => null]);
        $this->email = 'test@test.com';
        // Sanctum only starts a session (needed for Auth::login()) for
        // requests it recognises as coming from the SPA.
        $this->withHeader('Referer', 'http://localhost:8013');
    }

    #[Test]
    public function can_check_if_code_is_valid()
    {
        $this->json('GET', static::URL.'/'.$this->invite->code)
            ->assertStatus(200);

        $this->json('GET', static::URL.'/gobbledy-guk')
            ->assertStatus(404);
    }

    #[Test]
    public function returns_404_if_invite_not_found_by_code()
    {
        $this->fakeVerifier(['sub' => 'clerk_1', 'email' => $this->email]);

        $this->json('PUT', static::URL.'/gobbeldy-guk', [], ['Authorization' => 'Bearer test-token'])
            ->assertStatus(404);
    }

    #[Test]
    public function requires_a_valid_clerk_session()
    {
        $this->fakeVerifier(null);

        $this->json('PUT', static::URL.'/'.$this->invite->code, [], ['Authorization' => 'Bearer bad-token'])
            ->assertStatus(401);
    }

    #[Test]
    public function sets_redeemed_at_date_for_invite()
    {
        Carbon::setTestNow('2021-09-16');
        $this->fakeVerifier(['sub' => 'clerk_1', 'email' => $this->email]);

        $this->json('PUT', static::URL.'/'.$this->invite->code, [], ['Authorization' => 'Bearer test-token'])
            ->assertStatus(200);

        $this->assertDatabaseHas('invites', [
            'code' => $this->invite->code,
            'redeemed_at' => Carbon::now()->format('Y-m-d H:i:s')
        ]);
    }

    #[Test]
    public function user_created_and_linked_to_invited_person()
    {
        $this->fakeVerifier(['sub' => 'clerk_1', 'email' => $this->email]);

        $this->json('PUT', static::URL.'/'.$this->invite->code, [], ['Authorization' => 'Bearer test-token'])
            ->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'email' => $this->email,
            'clerk_id' => 'clerk_1',
        ]);
        $user = User::findByEmail($this->email);

        $this->assertDatabaseHas('people', [
            'id' => $this->invite->person_id,
            'user_id' => $user->id
        ]);
    }

    #[Test]
    public function an_existing_user_is_linked_by_email_instead_of_recreated()
    {
        $existing = User::factory()->create(['email' => $this->email, 'clerk_id' => null]);
        $this->fakeVerifier(['sub' => 'clerk_1', 'email' => $this->email]);

        $this->json('PUT', static::URL.'/'.$this->invite->code, [], ['Authorization' => 'Bearer test-token'])
            ->assertStatus(200);

        $this->assertSame('clerk_1', $existing->fresh()->clerk_id);
        $this->assertDatabaseHas('people', [
            'id' => $this->invite->person_id,
            'user_id' => $existing->id,
        ]);
    }

    #[Test]
    public function it_establishes_a_gpm_session_on_success()
    {
        $this->fakeVerifier(['sub' => 'clerk_1', 'email' => $this->email]);

        $this->json('PUT', static::URL.'/'.$this->invite->code, [], ['Authorization' => 'Bearer test-token'])
            ->assertStatus(200);

        $this->assertAuthenticatedAs(User::findByEmail($this->email));
    }

    #[Test]
    public function logs_invite_redemption_activity()
    {
        $this->fakeVerifier(['sub' => 'clerk_1', 'email' => $this->email]);

        $this->json('PUT', static::URL.'/'.$this->invite->code, [], ['Authorization' => 'Bearer test-token'])
            ->assertStatus(200);

        $user = User::orderBy('id', 'desc')->firstOrFail();
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Person::class,
            'subject_id' => $this->invite->person_id,
            'activity_type' => 'invite-redeemed',
            'properties->user->id' => $user->id,
            'properties->user->email' => $this->email,
        ]);
    }

    private function fakeVerifier(?array $claims): void
    {
        $verifier = Mockery::mock(ClerkTokenVerifier::class);
        $verifier->shouldReceive('verify')->andReturn($claims);
        $this->app->instance(ClerkTokenVerifier::class, $verifier);
    }
}
