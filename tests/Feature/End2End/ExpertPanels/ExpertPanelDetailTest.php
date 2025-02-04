<?php

namespace Tests\Feature\End2End\ExpertPanels;

use Tests\TestCase;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Carbon;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Actions\ContactAdd;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Actions\LogEntryAdd;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use App\Modules\ExpertPanel\Actions\NextActionCreate;
use App\Modules\ExpertPanel\Actions\ExpertPanelCreate;
use App\Modules\ExpertPanel\Actions\ApplicationDocumentAdd;
use Database\Seeders\NextActionAssigneesTableSeeder;

class ExpertPanelDetailTest extends TestCase
{
    use FastRefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->runSeeder(NextActionAssigneesTableSeeder::class);

        $this->user = $this->setupUser();
        $this->ep = ExpertPanel::factory()->vcep()->create();

        ApplicationDocumentAdd::run(
            expertPanelUuid: $this->ep->uuid,
            uuid: Uuid::uuid4()->toString(),
            filename: uniqid().'test.tst',
            storage_path: '/tmp/'.uniqid().'.tst',
            document_type_id: 1,
            step: 1,
            date_received: '2020-01-01'
        );
        NextActionCreate::run(
            expertPanel: $this->ep,
            uuid: Uuid::uuid4()->toString(),
            entry: 'TEst me',
            dateCreated: '2020-01-01'
        );
        LogEntryAdd::run(
            expertPanelUuid: $this->ep->uuid,
            entry: 'TEst me',
            logDate: Carbon::now()->addDays(1)->toJson()
        );

        $person = Person::factory()->create();

        ContactAdd::run(
            expertPanelUuid: $this->ep->uuid,
            uuid: $person->uuid,
        );
    }

    /**
     * @test
     */
    public function gets_application_with_uuid()
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $this->json('get', '/api/applications/'.$this->ep->uuid)
            ->assertStatus(200)
            ->assertJsonFragment(['uuid' => $this->ep->uuid]);
    }

    /**
     * @test
     */
    public function loads_latestLogEntry_by_default()
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $response = $this->json('get', '/api/applications/'.$this->ep->uuid)
            ->assertStatus(200);
        $response->assertJsonFragment(['description' => 'TEst me']);
    }

    /**
     * @test
     */
    public function loads_latestPendingNextAction_by_default()
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $this->json('get', '/api/applications/'.$this->ep->uuid)
            ->assertStatus(200)
            ->assertJsonFragment(['entry' => 'TEst me']);
    }
}
