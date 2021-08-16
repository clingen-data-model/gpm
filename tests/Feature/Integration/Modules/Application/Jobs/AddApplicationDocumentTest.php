<?php

namespace Tests\Feature\Integration\Modules\Application\Jobs;

use Illuminate\Support\Carbon;
use Tests\TestCase;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Event;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\ExpertPanel\Actions\ApplicationDocumentAdd;

/**
 * @group applications
 * @group expert-panels
 * @group documents
 */
class ApplicationDocumentAddTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->dispatcher = app()->make(Dispatcher::class);
        $this->expertPanel = ExpertPanel::factory()->create();
    }

    /**
     * @test
     */
    public function adds_v1_of_document()
    {
        $docUuid = Uuid::uuid4();
        Carbon::setTestNow('2021-01-01');

        (new ApplicationDocumentAdd)->handle(
            expertPanelUuid: $this->expertPanel->uuid,
            filename: 'testfile.doc',
            storage_path: $this->faker->file(base_path('tests/files')),
            document_type_id: 1,
            step: 1,
            uuid: $docUuid
        );


        $this->assertDatabaseHas('documents', [
            'uuid' => $docUuid,
            'document_type_id' => 1,
            'step' => 1,
            'date_received' => Carbon::now()->format('Y-m-d H:i:s'),
            'version' => 1,
            'application_id' => $this->expertPanel->id,
            'is_final' => 0
        ]);
    }

    /**
     * @test
     */
    public function marks_document_version_final_if_specified()
    {
        $docUuid = Uuid::uuid4();
        Carbon::setTestNow('2021-01-01');

        (new ApplicationDocumentAdd)->handle(
            expertPanelUuid: $this->expertPanel->uuid,
            filename: 'testfile.doc',
            storage_path: $this->faker->file(base_path('tests/files')),
            document_type_id: 1,
            step: 1,
            uuid: $docUuid,
            is_final: true
        );

        $this->assertDatabaseHas('documents', [
            'uuid' => $docUuid,
            'document_type_id' => 1,
            'step' => 1,
            'date_received' => Carbon::now()->format('Y-m-d H:i:s'),
            'version' => 1,
            'application_id' => $this->expertPanel->id,
            'is_final' => 1
        ]);
    }
}
