<?php

namespace Tests\Feature\End2End\ExpertPanels\Documents;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Document;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

/**
 * @group documents
 */
class UpdateDocumentInfoTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
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
    public function it_updates_info_for_a_document()
    {
        $this->json('PUT', $this->docUrl, [
            'date_received' => Carbon::now(),
            'notes' => 'This is a note!'
        ])
        ->assertStatus(200)
        ->assertJsonFragment([
            'date_received' => Carbon::now()->toISOString(),
            'notes' => 'This is a note!'
        ]);
    }

    /**
     * @test
     */
    public function it_validates_required_data()
    {
        $this->json('PUT', $this->docUrl, [])
            ->assertStatus(422)
            ->assertJsonFragment([
                'date_received' => ['This is required.']
            ]);
    }
}
