<?php

namespace Tests;

use App\Models\Cdwg;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Carbon;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Bus;
use App\Modules\Person\Models\Person;
use App\Modules\ExpertPanel\Jobs\AddContact;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Actions\ContactAdd;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

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
            'expert_panel_type_id' => config('expert_panels.types.vcep.id'),
            'cdwg_id' => Group::cdwg()->get()->random()->id,
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
    
    protected function addContactToApplication(ExpertPanel  $expertPanel)
    {
        $person = Person::factory()->create();
        ContactAdd::run($expertPanel->uuid, $person->uuid);
        
        return $person;
    }

    protected function assertLoggedActivity(
        $subject,
        $description,
        $properties = null,
        $causer_type = null,
        $causer_id = null,
        $activity_type = null,
        $logName = 'applications'
    ) {
        $data = [
            'log_name' => $logName,
            'description' => $description,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
        ];

        if ($causer_type) {
            $data['causer_type'] = $causer_type;
        }

        if ($causer_id) {
            $data['causer_id'] = $causer_id;
        }

        if ($activity_type) {
            $data['activity_type'] = $activity_type;
        }

        if ($properties) {
            if (!isset($properties['step'])) {
                $properties['step'] = $subject->current_step;
            }
            foreach ($properties as $key => $val) {
                $dbVal = $val;
                if (is_array($val) || is_object($val)) {
                    $dbVal = json_encode($val);
                }
                $data['properties->'.$key] = $dbVal;
            }
        }

        $this->assertDatabaseHas('activity_log', $data);
    }
}
