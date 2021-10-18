<?php

namespace Tests\Feature\Integration\Modules\Person\Jobs;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Bus\Dispatcher;
use App\Modules\User\Models\User;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use App\Modules\Person\Jobs\UpdatePerson;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Person\Events\PersonDataUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;

class UpdatePersonTest extends TestCase
{
    use RefreshDatabase;

    private Person $person;
    private $bus;

    public function setup():void
    {
        parent::setup();
        $this->person = Person::factory()->create();
        $this->bus = app()->make(BusDispatcher::class);
        $this->permission = config('permission.models.permission')::factory()->create(['name' => 'update-others-profile']);
        $this->user = User::factory()->create();
        $this->user->givePermissionTo($this->permission);
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function updates_non_null_attributes()
    {
        $job = new UpdatePerson($this->person->uuid, ['email' => 'a@b.com', 'first_name' => null]);
        $this->bus->dispatch($job);

        $freshPerson = $this->person->fresh();

        $this->assertEquals($freshPerson->email, 'a@b.com');
        $this->assertEquals($this->person->first_name, $freshPerson->first_name);
    }

    /**
     * @test
     */
    public function fires_PersonDataUpdatedEvent_if_attributes_changed()
    {
        Event::fake();

        $job = new UpdatePerson($this->person->uuid, ['email' => 'a@b.com', 'first_name' => null]);
        $this->bus->dispatch($job);

        Event::assertDispatched(PersonDataUpdated::class, function ($event) {
            return $event->getProperties() == ['email' => 'a@b.com'];
        });
    }

    /**
     * @test
     */
    public function records_activity_when_person_attribues_updated()
    {
        $job = new UpdatePerson($this->person->uuid, ['email' => 'a@b.com', 'first_name' => null]);
        $this->bus->dispatch($job);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Person::class,
            'subject_id' => $this->person->id,
            'properties->email' => 'a@b.com'
        ]);
    }
    

    /**
     * @test
     */
    public function does_not_fire_PersonDataUpdateEvent_if_attributes_not_changed()
    {
        Event::fake();

        $job = new UpdatePerson($this->person->uuid, ['email' => null, 'first_name' => null]);
        $this->bus->dispatch($job);

        Event::assertNotDispatched(PersonDataUpdated::class);
    }
}
