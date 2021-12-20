<?php

namespace Tests\Feature;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CountryEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $user = User::factory()->create();
        Sanctum::actingAs($user);
    }
    

    /**
     * @test
     */
    public function can_get_list_of_countries()
    {
        $response = $this->json('GET', '/api/people/lookups/countries');
        $response->assertStatus(200);
        $response->assertJsonFragment([['id' => 226, 'name' => 'United States']]);
        $response->assertJsonFragment([['id' => 1, 'name' => 'Afghanistan']]);
    }

    /**
     * @test
     */
    public function get_get_a_single_country()
    {
        $response = $this->json('GET', '/api/people/lookups/countries/226');
        $response->assertStatus(200);
        $response->assertJsonFragment([['id' => 226, 'name' => 'United States']]);
    }

    /**
     * @test
     */
    public function can_get_a_list_of_primary_occupations()
    {
        $response = $this->json('GET', '/api/people/lookups/primary-occupations');
        $response->assertStatus(200);
        $response->assertJsonFragment([['id' => 5, 'name' => 'Variant Scientist']]);
        $response->assertJsonFragment([['id' => 1, 'name' => 'Clinical Laboratory Director']]);
    }
    
    /**
     * @test
     */
    public function can_get_a_list_of_genders()
    {
        $response = $this->json('GET', '/api/people/lookups/genders');
        $response->assertStatus(200);
        $response->assertJsonFragment([['id' => 5, 'name' => 'Gender Variant/Non-conforming']]);
        $response->assertJsonFragment([['id' => 1, 'name' => 'Female']]);
    }
    
}
