<?php

namespace Tests;

use App\Models\Cdwg;
use App\Models\Role;
use Ramsey\Uuid\Uuid;
use App\Models\Permission;
use Illuminate\Support\Carbon;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Bus;
use App\Modules\Person\Models\Person;
use Database\Seeders\GroupTypeSeeder;
use Database\Seeders\GroupStatusSeeder;
use App\Modules\ExpertPanel\Jobs\AddContact;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Actions\ContactAdd;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Database\Seeders\EpTypesTableSeeder;
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
        $group = Group::factory()->create(['group_type_id' => config('groups.types.cdwg.id')]);
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

    protected function setupUser($userData = null, $permissions = [])
    {
        $userData = $userData ?? [];
        $user = User::factory()->create($userData);
        if (count($permissions)) {
            $user->syncPermissions($permissions);
        }

        return $user;
    }

    protected function setupUserWithPerson($userData = null, $permissions = [], $personData = [])
    {
        $user = $this->setupUser($userData, $permissions);
        $person = $user->person()->save(Person::factory()->make());

        return $user;
    }

    protected function setupPermission(String|array $permissions)
    {
        if (is_string($permissions)) {
            Permission::factory()->create(['name' => $permissions]);
            return;
        }

        foreach ($permissions as $perm) {
            Permission::factory()->create(['name' => $perm]);
        }
    }

    protected function setupRoles(String|array $roles)
    {
        if (is_string($roles)) {
            Role::factory()->create(['name' => $roles]);
            return;
        }

        foreach ($roles as $perm) {
            Role::factory()->create(['name' => $perm]);
        }
    }

    protected function runSeeder($seederClass): void
    {
        $seeder = new $seederClass();
        $seeder->run();
    }

    protected function seedForGroupTest(): void
    {
        $seeders = [
            GroupTypeSeeder::class,
            EpTypesTableSeeder::class,
            GroupStatusSeeder::class,
        ];

        foreach ($seeders as $seederClass) {
            $this->runSeeder($seederClass);
        }
    }
    
    protected function setupForGroupTest(): void
    {
        $this->seedForGroupTest();
        $this->setupRoles('coordinator', 'groups-manage');
    }

    protected function getLongString()
    {
        return 'something longer than 255 characters so that we can test the maximum length validation.  If we don\'t validate the length of the string some verbose pontificator will inevitably think their group is so important that it needs a name longer that 255 characters.';
    }
}
