<?php

namespace Tests\Feature\End2End\ExpertPanels\Documents;

use Tests\TestCase;
use Ramsey\Uuid\Uuid;
use App\Models\Document;
use App\Modules\ExpertPanel\Actions\ApplicationDocumentAdd;
use Illuminate\Support\Carbon;
use App\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\CreatesDocumentUploadRequestData;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group documents
 */
class UploadTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDocumentUploadRequestData;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->user = User::factory()->create();
        $this->expertPanel = ExpertPanel::factory()->create();
    }

    /**
     * @test
     */
    public function can_upload_step_1_document_to_application()
    {
        Carbon::setTestNow('2021-01-01');

        $data = $this->makeDocumentUploadRequestData();

        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $response = $this->json('POST', '/api/applications/'.$this->expertPanel->uuid.'/documents', $data);

        $response->assertStatus(200);
        $response->assertJson([
            'uuid' => $data['uuid'],
            'filename' => 'Test Scope Document.docx',
            'document_type_id' => config('documents.types.scope.id'),
            'notes' => 'this is a note',
            'owner_id' => $this->expertPanel->group->id,
            'owner_type' => get_class($this->expertPanel->group),
            'metadata' => [
                'date_received' => Carbon::now()->toJson(),
                'version' => 1,
            ],
            'notes' => 'this is a test'
        ]);

        $doc = Document::findByUuid($data['uuid']);

        Storage::disk('local')->assertExists($doc->storage_path);
    }
    
    /**
     * @test
     */
    public function sets_version_based_existing_versions()
    {
        $document = Document::factory()->make(['document_type_id' => 1]);
        (new ApplicationDocumentAdd)->handle(
            $this->expertPanel->uuid,
            $document->uuid,
            $document->filename,
            $document->storage_path,
            $document->document_type_id
        );
        
        $data = $this->makeDocumentUploadRequestData();
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $response = $this->json('POST', '/api/applications/'.$this->expertPanel->uuid.'/documents', $data);

        $response->assertStatus(200);
        $response->assertJson([
                'uuid' => $data['uuid'],
                'version' => 2,
                'owner_id' => $this->expertPanel->group->id,
                'owner_type' => get_class($this->expertPanel->group)
            ]);
    }

    /**
     * @test
     */
    public function validates_required_fields()
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $response = $this->json('POST', '/api/applications/'.$this->expertPanel->uuid.'/documents', [])
            ->assertStatus(422)
            ->assertJsonFragment([
                'uuid' => ['This is required.'],
                'file' => ['This is required.'],
                'document_type_id' => ['This is required.']
            ]);
    }
    
    /**
     * @test
     */
    public function validates_field_types()
    {
        $data = [
            'document_type_id' => 999,
            'uuid' => 'blah',
            'file' => 'beans',
            'date_received' => 'detach the head',
        ];

        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $response = $this->json('POST', '/api/applications/'.$this->expertPanel->uuid.'/documents', $data)
            ->assertStatus(422)
            ->assertJsonFragment([
                'uuid' => ['The uuid must be a valid UUID.'],
                'file' => ['The file must be a file.'],
                'document_type_id' => ['The selection is invalid.'],
                "date_received" => ["The date received is not a valid date."],
            ]);
    }
    
    /**
     * @test
     */
    public function sets_is_final_based_on_input()
    {
        Carbon::setTestNow('2021-01-01');

        $data = $this->makeDocumentUploadRequestData();
        $data['is_final'] = true;

        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $response = $this->json('POST', '/api/applications/'.$this->expertPanel->uuid.'/documents', $data);

        $response->assertStatus(200);
        $response->assertJson([
            'uuid' => $data['uuid'],
            'is_final' => 1
        ]);
    }
}
