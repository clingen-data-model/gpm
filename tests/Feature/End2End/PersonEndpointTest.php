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
        config('permission.models.permission')::factory([
            'name' => 'people-manage',
            'scope' => 'system'
        ])->create();
        $this->user = User::factory()->create();
        $this->user->givePermissionTo('people-manage');
        $this->people = Person::factory(2)->create();
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function authed_user_can_get_a_person()
    {
        $person = $this->people->first();
        $this->json('get', '/api/people/'.$person->uuid)
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $person->id,
                'uuid' => $person->uuid,
            ]);
    }
}
