<?php

namespace Tests\Feature\End2End;

use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * @group people
 */
class PersonEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function setup(): void
    {
        parent::setup();
        config('permission.models.permission')::factory([
            'name' => 'people-manage',
            'scope' => 'system',
        ])->create();
        $this->user = $this->setupUser();
        $this->user->givePermissionTo('people-manage');
        $this->people = Person::factory(2)->create();
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function authed_user_can_get_a_person(): void
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
