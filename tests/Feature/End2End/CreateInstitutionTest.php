<?php

namespace Tests\Feature\End2End;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\Person\Models\Country;
use App\Modules\Person\Models\Institution;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class CreateInstitutionTest extends TestCase
{
    use RefreshDatabase;

    private Country $country;

    public function setup():void
    {
        parent::setup();
        $this->country = Country::factory(['name' => 'Hildaland'])->create();
        $this->user = $this->setupUser();
        Sanctum::actingAs($this->user);
    }
    
    #[Test]
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
                'city' => 'Trollberg',
                'country_id' => $this->country->id,
                'reportable' => false
            ]);
    }

    #[Test]
    public function validates_required_params()
    {
        $this->makeRequest([])
            ->assertStatus(422)
            ->assertJsonFragment([
                'name' => ['This is required.'],
                'city' => ['This is required.'],
            ]);
    }
    
    #[Test]
    public function validates_unique_params()
    {
        Institution::factory([
            'name' => 'University of Trollberg',
            'url' => 'https://trollu.edu',
            'city' => 'Trollberg',
            'country_id' => $this->country->id,
        ])->create();

        $this->makeRequest()
            ->assertStatus(422)
            ->assertJsonFragment([
                'name' => ['The name has already been taken.'],
                'url' => ['The url has already been taken.'],
            ]);
    }
    
    #[Test]
    public function validates_country_exists()
    {
        $this->makeRequest($this->validParams(['country_id' => 666]))
            ->assertStatus(422)
            ->assertJsonFragment([
                'country_id' => ['The selection is invalid.'],
            ]);
    }
    

    private function validParams(array $overrides = []): array
    {
        return array_merge([
            'name' => 'University of Trollberg',
            'abbreviation' => 'Troll U',
            'url' => 'https://trollu.edu',
            'address' => null,
            'city' => 'Trollberg',
            'country_id' => $this->country->id,
            'reportable' => false,
        ], $overrides);
    }

    private function makeRequest(?array $data = null)
    {
        return $this->json(
            'POST',
            '/api/institutions',
            $data ?? $this->validParams()
        );
    }
}
