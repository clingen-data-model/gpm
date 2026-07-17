<?php

namespace Tests\Feature\End2End\Person;

use Tests\TestCase;
use Ramsey\Uuid\Uuid;
use App\Modules\User\Models\User;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('people')]
class PersonDetailTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function user_can_retrieve_a_person_with_uuid()
    {
        $this->actingAs($this->user, 'clerk');
        $person = Person::factory()->create();
        $this->json('GET', '/api/people/'.$person->uuid)
            ->assertStatus(200)
            ->assertJsonPath('data.id', $person->id)
            ->assertJsonPath('data.uuid', $person->uuid)
            ->assertJsonPath('data.first_name', $person->first_name)
            ->assertJsonPath('data.last_name', $person->last_name)
            ->assertJsonPath('data.name', $person->name)
            ->assertJsonPath('data.email', $person->email);
    }

    #[Test]
    public function responds_with_404_when_person_not_found()
    {
        $this->actingAs($this->user, 'clerk');
        $this->json('GET', '/api/people/'.Uuid::uuid4()->toString())
            ->assertStatus(404);
    }

    #[Test]
    public function guest_cannot_retrieve_person_with_uuid()
    {
        $person = Person::factory()->create();
        $this->json('GET', '/api/people/'.$person->uuid)
            ->assertStatus(401);
    }
}
