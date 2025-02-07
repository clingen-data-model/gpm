<?php

namespace Tests\Feature\End2End\Person;

use Tests\TestCase;
use App\Models\Role;
use App\Models\Expertise;
use App\Models\Credential;
use App\Models\Permission;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use Database\Seeders\CredentialSeeder;
use App\Modules\Group\Actions\MemberAdd;
use App\Modules\Person\Models\Institution;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Group\Actions\MemberAssignRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Group\Actions\MemberGrantPermissions;
use Tests\Feature\End2End\Person\TestEventPublished;

/**
 * @group people
 * @group person
 * @group profile
 */
class UpdateProfileTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private $institution;
    private $race;
    private $primaryOcc;
    private $gender;
    private $country;
    private $credential;
    use TestEventPublished;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->user = $this->setupUser();
        $this->person = Person::factory()->create();
        $this->perm = Permission::factory()->create(['name' => 'people-manage']);
        $this->groupPerm = Permission::factory()->create(['name' => 'members-update', 'scope' => 'group']);

        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function a_person_can_update_their_own_profile_info()
    {
        $data = $this->getDefaultData();
        $expected = $data;
        unset($expected['credential_ids']);
        unset($expected['expertise_ids']);

        $this->person->update(['user_id' => $this->user->id]);

        $this->makeRequest($data)
            ->assertStatus(200)
            ->assertJsonFragment($expected)
            ->assertJsonFragment([
                $this->credential->toArray()
            ])
            ->assertJsonFragment([
                $this->expertise->toArray()
            ]);

        $this->assertDatabaseHas('people', $expected);
        $this->assertDatabaseHas(
            'credential_person',
            [
                'credential_id' => $this->credential->id,
                'person_id' => $this->person->id
            ]
        );
    }

    /**
     * @test
     */
    public function a_user_who_can_update_others_profile_can_update_a_profile()
    {
        $this->user->givePermissionTo($this->perm);

        $this->makeRequest()
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function associated_user_name_and_email_updated_when_person_updated()
    {
        $this->person->update(['user_id' => $this->user->id]);

        $data = $this->getDefaultData();
        $data['first_name'] = 'beans';
        $data['last_name'] = 'mccradden';
        $data['email'] = 'beans@beans.com';

        $this->makeRequest($data)
            ->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'beans mccradden',
            'email' => 'beans@beans.com'
        ]);
    }


    /**
     * @test
     */
    public function a_user_with_group_permission_cannot_update_profile()
    {
        $group = Group::factory()->create();
        MemberAdd::run($group, $this->person);

        $userPerson = Person::factory()->create(['user_id' => $this->user->id]);
        $userMember = MemberAdd::run($group, $userPerson);
        MemberGrantPermissions::run($userMember, collect([$this->groupPerm]));

        $this->makeRequest(['biography' => 'I like beans and George Wendt.'])
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function another_user_without_permission_cannot_update_profile()
    {
        Sanctum::actingAs($this->user);
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function a_coordinator_can_edit_some_profile_fields_of_members()
    {
        $group = Group::factory()->create();
        MemberAdd::run($group, $this->person);

        $role = Role::where('name', 'coordinator')->first();

        $userPerson = Person::factory()->create(['user_id' => $this->user->id]);
        $userMember = MemberAdd::run($group, $userPerson);
        $action = new MemberAssignRole();
        $action->handle($userMember, [$role]);
        $credentials = Credential::factory()->count(2)->create();
        $expertises = Expertise::factory()->count(2)->create();

        $this->makeRequest([
                'first_name' => 'early',
                'last_name' => 'dog',
                'email' => 'earlydog@turds.com',
                'credential_ids' => $credentials->pluck('id')->toArray(),
                'expertise_ids' => $expertises->pluck('id')->toArray(),
                'biography' => 'A real turd burgler'
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('people', [
            'first_name' => 'early',
            'last_name' => 'dog',
            'email' => 'earlydog@turds.com',
            'biography' => null,
        ]);

        $this->assertDatabaseHas('credential_person', [
            'credential_id' => $credentials->first()->id,
            'person_id' => $this->person->id
        ]);
        $this->assertDatabaseHas('credential_person', [
            'credential_id' => $credentials->last()->id,
            'person_id' => $this->person->id
        ]);
    }


    /**
     * @test
     */
    public function validates_required_if_user_cannot_update_another_persons_profile()
    {
        $this->person->update(['user_id' => $this->user->id]);
        $response = $this->makeRequest([])
            ->assertStatus(422)
            ->assertJsonFragment([
                'institution_id' => ['This is required.'],
                'credential_ids' => ['This is required.'],
                'expertise_ids' => ['This is required.'],
                'country_id' => ['This is required.'],
                'timezone' => ['This is required.'],
            ]);
    }

    /**
     * @test
     */
    public function does_not_require_fields_if_user_can_update_another_persons_profile()
    {
        $this->user->givePermissionTo($this->perm);

        $this->makeRequest([
            'first_name' => $this->person->first_name,
            'last_name' => $this->person->last_name,
            'email' => $this->person->email,
            'biography' => 'I\'m a little teapot.'
        ])
        ->assertStatus(200)
        ->assertJsonFragment(['biography' => 'I\'m a little teapot.'])
        ->assertJsonMissingValidationErrors(['credential_ids'])
        ->assertJsonMissingValidationErrors(['expertise_ids']);
    }

    /**
     * @test
     */
    public function validates_relation_ids_exist()
    {
        $data = [
            'institution_id' => 666,
            'race_id' => 666,
            'primary_occupation_id' => 666,
            'gender_id' => 666,
            'country_id' => 666,
            'orcid_id' => 12345,
            'hypothesis_id' => 12345,
            'biography' => $this->faker->paragraph(),
            'timezone' => $this->faker->timezone(),
            'credential_ids' => [999],
            'expertise_ids' => [999]
        ];

        $this->user->givePermissionTo($this->perm);
        $this->makeRequest($data)
            ->assertJsonFragment([
                'institution_id' => ['The selection is invalid.'],
                'country_id' => ['The selection is invalid.'],
                'credential_ids.0' => ['The selection is invalid.'],
                'expertise_ids.0' => ['The selection is invalid.'],
            ]);
    }

    /**
     * @test
     */
    public function validates_email_is_unique_to_people()
    {
        $this->user->givePermissionTo('people-manage');
        Person::factory()->create(['email' => 'beans@beans.com']);

        $this->makeRequest([
            'first_name' => $this->person->first_name,
            'last_name' => $this->person->last_name,
            'email' => 'beans@beans.com',
            'biography' => 'I\'m a little teapot.',
        ])
        ->assertStatus(422)
        ->assertJsonFragment([
            'email' => ['Somebody is already using that email address.']
        ]);
    }

    /**
     * @test
     */
    public function validates_email_is_unique_to_users()
    {
        $this->user->givePermissionTo('people-manage');
        User::factory()->create(['email' => 'beans@beans.com']);

        $this->makeRequest([
            'first_name' => $this->person->first_name,
            'last_name' => $this->person->last_name,
            'email' => 'beans@beans.com',
            'biography' => 'I\'m a little teapot.',
        ])
        ->assertStatus(422)
        ->assertJsonFragment([
            'email' => ['Somebody is already using that email address.']
        ]);
    }

    /**
     * @test
     *
     * @group dx
     * @group gpm-person-events
     */
    public function publishes_updated_event_to_gpm_person_events()
    {
        $this->user->givePermissionTo('people-manage');

        $this->makeRequest()
            ->assertStatus(200);
        $person = $this->person->fresh();

        $this->assertEventPublished(config('dx.topics.outgoing.gpm-person-events'), 'person_updated', $person);
    }


    private function makeRequest($data = null)
    {
        $data = $data ?? $this->getDefaultData();

        return $this->json('PUT', '/api/people/'.$this->person->uuid.'/profile', $data);
    }

    private function getDefaultData(): array
    {
        $this->setupLookups();

        return [
            'first_name' => $this->person->first_name,
            'last_name' => $this->person->last_name,
            'email' => $this->person->email,
            'institution_id' => $this->institution->id,
            'credential_ids' => [$this->credential->id],
            'expertise_ids' => [$this->expertise->id],
            'race_id' => $this->race->id,
            'primary_occupation_id' => $this->primaryOcc->id,
            'gender_id' => $this->gender->id,
            'country_id' => $this->country->id,
            'orcid_id' => 12345,
            'hypothesis_id' => 12345,
            'biography' => $this->faker->paragraph(),
            'timezone' => $this->faker->timezone()
        ];
    }



    private function setupLookups()
    {
        $this->institution = \App\Modules\Person\Models\Institution::factory()->create();
        $this->race = \App\Modules\Person\Models\Race::factory()->create();
        $this->primaryOcc = \App\Modules\Person\Models\PrimaryOccupation::factory()->create();
        $this->gender = \App\Modules\Person\Models\Gender::factory()->create();
        $this->country = \App\Modules\Person\Models\Country::factory()->create();
        $this->credential = Credential::factory()->create();
        $this->expertise = Expertise::factory()->create(['approved' => true]);

    }
}
