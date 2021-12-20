<?php

namespace Tests\Feature\End2End\Person;

use Tests\TestCase;
use Ramsey\Uuid\Uuid;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group people
 */
class PersonDetailTest extends TestCase
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
    public function user_can_retrieve_a_person_with_uuid()
    {
        Sanctum::actingAs($this->user);
        $person = Person::factory()->create();
        $this->json('GET', '/api/people/'.$person->uuid)
            ->assertStatus(200)
            ->assertJsonFragment($person->toArray());
    }

    /**
     * @test
     */
    public function responds_with_404_when_person_not_found()
    {
        Sanctum::actingAs($this->user);
        $this->json('GET', '/api/people/'.Uuid::uuid4()->toString())
            ->assertStatus(404);
    }

    /**
     * @test
     */
    public function guest_cannot_retrieve_person_with_uuid()
    {
        $person = Person::factory()->create();
        $this->json('GET', '/api/people/'.$person->uuid)
            ->assertStatus(401);
    }
}
