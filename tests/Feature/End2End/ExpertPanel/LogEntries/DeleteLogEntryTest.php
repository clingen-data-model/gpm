<?php

namespace Tests\Feature\End2End\ExpertPanels\LogEntries;

use Tests\TestCase;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Jobs\AddLogEntry;
use App\Modules\ExpertPanel\Jobs\ApproveStep;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class DeleteLogEntryTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->user = User::factory()->create();
        $this->application = ExpertPanel::factory()->create();
        $this->baseUrl = '/api/applications/'.$this->application->uuid.'/log-entries';

        Bus::dispatch(new ApproveStep(
            applicationUuid: $this->application->uuid, 
            dateApproved: '2020-01-01', 
            notifyContacts: false
        ));
        $this->autoEntry = $this->application->fresh()->latestLogEntry;
        Bus::dispatch(new AddLogEntry($this->application->uuid, '2021-01-01', 'test test test'));
        $this->logEntry = $this->application->logEntries->last();
    }

    /**
     * @test
     */
    public function prevents_deleting_typed_log_entries()
    {
        Sanctum::actingAs($this->user);
        $response = $this->json('delete', '/api/applications/'.$this->application->uuid.'/log-entries/'.$this->autoEntry->id);

        $response->assertStatus(422)
            ->assertJson([
                'errors' => [
                    'activity_type' => ['Only manual log entries can be deleted.']
                ]
            ]);
    }

    /**
     * @test
     */
    public function deletes_manual_log_entries()
    {
        Sanctum::actingAs($this->user);
        $response = $this->json('delete', '/api/applications/'.$this->application->uuid.'/log-entries/'.$this->logEntry->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('activity_log', [
            'id' => $this->logEntry->id
        ]);
    }
    

}
