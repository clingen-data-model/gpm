<?php

namespace Tests\Feature\End2End\Appiclications\Documents;

use Illuminate\Support\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\Document;
use Illuminate\Foundation\Testing\WithFaker;
use App\Domain\Application\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MarkReviewedTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->user = User::factory()->create();
        $this->application = Application::factory()->create();
        $this->document = Document::factory()->make();
        $this->application->documents()->save($this->document);
        $this->docUrl = '/api/applications/'.$this->application->uuid.'/documents/'.$this->document->uuid;
        Carbon::setTestNow('2021-02-01');
    }

    /**
     * @test
     */
    public function responds_with_404_if_application_or_document_not_found()
    {
        $this->actingAs($this->user, 'api')
            ->json('POST', '/api/applications/bobs-yer-uncle/documents/'.$this->document->uuid.'/review', ['date_reviewed' => Carbon::today()])
            ->assertStatus(404);

        $this->actingAs($this->user, 'api')
            ->json('POST', '/api/applications/'.$this->application->uuid.'/documents/bobs-yer-uncle/review', ['date_reviewed' => Carbon::today()])
            ->assertStatus(404);
    }
    

    /**
     * @test
     */
    public function can_mark_existing_application_document_reviewed()
    {
        $this->actingAs($this->user, 'api')
            ->json('POST', $this->docUrl.'/review', ['date_reviewed' => Carbon::today()])
            ->assertStatus(200)
            ->assertJsonFragment([
                'uuid' => $this->document->uuid, 
                'date_reviewed'=>Carbon::today()->toJson()
            ]);
    }

    /**
     * @test
     */
    public function will_not_updated_date_reviewed_for_previously_reviewed_document()
    {
        $originalDate = Carbon::parse('2021-01-01');
        $this->document->update(['date_reviewed' => $originalDate]);

        $this->actingAs($this->user, 'api')
            ->json('POST', $this->docUrl.'/review', ['date_reviewed' => Carbon::today()])
            ->assertStatus(200)
            ->assertJsonFragment([
                'uuid' => $this->document->uuid, 
                'date_reviewed'=>$originalDate->toJson()
            ]);
    }

    /**
     * @test
     */
    public function validates_date_reviewed()
    {
        $this->actingAs($this->user, 'api')
            ->json('POST', $this->docUrl.'/review', [])
            ->assertStatus(422)
            ->assertJsonFragment([
                'date_reviewed' => ['The date reviewed field is required.'], 
            ]);

            $this->actingAs($this->user, 'api')
            ->json('POST', $this->docUrl.'/review', ['date_reviewed' => 'beansforlunch'])
            ->assertStatus(422)
            ->assertJsonFragment([
                'date_reviewed' => ['The date reviewed is not a valid date.'], 
            ]);
    }
    
    
    
    
}
