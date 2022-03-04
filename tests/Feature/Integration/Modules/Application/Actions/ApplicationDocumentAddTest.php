<?php

namespace Tests\Feature\Integration\Modules\Application\Actions;

use Tests\TestCase;
use App\Models\Document;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\ExpertPanel\Actions\ApplicationDocumentAdd;
use Carbon\Carbon;

class ApplicationDocumentAddTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->expertPanel = ExpertPanel::factory()->create();
        $this->document = Document::factory()->make(['document_type_id'=>config('documents.types.scope.id')]);
    }
    

    /**
     * @test
     */
    public function sets_version_based_existing_versions()
    {
        ApplicationDocumentAdd::run(
            expertPanelUuid: $this->expertPanel->uuid,
            uuid: $this->document->uuid,
            filename: $this->document->filename,
            storage_path: $this->document->storage_path,
            document_type_id: $this->document->document_type_id,
        );

        $this->assertEquals($this->expertPanel->group->documents->first()->version, 1);

        $document2 = Document::factory()->make(['document_type_id' => 1]);

        (new ApplicationDocumentAdd)->handle(
            expertPanelUuid: $this->expertPanel->uuid,
            uuid: $document2->uuid,
            filename: $document2->filename,
            storage_path: $document2->storage_path,
            document_type_id: $document2->document_type_id,
        );

        $this->assertEquals($this->expertPanel->fresh()->group->documents()->count(), 2);
    }
    
    /**
     * @test
     */
    public function sets_step_1_received_date_if_v1_and_scope_document_type()
    {
        Carbon::setTestNow('2021-08-30');
        ApplicationDocumentAdd::run(
            expertPanelUuid: $this->expertPanel->uuid,
            uuid: $this->document->uuid,
            filename: $this->document->filename,
            storage_path: $this->document->storage_path,
            document_type_id: $this->document->document_type_id,
        );
    
        $this->assertDatabaseHas('expert_panels', [
            'uuid' => $this->expertPanel->uuid,
            'step_1_received_date' => Carbon::now()->format('Y-m-d H:i:s')
        ]);
    }


    /**
     * @test
     */
    public function sets_step_4_received_date_if_v1_and_scope_document_type()
    {
        Carbon::setTestNow('2021-08-30');
        $this->document->document_type_id = config('documents.types.final-app.id');
        ApplicationDocumentAdd::run(
            expertPanelUuid: $this->expertPanel->uuid,
            uuid: $this->document->uuid,
            filename: $this->document->filename,
            storage_path: $this->document->storage_path,
            document_type_id: $this->document->document_type_id,
        );
    
        $this->assertDatabaseHas('expert_panels', [
            'uuid' => $this->expertPanel->uuid,
            'step_4_received_date' => Carbon::now()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * @test
     */
    public function does_not_set_step_1_received_date_if_version_not_1()
    {
        Carbon::setTestNow('2021-01-01');

        // Add Version 1
        ApplicationDocumentAdd::run(
            expertPanelUuid: $this->expertPanel->uuid,
            uuid: $this->document->uuid,
            filename: $this->document->filename,
            storage_path: $this->document->storage_path,
            document_type_id: $this->document->document_type_id,
        );

        // Add Version 2
        $doc2 = Document::factory()->make(['document_type_id'=>config('documents.types.scope.id')]);
        ApplicationDocumentAdd::run(
            expertPanelUuid: $this->expertPanel->uuid,
            uuid: $doc2->uuid,
            filename: $doc2->filename,
            storage_path: $doc2->storage_path,
            document_type_id: $doc2->document_type_id,
        );

        Carbon::setTestNow('2021-08-30');

        $this->assertDatabaseHas('expert_panels', [
            'uuid' => $this->expertPanel->uuid,
            'step_1_received_date' => '2021-01-01 00:00:00'
        ]);
    }
}
