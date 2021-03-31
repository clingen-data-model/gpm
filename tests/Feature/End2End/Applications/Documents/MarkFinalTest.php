<?php

namespace Tests\Feature\End2End\Applications\Documents;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Document;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Application\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

class MarkFinalTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->user = User::factory()->create();
        $this->application = Application::factory()->create();
        $this->document = Document::factory()->make();
        $this->application->documents()->save($this->document);
        $this->docUrl = '/api/applications/'.$this->application->uuid.'/documents/'.$this->document->uuid.'/final';
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
                'is_final' => "1"
            ]);
    }

    /**
     * @test
     */
    public function marks_any_previous_final_documents_for_same_application_and_type_as_not_final()
    {
        Sanctum::actingAs($this->user);
        $document = $this->application->documents()->save(Document::factory()->make(['is_final'=>1, 'document_type_id' => $this->document->document_type_id]));

        $this->call('POST', $this->docUrl)
            ->assertStatus(200)
            ->assertJsonFragment([
                'is_final' => "1"
            ]);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'is_final' => 0
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
                'is_final' => "1"
            ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => 'App\Modules\Application\Models\Application',
            'subject_id' => $this->application->id,
            'description' => $this->document->type->name.' version '.$this->document->version.' marked final.'
        ]);
    }
    
    
    
}
