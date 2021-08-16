<?php

namespace Tests\Feature\Integration\Modules\Application\Actions;

use Tests\TestCase;
use App\Models\Document;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\ExpertPanel\Actions\ApplicationDocumentAdd;

class ApplicationDocumentAddTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->expertPanel = ExpertPanel::factory()->create();
        $this->document = Document::factory()->make(['document_type_id'=>config('documents.types.scope.id')]);
    }
    

    /**
     * @test
     */
    public function sets_version_based_existing_versions()
    {
        (new ApplicationDocumentAdd)->handle(
            expertPanelUuid: $this->expertPanel->uuid,
            uuid: $this->document->uuid,
            filename: $this->document->filename,
            storage_path: $this->document->storage_path,
            document_type_id: $this->document->document_type_id,
        );

        $this->assertEquals($this->expertPanel->documents->first()->version, 1);

        $document2 = Document::factory()->make(['document_type_id' => 1]);

        (new ApplicationDocumentAdd)->handle(
            expertPanelUuid: $this->expertPanel->uuid,
            uuid: $document2->uuid,
            filename: $document2->filename,
            storage_path: $document2->storage_path,
            document_type_id: $document2->document_type_id,
        );

        $this->assertEquals($this->expertPanel->fresh()->documents()->count(), 2);
    }
}
