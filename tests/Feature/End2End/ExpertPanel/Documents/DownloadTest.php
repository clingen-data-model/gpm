<?php

namespace Tests\Feature\End2End\ExpertPanels\Documents;

use Tests\TestCase;
use Ramsey\Uuid\Uuid;
use App\Models\Document;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\CreatesDocumentUploadRequestData;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\ExpertPanel\Jobs\AddApplicationDocument;

/**
 * @group documents
 */
class DownloadTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDocumentUploadRequestData;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->application = ExpertPanel::factory()->create();
        $this->doc = Document::factory()->make();

        $job = new AddApplicationDocument(
            applicationUuid: $this->application->uuid,
            uuid: $this->doc->uuid,
            filename: 'test.docx',
            storage_path: 'documents/test_download.docx',
            document_type_id: 1
        );
        Bus::dispatch($job);
    }

    // public function tearDown():void
    // {
    //     parent::tearDown();
    //     unlink(storage_path('app/documents/test_download.docx'));
    // }

    /**
     * @test
     */
    public function guest_cannot_download_a_file()
    {
        $this->json('GET', '/documents/'.$this->doc->uuid)
            ->assertStatus(401);
    }

    /**
     * @test
     */
    public function responds_with_404_if_document_not_found()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $url = '/documents/'.Uuid::uuid4()->toString();
        $response = $this->json('GET', $url);
        $response->assertStatus(404);
    }

    /**
     * @test
     */
    public function responds_with_404_if_file_not_found()
    {
        $this->doc->storage_path = 'beans.txt';
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $response = $this->json('GET', '/documents/'.$this->doc->uuid)
                        ->assertStatus(404);
    }
    
    /**
     * @test
     */
    public function authed_user_can_download_a_file()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);


        $data = $this->makeDocumentUploadRequestData(filename: 'test_download.docx');

        \Laravel\Sanctum\Sanctum::actingAs($user);
        $response = $this->json('POST', '/api/applications/'.$this->application->uuid.'/documents', $data);
        
        $response = $this->json('GET', '/documents/'.$data['uuid']);
        $response->assertStatus(200);

        $content = $response->streamedContent();
    }
}
