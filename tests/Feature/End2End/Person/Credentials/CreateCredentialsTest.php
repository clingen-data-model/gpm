<?php

namespace Tests\Feature\End2End\Person\Credentials;

use App\Models\Credential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * @group credentials
 */
class CreateCredentialsTest extends TestCase
{
    use RefreshDatabase;

    public function setup(): void
    {
        parent::setup();
    }

    /**
     * @test
     */
    public function guest_cannot_create_credential(): void
    {
        $this->makeRequest()
            ->assertStatus(401);
    }

    /**
     * @test
     */
    public function authed_user_can_created_a_credential(): void
    {
        $this->login();
        $this->makeRequest([
            'name' => 'Cptn.',
            'approved' => 1,
        ])
            ->assertStatus(201)
            ->assertJson([
                'name' => 'Cptn.',
                'approved' => true,
            ]);

        $this->assertDatabaseHas('credentials', [
            'name' => 'Cptn.',
            'approved' => 1,
        ]);
    }

    /**
     * @test
     */
    public function validates_input(): void
    {
        $this->login();
        $this->makeRequest([])
            ->assertJsonValidationErrors([
                'name' => 'required',
            ]);

        Credential::factory()->create(['name' => 'Cptn.']);
        $this->makeRequest()
            ->assertJsonValidationErrors([
                'name' => 'The name has already been taken.',
            ]);
    }

    private function makeRequest($data = null): TestResponse
    {
        $data = $data ?? [
            'name' => 'Cptn.',
        ];

        return $this->json('POST', '/api/credentials', $data);
    }
}
