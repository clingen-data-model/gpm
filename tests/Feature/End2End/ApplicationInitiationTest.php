<?php

namespace Tests\Feature\End2End;

use App\Domain\Application\Models\Person;
use App\Models\Cdwg;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class ApplicationInitiationTest extends TestCase
{
    use WithFaker;
    use RefreshDatabase;

    /**
     * @test
     */
    public function can_create_initiate_an_application()
    {
        $this->seed();    

        $data = $this->makeApplicationData();
        $response = $this->json('POST', '/api/application', $data);

        $response->assertStatus(200);

        $expectedAttributes = array_merge(
            $data, 
            [
                'date_initiated'=>'2020-01-01T00:00:00.000000Z', 
                'current_step' => 1
            ]
        );
        $response->assertJson($expectedAttributes);
    }

    /**
     * @test
     */
    public function validates_parameters_before_initiating_application()
    {
        $this->seed();    

        $data = [];
        $response = $this->json('Post', '/api/application', $data);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The given data was invalid.',
            'errors' => [
                'uuid' => ['The uuid field is required.'],
                'working_name' => ['The working name field is required.'],
                'cdwg_id' => ['The cdwg id field is required.'],
                'ep_type_id' => ['The ep type id field is required.'],
            ]
        ]);
    }    
}
