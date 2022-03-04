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
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\ExpertPanel\Actions\NextActionCreate;
use App\Modules\ExpertPanel\Actions\ExpertPanelCreate;
use App\Modules\ExpertPanel\Actions\ApplicationDocumentAdd;
use Database\Seeders\NextActionAssigneesTableSeeder;

class ExpertPanelDetailTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->runSeeder(NextActionAssigneesTableSeeder::class);

        $this->user = User::factory()->create();
        $this->uuid = Uuid::uuid4()->toString();

        $this->parentGroup = Group::factory()->create();
        $this->ep = ExpertPanelCreate::run(
            uuid: $this->uuid,
            working_name: 'test name',
            cdwg_id: $this->parentGroup->id,
            expert_panel_type_id: 2,
            date_initiated: Carbon::parse('2020-01-01')
        );

        ApplicationDocumentAdd::run(
            expertPanelUuid: $this->uuid,
            uuid: Uuid::uuid4()->toString(),
            filename: uniqid().'test.tst',
            storage_path: '/tmp/'.uniqid().'.tst',
            document_type_id: 1,
            step: 1,
            date_received: '2020-01-01'
        );
        NextActionCreate::run(
            expertPanelUuid: $this->uuid,
            uuid: Uuid::uuid4()->toString(),
            entry: 'TEst me',
            dateCreated: '2020-01-01'
        );
        LogEntryAdd::run(
            expertPanelUuid: $this->uuid,
            entry: 'TEst me',
            logDate: Carbon::now()->addDays(1)->toJson()
        );

        $person = Person::factory()->create();

        ContactAdd::run(
            expertPanelUuid: $this->uuid,
            uuid: $person->uuid,
        );

        $this->expertPanel = ExpertPanel::findByUuidOrFail($this->uuid);
    }

    /**
     * @test
     */
    public function gets_application_with_uuid()
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $this->json('get', '/api/applications/'.$this->uuid)
            ->assertStatus(200)
            ->assertJsonFragment(['working_name' => $this->expertPanel->group->name])
            ->assertJsonFragment(['uuid' => $this->uuid]);
    }

    /**
     * @test
     */
    public function loads_latestLogEntry_by_default()
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $response = $this->json('get', '/api/applications/'.$this->uuid)
            ->assertStatus(200);
        $response->assertJsonFragment(['description' => 'TEst me']);
    }

    /**
     * @test
     */
    public function loads_latestPendingNextAction_by_default()
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $this->json('get', '/api/applications/'.$this->uuid)
            ->assertStatus(200)
            ->assertJsonFragment(['entry' => 'TEst me']);
    }
}
