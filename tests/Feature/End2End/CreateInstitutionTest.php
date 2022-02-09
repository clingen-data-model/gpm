<?php

namespace Tests\Feature\End2End;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\Person\Models\Country;
use App\Modules\Person\Models\Institution;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateInstitutionTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->country = Country::factory(['name' => 'Hildaland'])->create();
        $this->user = $this->setupUser();
        Sanctum::actingAs($this->user);
    }
    
    /**
     * @test
     */
    public function can_create_an_institution()
    {
        $this->makeRequest()
            ->assertStatus(201)
            ->assertJsonFragment([
                'id' => 1,
                'name' => 'University of Trollberg',
                'abbreviation' => 'Troll U',
                'url' => 'https://trollu.edu',
                'address' => null,
                'country_id' => $this->country->id
            ]);
    }

    /**
     * @test
     */
    public function validates_required_params()
    {
        $this->makeRequest([])
            ->assertStatus(422)
            ->assertJsonFragment([
                'name' => ['This is required.']
            ]);
    }
    
    /**
     * @test
     */
    public function validates_unique_params()
    {
        Institution::factory([
            'name' => 'University of Trollberg',
            'url' => 'https://trollu.edu'
        ])->create();

        $this->makeRequest()
            ->assertStatus(422)
            ->assertJsonFragment([
                'name' => ['The name has already been taken.'],
                'url' => ['The url has already been taken.']
            ]);
    }
    
    /**
     * @test
     */
    public function validates_country_exists()
    {
        $this->makeRequest(['country_id' => 666])
            ->assertStatus(422)
            ->assertJsonFragment([
                'country_id' => ['The selection is invalid.'],
            ]);
    }
    

    private function makeRequest($data = null)
    {
        $data = $data ?? [
            'name' => 'University of Trollberg',
            'abbreviation' => 'Troll U',
            'url' => 'https://trollu.edu',
            'address' => null,
            'country_id' => $this->country->id
        ];

        return $this->json('POST', '/api/institutions', $data);
    }
}
