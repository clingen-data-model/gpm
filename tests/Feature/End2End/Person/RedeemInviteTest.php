<?php

namespace Tests\Feature\End2End\Person;

use Carbon\Carbon;
use Tests\TestCase;
use App\Modules\User\Models\User;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RedeemInviteTest extends TestCase
{
    use RefreshDatabase;

    const URL = '/api/people/invites';
    public function setup():void
    {
        parent::setup();

        $this->invite = Invite::factory()->create(['redeemed_at' => null]);
        $this->validData = [
            'email' => 'test@test.com',
            'password' => 'tester',
            'password_confirmation' => 'tester'
        ];
    }

    /**
     * @test
     */
    public function can_check_if_code_is_valid()
    {
        $this->json('GET', static::URL.'/'.$this->invite->code)
            ->assertStatus(200);

        $this->json('GET', static::URL.'/gobbledy-guk')
            ->assertStatus(404);
    }

    /**
     * @test
     */
    // public function can_check_if_validation_code_has_been_used()
    // {
    //     $this->invite->update(['redeemed_at' => '2021-09-15']);

    //     $this->json('GET', static::URL.'/'.$this->invite->code, $this->validData)
    //         ->assertStatus(422)
    //         ->assertJsonFragment([
    //             'code' => ['This invite has already been redeemed. Please log in to access your account.']
    //         ]);
    // }

    /**
     * @test
     */
    public function returns_404_if_invite_not_found_by_code()
    {
        $this->json('PUT', static::URL.'/gobbeldy-guk', $this->validData)
            ->assertStatus(404);
    }
    
    
    /**
     * @test
     */
    // public function validates_code_is_valid_before_redeeming()
    // {
    //     $this->invite->update(['redeemed_at' => '2021-09-15']);

    //     $this->json('PUT', static::URL.'/'.$this->invite->code, $this->validData)
    //         ->assertStatus(422)
    //         ->assertJsonFragment([
    //             'code' => ['This invite has already been redeemed. Please log in to access your account.']
    //         ]);
    // }

    /**
     * @test
     */
    public function validates_params()
    {
        $this->json('PUT', static::URL.'/'.$this->invite->code, ['email' => 'bob', 'password' => 'beans', 'password_confirmation' => 'farts'])
            ->assertStatus(422)
            ->assertJsonFragment([
                'email' => ['The email must be a valid email address.'],
                'password' => ['The password confirmation does not match.']
            ]);

        $user = User::factory()->create();
        $this->json('PUT', static::URL.'/'.$this->invite->code, ['email' => $user->email, 'password' => 'beans', 'password_confirmation' => 'farts'])
            ->assertStatus(422)
            ->assertJsonFragment([
                'email' => ['The email has already been taken.'],
            ]);
    }
    

    /**
     * @test
     */
    public function sets_redeemed_at_date_for_invite()
    {
        Carbon::setTestNow('2021-09-16');
        $this->json('PUT', static::URL.'/'.$this->invite->code, $this->validData)
            ->assertStatus(200);

        $this->assertDatabaseHas('invites', [
            'code' => $this->invite->code,
            'redeemed_at' => Carbon::now()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * @test
     */
    public function user_created_and_linked_to_invited_person()
    {
        $this->json('PUT', static::URL.'/'.$this->invite->code, $this->validData)
            ->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'email' => $this->validData['email'],
        ]);
        $user = User::findByEmail($this->validData['email']);

        $this->assertDatabaseHas('people', [
            'id' => $this->invite->person_id,
            'user_id' => $user->id
        ]);
    }
    

    /**
     * @test
     */
    public function logs_invite_redemption_activity()
    {
        $this->json('PUT', static::URL.'/'.$this->invite->code, $this->validData)
            ->assertStatus(200);

        $user = User::orderBy('id', 'desc')->firstOrFail();
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Person::class,
            'subject_id' => $this->invite->person_id,
            'activity_type' => 'invite-redeemed',
            'properties->user->id' => $user->id,
            'properties->user->email' => $this->validData['email'],
        ]);
    }
}
