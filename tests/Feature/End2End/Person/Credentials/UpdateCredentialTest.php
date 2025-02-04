<?php

namespace Tests\Feature\End2End\Person\Credentials;

use Tests\TestCase;
use App\Models\Credential;
use Illuminate\Testing\TestResponse;
use Illuminate\Foundation\Testing\WithFaker;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

/**
 * @group credentials
 */
class UpdateCredentialTest extends TestCase
{
    use FastRefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->credential = Credential::factory()->create();
    }

    public function guest_cannot_update_a_credential()
    {
        $this->makeRequest()
            ->assertStatus(401);
    }

    /**
     * @test
     */
    public function unpermissioned_user_cannot_update_a_credential()
    {
        $this->login();
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function permissioned_user_can_update_a_credential()
    {
        $this->login(permissions: ['people-manage']);
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJson([
                'name' => 'PhD',
                'approved' => 0
            ]);

        $this->assertDatabaseHas('credentials', [
            'id' => $this->credential->id,
            'name' => 'PhD'
        ]);
    }

    /**
     * @test
     */
    public function validates_params()
    {
        $this->login(permissions: ['people-manage']);
        $this->makeRequest([])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'name' => 'This is required.'
            ]);

        Credential::factory()->create(['name' => 'PhD']);
        $this->makeRequest()
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'name' => 'The name has already been taken.'
            ]);

        $this->makeRequest(['name' => str_repeat('x', 256), 'approved' => 2])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'name' => 'The name may not be greater than 255 characters.',
                'approved' => 'The approved field must be true or false.'
            ]);
    }




    private function makeRequest($data = null): TestResponse
    {
        $data = $data ?? [
            'name' => 'PhD',
            'approved' => 0,
        ];

        return $this->json('PUT', '/api/credentials/'.$this->credential->id, $data);
    }

}
