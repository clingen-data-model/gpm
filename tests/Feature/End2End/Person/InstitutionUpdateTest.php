<?php

namespace Tests\Feature\End2End\Person;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\Person\Models\Country;
use App\Modules\Person\Models\Institution;
use Illuminate\Foundation\Testing\WithFaker;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class InstitutionUpdateTest extends TestCase
{
    use FastRefreshDatabase;
    
    public function setup():void
    {
        parent::setup();
        $this->country = Country::factory()->create();
        $this->user = $this->setupUser(permissions: ['people-manage']);
        Sanctum::actingAs($this->user);

        $this->institution = Institution::factory()->create(['country_id' => null]);
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_update_institution()
    {
        $this->user->revokePermissionTo('people-manage');

        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function privileged_user_can_update_institution()
    {
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJson([
                'name' => 'Beans University',
                'abbreviation' => 'BU',
                'country_id' => $this->country->id,
                'url' => 'https://www.beansu.edu'
            ]);

        $this->assertDatabaseHas('institutions', [
            'name' => 'Beans University',
            'abbreviation' => 'BU',
            'country_id' => $this->country->id,
            'url' => 'https://www.beansu.edu'
        ]);
    }
    
    /**
     * @test
     */
    public function validates_data()
    {
        $this->makeRequest(['country_id' => 777])
            ->assertStatus(422)
            ->assertJson([
                'errors'=>[
                    'name' => ['This is required.'],
                    'country_id' => ['The selection is invalid.']
                ]
            ]);
    }
    

    private function makeRequest($data = null)
    {
        $data = $data ?? [
            'name' => 'Beans University',
            'abbreviation' => 'BU',
            'country_id' => $this->country->id,
            'url' => 'https://www.beansu.edu'
        ];

        return $this->json('PUT', '/api/institutions/'.$this->institution->id, $data);
    }
}
