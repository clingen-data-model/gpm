<?php

namespace Tests\Feature\End2End\Applications\Documents;

use Illuminate\Support\Carbon;
use Tests\TestCase;
use App\Models\User;
use Ramsey\Uuid\Uuid;
use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\WithFaker;
use App\Domain\Application\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UploadTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();    

        $this->user = User::factory()->create();
        $this->application = Application::factory()->create();
    }

    /**
     * @test
     */
    public function can_upload_step_1_document_to_application()
    {
        Carbon::setTestNow('2021-01-01');

        $data = $this->makeRequestData();

        $response = $this->actingAs($this->user)
            ->json('POST', '/api/applications/'.$this->application->uuid.'/documents', $data);

        $response->assertStatus(200);
        $response->assertJson([
            'uuid' => $data['uuid'],
            'filename' => 'Test Scope Document.docx',
            'document_category_id' => config('documents.categories.scope.id'),
            'date_received' => Carbon::now()->toJson(),
            'date_reviewed' => null,
            'metadata' => null,
            'version' => 1,
            'application_id' => $this->application->id
        ]);

        Storage::disk()->assertExists('documents/'.$data['file']->hashName());
    }

    /**
     * @test
     */
    public function sets__date_received_and_date_reviewed_if_provided()
    {
        Carbon::setTestNow('2021-01-01');
        $data = $this->makeRequestData(
            dateReceived: Carbon::parse('2020-10-28'), 
            dateReviewed: Carbon::parse('2020-11-14')
        );

        $response = $this->actingAs($this->user)
            ->json('POST', '/api/applications/'.$this->application->uuid.'/documents', $data);

        $response->assertStatus(200);
        $response->assertJson([
            'uuid' => $data['uuid'],
            'filename' => 'Test Scope Document.docx',
            'document_category_id' => config('documents.categories.scope.id'),
            'date_received' => Carbon::parse('2020-10-28')->toJson(),
            'date_reviewed' => Carbon::parse('2020-11-14')->toJson(),
            'metadata' => null,
            'version' => 1,
            'application_id' => $this->application->id
        ]);
    }
    
    /**
     * @test
     */
    public function sets_version_based_existing_versions()
    {
        $this->application->addDocument(Document::factory()->make(['document_category_id' => 1]));

        $data = $this->makeRequestData();
        $response = $this->actingAs($this->user, 'api')
            ->json('POST', '/api/applications/'.$this->application->uuid.'/documents', $data);

            $response->assertStatus(200);
            $response->assertJson([
                'uuid' => $data['uuid'],
                'version' => 2,
                'application_id' => $this->application->id
            ]);        
    }

    /**
     * @test
     */
    public function validates_required_fields()
    {
        $response = $this->actingAs($this->user, 'api')
            ->json('POST', '/api/applications/'.$this->application->uuid.'/documents', [])
            ->assertStatus(422)
            ->assertJsonFragment([
                'uuid' => ['The uuid field is required.'],
                'file' => ['The file field is required.'],
                'document_category_id' => ['The document category id field is required.']
            ]);
    }
    
    /**
     * @test
     */
    public function validates_field_types()
    {
        $data = [
            'document_category_id' => 999,
            'uuid' => 'blah',
            'file' => 'beans',
            'date_received' => 'detach the head',
            'date_reviewed' => 'or destroy the brain'
        ];

        $response = $this->actingAs($this->user, 'api')
            ->json('POST', '/api/applications/'.$this->application->uuid.'/documents', $data)
            ->assertStatus(422)
            ->assertJsonFragment([
                'uuid' => ['The uuid must be a valid UUID.'],
                'file' => ['The file must be a file.'],
                'document_category_id' => ['The selected document category id is invalid.'],
                "date_received" => ["The date received is not a valid date."],
                'date_reviewed' => ['The date reviewed is not a valid date.'],
            ]);
    }
    
    private function makeRequestData($documentCategoryId = 1, $dateReceived = null, $dateReviewed = null, $step = null)
    {
        Storage::fake();
        $file = UploadedFile::fake()->create(name: 'Test Scope Document.docx', mimeType: 'docx');

        return [
            'uuid' => Uuid::uuid4()->toString(),
            'file' => $file,
            'document_category_id' => $documentCategoryId,
            'date_received' => $dateReceived,
            'date_reviewed' => $dateReviewed,
            'step' => $step
        ];
    }


    
    
    
    
}
