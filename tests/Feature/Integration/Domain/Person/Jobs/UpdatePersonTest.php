<?php

namespace Tests\Feature\Integration\Domain\Person\Jobs;

use App\Modules\Person\Events\PersonDataUpdated;
use App\Modules\Person\Jobs\UpdatePerson;
use Tests\TestCase;
use App\Modules\Person\Models\Person;
use Illuminate\Bus\Dispatcher;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

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
            'properties' => json_encode(['email' => 'a@b.com'])
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
