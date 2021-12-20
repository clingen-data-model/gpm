<?php

namespace Tests\Feature\End2End\Person;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group people
 */
class CreatePersonTest extends TestCase
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
    public function an_unprivileged_user_cannot_create_a_person()
    {
        Sanctum::actingAs($this->user);
        $person = Person::factory()->make();
        $person->phone = null;

        $this->json('POST', '/api/people', $person->toArray())
            ->assertStatus(403);
    }
    

    /**
     * @test
     */
    public function it_can_create_a_person_entity()
    {
        $perm = config('permission.models.permission')::factory(['name' => 'person-create'])->create();
        $this->user->givePermissionTo($perm->name);
        Sanctum::actingAs($this->user->fresh());
        $person = Person::factory()->make();
        $person->phone = null;

        $this->json('POST', '/api/people', $person->toArray())
            ->assertStatus(200)
            ->assertJson($person->toArray());
    }
}
