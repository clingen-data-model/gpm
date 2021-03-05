<?php

namespace Tests\Feature\End2End\Applications\LogEntries;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Application\Models\Application;
use App\Models\User;
use Illuminate\Support\Carbon;
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
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
            $this->json('POST', $this->baseUrl, [
                'step' => $this->application->current_step,
                'log_date' => '2021-01-01',
                'entry' => 'A log entry description',
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
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
            $this->json('POST', $this->baseUrl, [])
            ->assertStatus(422)
            ->assertJsonFragment([
                'entry' => ['The entry field is required.'],
                'log_date' => ['The log date field is required.'],
            ]);
    }

    /**
     * @test
     */
    public function validates_field_types()
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
            $this->json('POST', $this->baseUrl, [
                'log_date' => 'bob\'s yer uncle',
                'step' => 'four'
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'step' => ['The step must be a number.'],
                'log_date' => ['The log date is not a valid date.'],
            ]);
    }
    
}
