<?php

namespace Tests\Feature\Integration\Domain\Application\Jobs;

use Carbon\Carbon;
use Tests\TestCase;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Event;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Testing\WithFaker;
use App\Domain\Application\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domain\Application\Jobs\AddApplicationDocument;

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
            application: $this->application,
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
