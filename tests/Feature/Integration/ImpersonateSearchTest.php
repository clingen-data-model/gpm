<?php

namespace Tests\Feature\Integration;

use App\Models\Role;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ImpersonateSearchTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();

        $this->user = User::factory()->create();
        $this->setupRoles(['super-user', 'super-admin', 'admin']);

        $this->others = collect([
            'michael' => User::factory()->create(['name'=>'michael', 'email'=>'squid@hell.com'])
                ->assignRole('super-user'),
            'elenor' =>User::factory()->create(['name'=>'elenor shelstrop', 'email'=>'elenor@middle.com'])
                ->assignRole('super-admin'),
            'jason' => User::factory()->create(['name'=>'jason mendoza', 'email'=>'jason@stpdnks.com'])
                ->assignRole('admin'),
            'tehani' => User::factory()->create(['name'=>'tehani al-jamil', 'email'=>'tehani@elegant.com'])
                ->assignRole('admin'),
            'vicky' => User::factory()->create(['name' => 'vicky', 'email'=>'vicky@hell.com']),
        ]);
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function superUser_gets_matching_except_superUsers()
    {
        $this->user->assignRole('super-user');


        $response = $this->makeRequest(queryString: 'el')
            ->assertStatus(200);
        $this->assertResponseHas($response, 'elenor');
        $this->assertResponseMissing($response, 'michael');
        $this->assertResponseMissing($response, 'jason');
    }

    /**
     * @test
     */
    public function superAdmin_gets_matching_except_superUsers_and_superAdmins()
    {
        $this->user->assignRole('super-admin');

        $response = $this->makeRequest(queryString: 'el')
            ->assertStatus(200);
        $this->assertResponseMissing($response, 'elenor');
        $this->assertResponseMissing($response, 'michael');
        $this->assertResponseHas($response, 'tehani');
    }

    /**
     * @test
     */
    public function admin_gets_matching_except_superUsers_and_superAdmins_and_admins()
    {
        $this->user->assignRole('admin');

        $response = $this->makeRequest(queryString: 'el')
            ->assertStatus(200);
        $this->assertResponseMissing($response, 'elenor');
        $this->assertResponseMissing($response, 'michael');
        $this->assertResponseMissing($response, 'tehani');
        $this->assertResponseHas($response, 'vicky');
    }



    private function assertResponseHas($response, $key)
    {
        $response->assertJsonFragment([
            [
                'id' => $this->others->get($key)->id,
                'name' => $this->others->get($key)->name,
                'email' => $this->others->get($key)->email
            ]
        ]);
    }

    private function assertResponseMissing($response, $key)
    {
        $response->assertJsonMissing([
            [
                'id' => $this->others->get($key)->id,
                'name' => $this->others->get($key)->name,
                'email' => $this->others->get($key)->email
            ]
        ]);
    }
    
    

    private function makeRequest($queryString)
    {
        return $this->json('GET', '/api/impersonate/search?query_string='.$queryString);
    }
}
