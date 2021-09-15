<?php

namespace Tests\Feature\End2End;

use Tests\TestCase;
use App\Modules\User\Models\User;
use Laravel\Sanctum\Sanctum;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ramsey\Uuid\Uuid;

/**
 * @group people
 */
class PersonEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }
    
    /**
     * @test
     */
    public function it_can_update_a_person_record()
    {
        $person = Person::factory()->create();

        $this->json('PUT', '/api/people/'.$person->uuid, [
            'first_name' => 'Beano',
            'last_name'=>$person->last_name,
            'email' => $person->email,
            // 'phone' => $person->phone
        ])
            ->assertStatus(200)
            ->assertJson(['first_name' => 'Beano']);
    }

    /**
     * @test
     */
    public function it_validates_data_before_updating_a_person()
    {
        $person = Person::factory()->create();
        $url = '/api/people/'.$person->uuid;
        
        $this->json('PUT', $url, [])
        ->assertStatus(422)
        ->assertJsonFragment([
            'errors' => [
                'email' => ['The email field is required.'],
                'first_name' => ['The first name field is required.'],
                'last_name' => ['The last name field is required.'],
                // 'phone' => ['The phone field is required.'] // Phone was determined to be optional by stakeholders.
            ]
        ]);
        $otherPerson = Person::factory()->create();
                
        $this->json('PUT', $url, [
            'email' => $otherPerson->email
        ])
        ->assertStatus(422)
        ->assertJsonFragment([
            'email' => ['The email has already been taken.']
        ]);
    }
}
