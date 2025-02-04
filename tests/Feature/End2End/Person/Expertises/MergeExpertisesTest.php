<?php

namespace Tests\Feature\End2End\Person\Expertises;

use Tests\TestCase;
use App\Models\Expertise;
use Illuminate\Testing\TestResponse;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

/**
 * @group expertises
 */
class MergeExpertisesTest extends TestCase
{
    use FastRefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->obsolete = Expertise::factory()->has(Person::factory())->create();
        $this->authority = Expertise::factory()->has(Person::factory())->create();
    }

    /**
     * @test
     */
    public function guest_cannot_merge_expertises()
    {
        $this->makeRequest()
            ->assertStatus(401);
    }

    /**
     * @test
     */
    public function unpermissioned_user_cannot_merge_expertises()
    {
        $this->login();
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function permissioned_user_can_merge_expertises()
    {
        $this->login(permissions: ['people-manage']);
        $this->makeRequest()
            ->assertStatus(200);

        $this->assertDatabaseMissing('expertises', [
            'id' => $this->obsolete->id
        ]);
        $this->assertDatabaseMissing('expertise_person', [
            'expertise_id' => $this->obsolete->id
        ]);
        $this->assertEquals($this->authority->people()->count(), 2);
    }

    /**
     * @test
     */
    public function validates_required_params()
    {
        $this->login(permissions: ['people-manage']);
        $this->makeRequest([])
            ->assertStatus(422)
            ->assertJsonMissingValidationErrors([
                'obsolete_id' => 'This is required.',
                'authority_id' => 'This is required.'
            ]);
    }


    /**
     * @test
     */
    public function validates_both_expertises_exist()
    {
        $this->login(permissions: ['people-manage']);
        $this->makeRequest(['obsolete_id' => 100, 'authority_id' => 99])
            ->assertStatus(422)
            ->assertJsonMissingValidationErrors([
                'obsolete_id' => 'This selection is invalid.',
                'authority_id' => 'This selection is invalid.'
            ]);
    }

    /**
     * @test
     */
    public function validates_obsolete_is_not_authority()
    {
        $this->login(permissions: ['people-manage']);
        $this->makeRequest(['obsolete_id' => $this->authority->id, 'authority_id' => $this->authority->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'obsolete_id' => 'The obsolete id and authority id must be different.',
                'authority_id' => 'The authority id and obsolete id must be different.'
            ]);

    }



    private function makeRequest($data = null): TestResponse
    {
        $data = $data ?? [
            'obsolete_id' => $this->obsolete->id,
            'authority_id' => $this->authority->id
        ];

        return $this->json('PUT', '/api/expertises/merge', $data);
    }

}
