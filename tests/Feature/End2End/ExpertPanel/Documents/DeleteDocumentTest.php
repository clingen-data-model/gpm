<?php

namespace Tests\Feature\End2End\ExpertPanels\Documents;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Document;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group documents
 */
class DeleteDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        $this->application = ExpertPanel::factory()->create();
        $this->document = Document::factory()->make();
        $this->application->documents()->save($this->document);
        $this->docUrl = '/api/applications/'.$this->application->uuid.'/documents/'.$this->document->uuid;
        Carbon::setTestNow('2021-02-01');
    }
    
    /**
     * @test
     */
    public function it_can_delete_a_document()
    {
        $this->json('DELETE', $this->docUrl)
            ->assertStatus(200);

        $this->assertDatabaseMissing('documents', [
            'uuid' => $this->document->uuid,
            'deleted_at' => null
        ]);
    }

    /**
     * @test
     */
    public function it_logs_a_document_deleted_event()
    {
        $this->json('DELETE', $this->docUrl)
            ->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => (string)$this->application->id,
            'subject_type' => get_class($this->application),
            'activity_type' => 'document-deleted',
            'properties->document_uuid' => $this->document->uuid
        ]);
    }
}
