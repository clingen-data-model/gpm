<?php

namespace Tests\Feature\End2End\Applications\LogEntries;

use Tests\TestCase;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Application\Jobs\AddLogEntry;
use App\Modules\Application\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class UpdateLogEntryTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->user = User::factory()->create();
        $this->application = Application::factory()->create();
        $this->baseUrl = '/api/applications/'.$this->application->uuid.'/log-entries';

        Bus::dispatch(new AddLogEntry($this->application->uuid, '2020-01-01', 'test test test'));
        $this->logEntry = $this->application->fresh()->latestLogEntry;
    }
    

    /**
     * @test
     */
    public function updates_log_entry()
    {
        Sanctum::actingAs($this->user);
        $response = $this->json(
            'PUT',
            '/api/applications/'.$this->application->uuid.'/log-entries/'.$this->logEntry->id,
            [
                'entry' => 'puppies are cute',
                'log_date' => $this->logEntry->created_at->toIsoString(),
                'step' => $this->logEntry->step
            ]
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
            'id' => (string)$this->application->fresh()->latestLogEntry->id,
            'description' => 'puppies are cute',
            'created_at' => $this->logEntry->created_at->format('Y-m-d H:i:s'),
            'properties->entry' => 'puppies are cute',
            'properties->log_date' => $this->logEntry->created_at->toIsoString(),
            'properties->step' => $this->logEntry->step
        ]);
    }
}
