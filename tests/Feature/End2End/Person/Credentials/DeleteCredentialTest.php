<?php

namespace Tests\Feature\End2End\Person\Credentials;

use Tests\TestCase;
use App\Models\Credential;
use Illuminate\Testing\TestResponse;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group credentials
 */
class DeleteCredentialTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->credential = Credential::factory()
                            ->has(Person::factory())
                            ->create();
        $this->person = $this->credential->people->first();
    }

    /**
     * @test
     */
    public function guest_cannot_delete_a_credential()
    {
        $this->makeRequest()
            ->assertStatus(401);
    }

    /**
     * @test
     */
    public function unpermissioned_user_cannot_delete_a_credential()
    {
        $this->login();
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function permissioned_user_can_delete_a_credential()
    {
        $this->login(permissions: ['people-manage']);
        $this->makeRequest()
            ->assertStatus(200);

        $this->assertDatabaseMissing('credentials', [
            'id' => $this->credential->id
        ]);

        $this->assertDatabaseMissing('credential_person', [
            'credential_id' => $this->credential->id,
            'person_id' => $this->person->id
        ]);
    }



    private function makeRequest($data = null): TestResponse
    {
        $data = $data ?? [];

        return $this->json('DELETE', '/api/credentials/'.$this->credential->id, $data);
    }
}
