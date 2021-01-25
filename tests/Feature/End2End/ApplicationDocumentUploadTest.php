<?php

namespace Tests\Feature\End2End;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\WithFaker;
use App\Domain\Application\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApplicationDocumentUploadTest extends TestCase
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
        Storage::fake();
        $file = UploadedFile::fake()->create(name: 'Test Scope Document', mimeType: 'docx');
        $document = Document::factory()->make();

        $data = [
            'uuid' => $document->uuid->toString(),
            'file' => $file,
            'document_category_id' => config('documents.categories.scope.id'),
            'date_received' => null,
            'date_reviewed' => null,
        ];

        $response = $this->actingAs($this->user)
            ->json('POST', '/api/applications/'.$this->application->uuid.'/documents', $data);

        $response->assertStatus(200);
        $response->assertJson([
            'uuid' => $document->uuid->toString(),
            'filename' => 'Test Scope Document',
            'document_category_id' => config('documents.categories.scope.id'),
            'date_received' => Carbon::now()->toJson(),
            'date_reviewed' => null,
            'metadata' => null,
            'version' => 1,
            'application_id' => $this->application->id
        ]);

        Storage::disk()->assertExists('documents/'.$file->hashName());
            
    }
    
    
}
