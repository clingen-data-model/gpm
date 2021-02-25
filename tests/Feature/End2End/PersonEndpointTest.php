<?php

namespace Tests\Feature\End2End;

use Tests\TestCase;
use App\Domain\Person\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PersonEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->user = User::factory()->create();
    }

    /**
     * @test
     */
    public function it_can_create_a_person_entity()
    {
        $person = Person::factory()->make();

        $this->actingAs($this->user, 'api')
            ->json('POST', '/api/people', $person->toArray())
            ->assertStatus(200)
            ->assertJson($person->toArray());
    }
    
}
