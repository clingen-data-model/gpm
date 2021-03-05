<?php

namespace Tests\Feature\Integration\Modules\Person\Jobs;

use Tests\TestCase;
use Ramsey\Uuid\Uuid;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use App\Modules\Person\Jobs\CreatePerson;
use App\Modules\Person\Events\PersonCreated;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;

class CreatePersonTest extends TestCase
{
    use RefreshDatabase;

    private Person $person;
    private $bus;

    public function setup():void
    {
        parent::setup();
        $this->person = Person::factory()->create();
        $this->bus = app()->make(BusDispatcher::class);
        $this->data = [
            'uuid' => Uuid::uuid4(),
            'first_name' => 'first',
            'last_name' => 'last',
            'email' => 'email@email.com',
            'phone' => '123-123-1234'
        ];
        $this->job = new CreatePerson(...$this->data);

    }

    /**
     * @test
     */
    public function creates_a_new_person()
    {
        $this->bus->dispatch($this->job);

        $this->assertDatabaseHas('people', $this->data);
    }

    /**
     * @test
     */
    public function fires_PersonCreated_event()
    {
        Event::fake();
        $this->bus->dispatch($this->job);

        Event::assertDispatched(PersonCreated::class);
    }

    /**
     * @test
     */
    public function PersonCreate_event_is_recorded()
    {

        $this->bus->dispatch($this->job);
        $person = Person::findByUuidOrFail($this->data['uuid']);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'people',
            'subject_type' => get_class($person),
            'subject_id' => $person->id,
            'description' => 'Person created.'
        ]);
    }
    
    
    
}