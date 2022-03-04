<?php

namespace Tests\Feature\End2End\ExpertPanels\LogEntries;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Actions\LogEntryAdd;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateLogEntryTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->user = $this->setupUser();
        $this->expertPanel = ExpertPanel::factory()->create();
        $this->baseUrl = '/api/applications/'.$this->expertPanel->uuid.'/log-entries';

        app()->make(LogEntryAdd::class)->handle($this->expertPanel->uuid, '2020-01-01', 'test test test');
        $this->logEntry = $this->expertPanel->group->fresh()->latestLogEntry;
    }
    

    /**
     * @test
     */
    public function updates_log_entry()
    {
        Sanctum::actingAs($this->user);
        $response = $this->json(
            'PUT',
            '/api/applications/'.$this->expertPanel->uuid.'/log-entries/'.$this->logEntry->id,
            [
                'entry' => 'puppies are cute',
                'log_date' => $this->logEntry->created_at->toIsoString(),
                'step' => $this->logEntry->step
            ]
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
            'id' => (string)$this->expertPanel->fresh()->group->latestLogEntry->id,
            'description' => 'puppies are cute',
            'created_at' => $this->logEntry->created_at->format('Y-m-d H:i:s'),
            'properties->entry' => 'puppies are cute',
            'properties->log_date' => $this->logEntry->created_at->toIsoString(),
            'properties->step' => $this->logEntry->step
        ]);
    }
}
