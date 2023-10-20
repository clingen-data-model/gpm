<?php

namespace Tests\Feature\End2End\ExpertPanels\Documents;

use App\Models\Document;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * @group documents
 */
class DeleteDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function setup(): void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        $this->expertPanel = ExpertPanel::factory()->create();
        $this->document = Document::factory()->make();

        $this->expertPanel->group->documents()->save($this->document);
        $this->docUrl = '/api/applications/'.$this->expertPanel->uuid.'/documents/'.$this->document->uuid;
        Carbon::setTestNow('2021-02-01');
    }

    /**
     * @test
     */
    public function it_can_delete_a_document(): void
    {
        $this->json('DELETE', $this->docUrl)
            ->assertStatus(200);

        $this->assertDatabaseMissing('documents', [
            'uuid' => $this->document->uuid,
            'deleted_at' => null,
        ]);
    }

    /**
     * @test
     */
    public function it_logs_a_document_deleted_event(): void
    {
        $this->json('DELETE', $this->docUrl)
            ->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $this->expertPanel->group->id,
            'subject_type' => $this->expertPanel->group::class,
            'activity_type' => 'document-deleted',
            'properties->document_uuid' => $this->document->uuid,
        ]);
    }
}
