<?php

namespace Tests\Feature\Integration\Modules\Application\Jobs;

use Illuminate\Support\Carbon;
use Tests\TestCase;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Event;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Application\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Application\Jobs\AddApplicationDocument;

class AddApplicationDocumentTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->dispatcher = app()->make(Dispatcher::class);
        $this->application = Application::factory()->create();
    }

    /**
     * @test
     */
    public function adds_v1_of_document()
    {
        $docUuid = Uuid::uuid4();
        Carbon::setTestNow('2021-01-01');

        $job = new AddApplicationDocument(
            applicationUuid: $this->application->uuid,
            filename: 'testfile.doc',
            storage_path: $this->faker->file(base_path('tests/files')),
            document_category_id: 1,
            step: 1,
            uuid: $docUuid
        );


        $this->dispatcher->dispatch($job);
        
        $this->assertDatabaseHas('documents', [
            'uuid' => $docUuid,
            'document_category_id' => 1,
            'step' => 1,
            'date_received' => Carbon::now()->format('Y-m-d H:i:s'),
            'date_reviewed' => null,
            'version' => 1,
            'application_id' => $this->application->id
        ]);

    }    
    
}
