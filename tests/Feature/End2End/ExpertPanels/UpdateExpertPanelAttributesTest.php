<?php

namespace Tests\Feature\End2End\ExpertPanels;

use Carbon\Carbon;
use Tests\TestCase;
use Ramsey\Uuid\Uuid;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use Illuminate\Testing\TestResponse;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateExpertPanelAttributesTest extends TestCase
{
    use RefreshDatabase;

    private $user, $cdwg, $longText;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        $this->cdwg = Group::factory()->cdwg()->create();

        $this->longText = "Sint nisi commodo nisi tempor adipisicing. Velit officia exercitation voluptate anim consequat eiusmod nisi officia consequat duis aute enim. Eu cupidatat nostrud dolore esse exercitation tempor anim magna ex eiusmod incididunt pariatur. Laboris est eu aup";
    }

    /**
     * @test
     */
    public function sets_ep_attributes()
    {
        $expertPanel = ExpertPanel::factory()->gcep()->create();

        $this->makeRequest($expertPanel)
            ->assertStatus(200)
            ->assertJsonFragment([

            ]);
    }

    /**
     * @test
     */
    public function will_not_update_other_non_ep_attributes()
    {
        $expertPanel = ExpertPanel::factory()->gcep()->create();
        $data = [
            'long_base_name' => 'Test Expert Panel Base Name',
            'short_base_name' => 'Test EP',
            'affiliation_id' => '40001',
            'cdwg_id' => $this->cdwg->id,
            'group' => [
                'name' => 'New Test Working Name',
            ]
        ];

        $nonEpData = [
            'uuid' => Uuid::uuid4()->toString(),
            'date_initiated' => '2019-01-01',
            'date_completed' => '2019-01-01',
            'step_1_approval_date' => '2019-01-01 00:00:00',
            'current_step' => 12,
        ];
        $dataWithUuid = array_merge($data, $nonEpData);

        $this->makeRequest($expertPanel, $dataWithUuid)
            ->assertStatus(200)
            ->assertJsonFragment([
                'uuid' => $expertPanel->uuid,
                'date_initiated' => $expertPanel->date_initiated->toJson(),
                'date_completed' => null,
                'current_step' => 1,
                'step_1_approval_date' => null
            ]);
    }

    /**
     * @test
     */
    public function validates_data_types()
    {
        $expertPanel = ExpertPanel::factory()->gcep()->create();
        $this->makeRequest($expertPanel, [
                'cdwg_id' => 999,
                'long_base_name' => $this->longText,
                'short_base_name' => 'more than sixteen',
                'affiliation_id' => '4000000001',
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'cdwg_id' => ['The selection is invalid.'],
                'long_base_name' => ['The long base name may not be greater than 255 characters.'],
                'short_base_name' => ['The short base name may not be greater than 15 characters.'],
                'affiliation_id' => ['The affiliation id must be 5 digits.'],
            ]);
    }

    /**
     * @test
     */
    public function validates_long_base_name_must_be_unique_for_type_if_not_null()
    {
        $nullLongName = ExpertPanel::factory()->gcep()->create();
        $existingGcep = ExpertPanel::factory()->gcep()->create(['long_base_name' => 'Early is a bad dog']);

        $expertPanel = ExpertPanel::factory()->gcep()->create();
        $this->makeRequest($expertPanel, [
            'cdwg_id' => 1,
            'long_base_name' => null,
            'short_base_name' => 'blah',
            'affiliation_id' => '40001',
        ])
        ->assertStatus(200);


        $this->makeRequest($expertPanel, [
                'cdwg_id' => 1,
                'long_base_name' => $existingGcep->long_base_name,
                'short_base_name' => 'blah',
                'affiliation_id' => '40001',
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'long_base_name' => ['The long base name has already been taken.'],
            ]);

        $expertPanel->update(['long_base_name' => 'Bird']);
        $this->makeRequest($expertPanel, [
            'cdwg_id' => 1,
            'long_base_name' => 'Bird',
            'short_base_name' => 'blah',
            'affiliation_id' => '40001',
        ])
        ->assertStatus(200);
    }

    /**
     * @test
     */
    public function validates_short_base_name_must_be_unique_for_type_if_not_null()
    {
        $nullShortName = ExpertPanel::factory()->gcep()->create();
        $existingGcep = ExpertPanel::factory()->gcep()->create(['short_base_name' => 'Early']);

        $expertPanel = ExpertPanel::factory()->gcep()->create();
        $this->makeRequest($expertPanel, [
            'cdwg_id' => 1,
            'long_base_name' => null,
            'short_base_name' => null,
            'affiliation_id' => '40001',
        ])
        ->assertStatus(200);


        $this->makeRequest($expertPanel, [
                'cdwg_id' => 1,
                'long_base_name' => $existingGcep->long_base_name,
                'short_base_name' => 'Early',
                'affiliation_id' => '40001',
            ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'short_base_name' => ['The short base name has already been taken.'],
            ]);

        $expertPanel->update(['short_base_name' => 'Bird']);
        $this->makeRequest($expertPanel, [
            'cdwg_id' => 1,
            'short_base_name' => 'Bird',
            'long_base_name' => 'blah',
            'affiliation_id' => '40001',
        ])
        ->assertStatus(200);
    }

    /**
     * @test
     */
    public function ep_info_updated_event_published_if_EP_definition_approved()
    {
        Carbon::setTestNow('2022-09-26');
        $ep = ExpertPanel::factory()->create(['step_1_approval_date' => Carbon::now()]);
        $this->makeRequest($ep)
            ->assertStatus(200);
        $ep->refresh();

        $this->assertDatabaseHas('stream_messages', [
            'topic' => config('dx.topics.outgoing.gpm-general-events'),
            'message->event_type' => 'ep_info_updated',
            'message->schema_version' => '1.1.0',
            'message->date' => Carbon::now()->format('Y-m-d H:i:s'),
            'message->data->expert_panel->id' => $ep->group->uuid,
            'message->data->expert_panel->name' => $ep->group->display_name,
            'message->data->expert_panel->type' => $ep->group->type->name,
            'message->data->expert_panel->affiliation_id' => $ep->affiliation_id,
            'message->data->expert_panel->long_base_name' => $ep->long_base_name,
            'message->data->expert_panel->short_base_name' => $ep->short_base_name,
            'message->data->expert_panel->hypothesis_group' => $ep->hypothesis_group,
            'message->data->expert_panel->membership_description' => $ep->membership_description,
            'message->data->expert_panel->scope_description' => $ep->scope_description
        ]);
    }

    /**
     * @test
     */
    public function ep_info_updated_event_NOT_published_if_EP_definition_NOT_approved()
    {
        Carbon::setTestNow('2022-09-26');
        $ep = ExpertPanel::factory()->create();
        $this->makeRequest($ep)
            ->assertStatus(200);
        $ep->refresh();

        $this->assertDatabaseMissing(
            'stream_messages',
            [
                'topic' => config('dx.topics.outgoing.gpm_general_events'),
                'message->event_type' => 'ep_info_updated'
            ]
        );
    }

    private function makeRequest($expertPanel, $data = null): TestResponse
    {
        $data = $data ?? [
            'long_base_name' => 'Test Expert Panel Base Name GCEP',
            'short_base_name' => 'Test EP GCEP',
            'affiliation_id' => '40001',
            'cdwg_id' => $this->cdwg->id,
        ];

        return $this->json('PUT', '/api/applications/'.$expertPanel->uuid, $data);
    }

}
