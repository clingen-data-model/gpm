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
    
    
    
}
