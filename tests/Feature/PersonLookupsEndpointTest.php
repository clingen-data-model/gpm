<?php

namespace Tests\Feature;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use Database\Seeders\CountrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class PersonLookupsEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->runSeeder([CountrySeeder::class]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);
    }


    #[Test]
    public function can_get_list_of_countries()
    {
        $response = $this->json('GET', '/api/people/lookups/countries');
        $response->assertStatus(200);
        $response->assertJsonFragment([['id' => 226, 'name' => 'United States']]);
        $response->assertJsonFragment([['id' => 1, 'name' => 'Afghanistan']]);
    }

    #[Test]
    public function get_get_a_single_country()
    {
        $response = $this->json('GET', '/api/people/lookups/countries/226');
        $response->assertStatus(200);
        $response->assertJsonFragment([['id' => 226, 'name' => 'United States']]);
    }

}
