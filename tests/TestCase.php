<?php

namespace Tests;

use App\Models\Role;
use Ramsey\Uuid\Uuid;
use App\Models\Permission;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Carbon;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use Illuminate\Testing\TestResponse;
use App\Modules\Person\Models\Person;
use Database\Seeders\GroupTypeSeeder;
use Database\Seeders\GroupStatusSeeder;
use Database\Seeders\EpTypesTableSeeder;
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
        TestResponse::macro('assertValidationErrors', function($validationErrrors) {
            $this->assertStatus(422)
                ->assertInvalid($validationErrrors);

            return $this;
        });
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
        $logName = null
    ) {
        $data = [
            'description' => $description,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
        ];

        if ($logName) {
            $data['log_name'] = $logName;
        }

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
            // if (!isset($properties['step'])) {
            //     $properties['step'] = $subject->current_step;
            // }
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
            foreach ($permissions as $perm) {
                Permission::firstOrCreate([
                    'name' => $perm,
                    'guard_name' => 'web',
                ]);
            }
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

    protected function login(?array $userData = null, array $permissions = []): User
    {
        $user = $this->setupUser(userData: $userData, permissions: $permissions);
        Sanctum::actingAs($user);
        return $user;
    }

    protected function loginWithPerson($userData = null, $permissions = [], $personData = []): User
    {
        $user = $this->setupUserWithPerson($userData, $permissions, $personData);
        Sanctum::actingAs($user);
        return $user;
    }


    protected function setupPermission(String|array $permissions, $scope = 'system')
    {
        if (is_string($permissions)) {
            return Permission::factory()->create(['name' => $permissions, 'scope' => $scope]);
        }

        $perms = collect();
        foreach ($permissions as $perm) {
            $perms->push(Permission::factory()->create(['name' => $perm, 'scope' => $scope]));
        }

        return $perms;
    }

    protected function setupRoles(String|array $roles, $scope = 'system')
    {
        if (is_string($roles)) {
            return Role::factory()->create(['name' => $roles, 'scope' => $scope]);
        }

        $createdRoles = collect();
        foreach ($roles as $perm) {
            $createdRoles->push(Role::factory()->create(['name' => $perm, 'scope' => $scope]));
        }
        return $createdRoles;
    }

    protected function runSeeder($seederClass): void
    {
        if (is_array($seederClass)) {
            foreach ($seederClass as $class) {
                if (!class_exists($class)) {
                    throw new \Exception('Bad seeder class given: '.$class);
                }
                $seeder = new $class();
                $seeder->run();
            }
            return;
        }

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

        $this->runSeeder($seeders);
    }

    protected function setupForGroupTest(): void
    {
        $this->seedForGroupTest();
        $roles = $this->setupRoles('coordinator', 'group');
    }

    protected function getLongString()
    {
        return 'something longer than 255 characters so that we can test the maximum length validation.  If we don\'t validate the length of the string some verbose pontificator will inevitably think their group is so important that it needs a name longer that 255 characters.';
    }

    protected function jsonifyArrays($data): array
    {
        $jsonified = [];
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $val = json_encode($val);
            }
            $jsonified[$key] = $val;
        }
        return $jsonified;
    }
}
