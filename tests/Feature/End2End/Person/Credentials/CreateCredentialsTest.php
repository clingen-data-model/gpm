<?php

namespace Tests\Feature\End2End\Person\Credentials;

use Tests\TestCase;
use App\Models\Credential;
use Laravel\Sanctum\Sanctum;
use Illuminate\Testing\TestResponse;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group credentials
 */
class CreateCredentialsTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
    }

    /**
     * @test
     */
    public function guest_cannot_create_credential()
    {
        $this->makeRequest()
            ->assertStatus(401);
    }

    /**
     * @test
     */
    public function authed_user_can_created_a_credential()
    {

        $this->login();
        $this->makeRequest()
            ->assertStatus(201)
            ->assertJson([
                'name' => 'Cptn.',
                'approved' => 0,
            ]);

        $this->assertDatabaseHas('credentials', [
            'name' => 'Cptn.',
            'approved' => 0,
        ]);
    }

    /**
     * @test
     */
    public function validates_input()
    {
        $this->login();
        $this->makeRequest([])
            ->assertJsonValidationErrors([
                'name' => 'required',
            ]);

        Credential::factory()->create(['name' => 'Cptn.']);
        $this->makeRequest()
            ->assertJsonValidationErrors([
                'name' => 'The name has already been taken.'
            ]);
    }



    private function makeRequest($data = null): TestResponse
    {
        $data = $data ?? [
            'name' => 'Cptn.',
            'approved' => 0
        ];

        return $this->json('POST', '/api/credentials', $data);
    }

}
