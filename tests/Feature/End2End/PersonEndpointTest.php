<?php

namespace Tests\Feature\End2End;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use App\Domain\Person\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ramsey\Uuid\Uuid;

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
    public function it_can_create_a_person_entity()
    {
        $person = Person::factory()->make();

        $this->json('POST', '/api/people', $person->toArray())
            ->assertStatus(200)
            ->assertJson($person->toArray());
    }

    /**
     * @test
     */
    public function it_can_retrieve_a_person_with_uuid()
    {
        $person = Person::factory()->create();
        $this->json('GET', '/api/people/'.$person->uuid)
            ->assertStatus(200)
            ->assertJson($person->toArray());   
    }

    /**
     * @test
     */
    public function responds_with_404_when_person_not_found()
    {
        $this->json('GET', '/api/people/'.Uuid::uuid4()->toString())
            ->assertStatus(404);   
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
            'phone' => $person->phone
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
                'phone' => ['The phone field is required.']
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
