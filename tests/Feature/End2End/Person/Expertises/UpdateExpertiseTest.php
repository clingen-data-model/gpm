<?php

namespace Tests\Feature\End2End\Person\Expertises;

use Tests\TestCase;
use App\Models\Expertise;
use Illuminate\Testing\TestResponse;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group expertises
 */
class UpdateExpertiseTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->expertise = Expertise::factory()->create();
    }

    public function guest_cannot_update_a_expertise()
    {
        $this->makeRequest()
            ->assertStatus(401);
    }

    /**
     * @test
     */
    public function unpermissioned_user_cannot_update_a_expertise()
    {
        $this->login();
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function permissioned_user_can_update_a_expertise()
    {
        $this->login(permissions: ['people-manage']);
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJson([
                'name' => 'PhD',
                'approved' => 0
            ]);

        $this->assertDatabaseHas('expertises', [
            'id' => $this->expertise->id,
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

        Expertise::factory()->create(['name' => 'PhD']);
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

        return $this->json('PUT', '/api/expertises/'.$this->expertise->id, $data);
    }

}
