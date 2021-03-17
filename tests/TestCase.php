<?php

namespace Tests;

use App\Models\Cdwg;
use App\Modules\Application\Jobs\AddContact;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Carbon;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Application\Models\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Bus;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use WithFaker;
    // Helper methods
    
    public function setup():void
    {
        parent::setup();
    }
    

    public function makeApplicationData()
    {
        $data = [
            'uuid' => Uuid::uuid4()->toString(),
            'working_name' => 'EP Working Name',
            'ep_type_id' => config('expert_panels.types.vcep.id'),
            'cdwg_id' => Cdwg::all()->random()->id,
            'date_initiated' => Carbon::parse('2020-01-01')
        ];
        return $data;
    }

    protected function makeContactData(int $number = 1)
    {
        $contacts = [];
        for ($i=0; $i < $number; $i++) { 
            $contacts[] = [
                'uuid' => Uuid::uuid4(),
                'first_name' => $this->faker()->firstName,
                'last_name' => $this->faker()->lastName,
                'email' => $this->faker()->email,
                'phone' => $this->faker()->phoneNumber
            ];
        }

        return $contacts;
    }
    
    protected function addContactToApplication(Application $application)
    {
        $person = Person::factory()->create();
        $job = new AddContact($application->uuid, $person->uuid);
        Bus::dispatch($job);
        
        return $person;
    }

    protected  function assertLoggedActivity(
        $application, 
        $description, 
        $properties = null, 
        $causer_type = null, 
        $causer_id = null
    )
    {

        $data = [
            'log_name' => 'applications',
            'description' => $description,
            'subject_type' => Application::class,
            'subject_id' => (string)$application->id,
            'causer_type' => $causer_type,
            'causer_id' => $causer_id,
        ];

        if ($properties) {
            if (!isset($properties['step'])) {
                $properties['step'] = $application->current_step;
            }
            $data['properties'] = json_encode($properties);
        }

        $this->assertDatabaseHas('activity_log', $data);
    }
}
