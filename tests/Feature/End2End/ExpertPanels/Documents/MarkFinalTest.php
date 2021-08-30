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
use Illuminate\Support\Facades\Bus;

/**
 * @group documents
 */
class MarkFinalTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->user = User::factory()->create();
        $this->expertPanel = ExpertPanel::factory()->create();
        $this->document = Document::factory()->make();
        $this->expertPanel->documents()->save($this->document);
        $this->docUrl = '/api/applications/'.$this->expertPanel->uuid.'/documents/'.$this->document->uuid.'/final';
        Carbon::setTestNow('2021-02-01');
    }

    /**
     * @test
     */
    public function marks_a_document_final()
    {
        Sanctum::actingAs($this->user);
        $this->call('POST', $this->docUrl)
            ->assertStatus(200)
            ->assertJsonFragment([
                'is_final' => 1
            ]);
    }

    /**
     * @test
     */
    public function marks_any_previous_final_documents_for_same_application_and_type_as_not_final()
    {
        Sanctum::actingAs($this->user);
        $d = Document::factory()->make(['metadata'=>['is_final' => 1], 'document_type_id' => $this->document->document_type_id]);
        $document = $this->expertPanel->documents()->save($d);

        $this->call('POST', $this->docUrl)
            ->assertStatus(200)
            ->assertJsonFragment([
                'is_final' => 1
            ]);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'metadata->is_final' => 0
        ]);
    }

    /**
     * @test
     */
    public function event_recorded_when_marked_final()
    {
        Sanctum::actingAs($this->user);
        $this->call('POST', $this->docUrl)
            ->assertStatus(200)
            ->assertJsonFragment([
                'is_final' => 1
            ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => 'App\Modules\ExpertPanel\Models\ExpertPanel',
            'subject_id' => $this->expertPanel->id,
            'description' => $this->document->type->name.' version '.$this->document->version.' marked final.'
        ]);
    }
}
