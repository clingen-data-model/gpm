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
use App\Modules\Group\Actions\MemberAdd;
use Tests\CreatesDocumentUploadRequestData;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use App\Modules\ExpertPanel\Actions\ApplicationDocumentAdd;

/**
 * @group documents
 */
class DownloadTest extends TestCase
{
    use FastRefreshDatabase;
    use CreatesDocumentUploadRequestData;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->expertPanel = ExpertPanel::factory()->create();
        $this->doc = Document::factory()->make();

        $this->user = $this->setupUserWithPerson();
        app()->make(MemberAdd::class)->handle($this->expertPanel->group, $this->user->person);
        Sanctum::actingAs($this->user);

        (new ApplicationDocumentAdd)->handle(
            expertPanelUuid: $this->expertPanel->uuid,
            uuid: $this->doc->uuid,
            filename: 'test.docx',
            storage_path: 'documents/test_download.docx',
            document_type_id: 1,
            step: 1
        );
    }

    /**
     * @test
     */
    public function responds_with_404_if_document_not_found()
    {
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
        $response = $this->json('GET', '/documents/'.$this->doc->uuid)
                        ->assertStatus(404);
    }
    
    /**
     * @test
     */
    public function authed_user_can_download_a_file()
    {
        $data = $this->makeDocumentUploadRequestData(filename: 'test_download.docx');

        $response = $this->json('POST', '/api/applications/'.$this->expertPanel->uuid.'/documents', $data);
        
        $response = $this->json('GET', '/documents/'.$data['uuid']);
        $response->assertStatus(200);

        $content = $response->streamedContent();
    }
}
