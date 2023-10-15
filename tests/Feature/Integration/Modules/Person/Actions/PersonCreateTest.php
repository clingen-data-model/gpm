<?php

namespace Tests\Feature\Integration\Modules\Person\Actions;

use App\Modules\Person\Actions\PersonCreate;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\End2End\Person\TestEventPublished;
use Tests\TestCase;

class PersonCreateTest extends TestCase
{
    use WithFaker;
    use TestEventPublished;
    use RefreshDatabase;

    /**
     * @test
     *
     * @group dx
     * @group gpm-person-events
     */
    public function publishes_created_event_to_gpm_person_events(): void
    {
        $user = $this->setupUser();

        $action = app()->make(PersonCreate::class);
        $person = $action->handle(
            uuid: $this->faker->uuid(),
            first_name: $this->faker->firstName(),
            last_name: $this->faker->lastName(),
            email: $this->faker->email(),
            phone: $this->faker->phoneNumber(),
            user_id: $user->id
        );

        $this->assertEventPublished(config('dx.topics.outgoing.gpm-person-events'), 'created', $person);
    }
}
