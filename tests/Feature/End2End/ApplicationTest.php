<?php

namespace Tests\Feature\End2End;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use WithFaker;

    public function setup(): void
    {
        parent::setup();
    }

    /**
     * @test
     */
    public function can_create_initiate_an_application()
    {
        $data = [
            'working_name' => 'EP Working Name',
            'ep_type_id' => config('expert_panels.types.vcep.id'),
            'cdwg_uuid' => $this->faker->Uuid,
            'date_initiated' => '2020-01-01',
        ];
        $response = $this->call('POST', '/api/application', $data);

        $response->assertStatus(200);
        $response->assertJson($data);
    }
}
