<?php

namespace Tests\Feature\End2End\Person\Credentials;

use Tests\TestCase;
use App\Models\Credential;
use Illuminate\Testing\TestResponse;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

/**
 * @group credentials
 */
class MergeCredentialsTest extends TestCase
{
    use FastRefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->obsolete = Credential::factory()->has(Person::factory())->create();
        $this->authority = Credential::factory()->has(Person::factory())->create();
    }

    /**
     * @test
     */
    public function guest_cannot_merge_credentials()
    {
        $this->makeRequest()
            ->assertStatus(401);
    }

    /**
     * @test
     */
    public function unpermissioned_user_cannot_merge_credentials()
    {
        $this->login();
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function permissioned_user_can_merge_credentials()
    {
        $this->login(permissions: ['people-manage']);
        $this->makeRequest()
            ->assertStatus(200);

        $this->assertDatabaseMissing('credentials', [
            'id' => $this->obsolete->id
        ]);
        $this->assertDatabaseMissing('credential_person', [
            'credential_id' => $this->obsolete->id
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
    public function validates_both_credentials_exist()
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

        return $this->json('PUT', '/api/credentials/merge', $data);
    }

}
