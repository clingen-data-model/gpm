<?php

namespace Tests\Feature\End2End\ExpertPanels\LogEntries;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Actions\StepApprove;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Actions\LogEntryAdd;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

/**
 * @group applications
 * @group log-entries
 */
class DeleteLogEntryTest extends TestCase
{
    use FastRefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->user = $this->setupUser();
        $this->expertPanel = ExpertPanel::factory()->create();
        $this->baseUrl = '/api/applications/'.$this->expertPanel->uuid.'/log-entries';

        app()->make(StepApprove::class)->handle(
            expertPanel: $this->expertPanel,
            dateApproved: '2020-01-01',
            notifyContacts: false
        );
        $this->autoEntry = $this->expertPanel->group->fresh()->latestLogEntry;

        app()->make(LogEntryAdd::class)->handle($this->expertPanel->uuid, '2021-01-01', 'test test test');
        $this->logEntry = $this->expertPanel->group->logEntries->last();
    }

    /**
     * @test
     */
    public function prevents_deleting_typed_log_entries()
    {
        Sanctum::actingAs($this->user);
        $response = $this->json(
            'delete',
            '/api/applications/'.$this->expertPanel->uuid.'/log-entries/'.$this->autoEntry->id
        );

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
        $response = $this->json('delete', '/api/applications/'.$this->expertPanel->uuid.'/log-entries/'.$this->logEntry->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('activity_log', [
            'id' => $this->logEntry->id
        ]);
    }
}
