<?php

namespace Tests\Feature\End2End\ExpertPanels;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Document;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use App\Modules\Group\Models\Group;
use App\Modules\ExpertPanel\Models\Coi;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group coi
 */
class SimpleCoiEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->group = Group::factory()->create();
    }

    /**
     * @test
     */
    public function can_get_group_from_coi_code()
    {
        $this->json('GET', '/api/coi/'.$this->group->coi_code.'/group')
        ->assertStatus(200)
        ->assertJson($this->group->toArray());
    }

    /**
     * @test
     */
    public function returns_404_if_group_not_found_for_code()
    {
        $this->json('GET', '/api/coi/some-fake-code/group')
        ->assertStatus(404);
    }

    /**
     * @test
     */
    public function validates_coi_response_data()
    {
        $this->json('POST', '/api/coi/'.$this->group->coi_code, [])
            ->assertStatus(422)
            ->assertJsonFragment(['group_member_id' => ['Storing a COI requires a group member id']])
            ->assertJsonFragment(['work_fee_lab' => ['This field is required.']])
            ->assertJsonFragment(['contributions_to_gd_in_ep' => ['This field is required.']])
            ->assertJsonFragment(['independent_efforts' => ['This field is required.']])
            ->assertJsonFragment(['coi' => ['This field is required.']]);

        $this->json('POST', '/api/coi/'.$this->group->coi_code, ['contributions_to_gd_in_ep' => 1])
            ->assertStatus(422)
            ->assertJsonFragment(['contributions_to_genes' => ['This field is required.']]);
    }

    /**
     * @test
     */
    public function stores_valid_coi_response()
    {
        $groupMember = GroupMember::factory()->create();
        $data = [
            'group_member_id' => $groupMember->id,
            'work_fee_lab' => 0,
            'contributions_to_gd_in_ep' => 1,
            'contributions_to_genes' => 'beans',
            'independent_efforts' => 'None',
            'coi' => 'no coi',
        ];


        $this->json('POST', '/api/coi/'.$this->group->coi_code, $data)
        ->assertStatus(200);

        unset($data['group_member_id']);

        $this->assertDatabaseHas('cois', [
            'group_member_id' => $groupMember->id,
            'group_id' => $this->group->id,
            'data->work_fee_lab' => 0,
            'data->contributions_to_gd_in_ep' => 1,
            'data->contributions_to_genes' => 'beans',
            'data->independent_efforts' => 'None',
            'data->coi' => 'no coi'
        ]);
    }

    /**
     * @test
     */
    public function stores_legacy_coi()
    {
        $document = Document::factory()->create([
            'owner_id' => $this->group->id,
            'owner_type' => get_class($this->group),
            'document_type_id' => config('documents.types.coi.id')
        ]);

        $data = [
            "download_url" => "http://localhost:8080/documents/".$document->uuid,
            "document_uuid" => $document->uuid
        ];
        $this->json('POST', '/api/coi/'.$this->group->coi_code, $data)
            ->assertStatus(200);


        $expectedData = array_merge($data, [
            'email' => 'Legacy Coi',
            'first_name' => 'Legacy',
            'last_name' => 'Coi'
        ]);
        $this->assertDatabaseHas('cois', [
            'group_id' => $this->group->id,
            'data' => json_encode($expectedData)
        ]);
    }
}
