<?php

namespace Tests\Feature\End2End\Group;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Testing\TestResponse;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CoiResponseStoreTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private $member, $user;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->member = GroupMember::factory()->create();
        $this->user = $this->setupUser();
        $this->member->person->update(['user_id' => $this->user->id]);
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function responds_404_if_group_with_code_not_found()
    {
        $this->makeRequest(code: 'blahblahblah')
            ->assertStatus(404);
    }

    /**
     * @test
     */
    public function responds_404_if_group_member_not_found()
    {
        $data = $this->makeDefaultData();
        $data['group_member_id'] = 666;
        $this->makeRequest(data: $data)
            ->assertStatus(404);
    }

    /**
     * @test
     */
    public function validates_base_required_data()
    {
        $this->makeRequest(data: [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'work_fee_lab' => 'This is required.',
                'contributions_to_gd_in_group' => 'This is required.',
                'coi' => 'This is required.',
                'coi_attestation' => 'This is required',
                'data_policy_attestation' => 'This is required'
            ])
            ->assertJsonMissingValidationErrors([
                'contributions_to_genes' => 'This is required.',
                'coi_details' => 'This is required.',
            ]);
    }

    /**
     * @test
     */
    public function validates_follow_required_questions()
    {
        $data = $this->makeDefaultData();
        $data['contributions_to_genes'] = null;
        $data['coi_details'] = null;

        $this->makeRequest(data: $data)
            ->assertJsonValidationErrors([
                'contributions_to_genes' => 'This is required.',
                'coi_details' => 'This is required.',
            ]);
    }

    /**
     * @test
     */
    public function validates_attetestations_are_accepted()
    {
        $data = $this->makeDefaultData();
        $data['coi_attestation'] = 0;
        $data['data_policy_attestation'] = 0;

        $this->makeRequest(data: $data)
            ->assertJsonValidationErrors([
                'coi_attestation' => 'The coi attestation must be accepted.',
                'data_policy_attestation' => 'The data policy attestation must be accepted.'
            ]);
    }

    /**
     * @test
     */
    public function stores_a_valid_coi_response()
    {
        $this->makeRequest()
            ->assertStatus(200);

        $this->assertDatabaseHas('cois', [
            'group_id' => $this->member->group_id,
            'group_member_id' => $this->member->id,
            'data->first_name' => $this->member->person->first_name,
            'data->last_name' => $this->member->person->last_name,
            'data->email' => $this->member->person->email,
            'data->work_fee_lab' => 1,
            'data->contributions_to_gd_in_group' => 1,
            'data->contributions_to_genes' => 'test test test contributes',
            'data->coi' => 2,
            'data->coi_details' => 'test test test coi',
            'data->coi_attestation' => 1,
            'data->data_policy_attestation' => 1,
            'version' => '2.0.0',
        ]);
    }

    private function makeRequest($data = null, $code = null): TestResponse
    {
        $data = $data ?? $this->makeDefaultData();

        $code = $code ?? $this->member->group->coi_code;

        return $this->json('POST', 'api/coi/'.$code, $data);
    }


    private function makeDefaultData()
    {
        return [
            'group_member_id' => $this->member->id,
            'work_fee_lab' => 1,
            'contributions_to_gd_in_group' => 1,
            'contributions_to_genes' => 'test test test contributes',
            'coi' => 2,
            'coi_details' => 'test test test coi',
            'coi_attestation' => 1,
            'data_policy_attestation' => 1
        ];
    }


}
