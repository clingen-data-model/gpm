<?php

namespace Tests\Feature\End2End\Applications\LogEntries;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Domain\Application\Models\Application;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AddLogEntryTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->user = User::factory()->create();
        $this->application = Application::factory()->create();
        $this->baseUrl = '/api/applications/'.$this->application->uuid.'/log-entries';

    }

    /**
     * @test
     */
    public function user_can_add_a_log_entry()
    {
        $this->actingAs($this->user, 'api')
            ->json('POST', $this->baseUrl, [
                'step' => $this->application->current_step,
                'created_at' => '2021-01-01',
                'description' => 'A log entry description',
            ])
            ->assertStatus(200)
            ->assertJson([
                'description' => 'A log entry description',
                'created_at' => Carbon::parse('2021-01-01')->toJson(),
                'causer' => [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email
                ],
                'properties' => [
                    'step' => $this->application->current_step
                ]
            ]);
    }

    /**
     * @test
     */
    public function validates_required_fields()
    {
        $this->actingAs($this->user, 'api')
            ->json('POST', $this->baseUrl, [])
            ->assertStatus(422)
            ->assertJsonFragment([
                'description' => ['The description field is required.'],
                'created_at' => ['The created at field is required.'],
            ]);
    }

    /**
     * @test
     */
    public function validates_field_types()
    {
        $this->actingAs($this->user, 'api')
            ->json('POST', $this->baseUrl, [
                'created_at' => 'bob\'s yer uncle',
                'step' => 'four'
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'step' => ['The step must be a number.'],
                'created_at' => ['The created at is not a valid date.'],
            ]);
    }
    
}
