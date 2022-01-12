<?php

namespace Tests\Feature\End2End\Person\Profile;

use DateTime;
use Tests\TestCase;
use App\Models\Permission;
use Laravel\Sanctum\Sanctum;
use Illuminate\Validation\Rule;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Race;
use App\Modules\Person\Models\Gender;
use App\Modules\Person\Models\Person;
use App\Modules\Person\Models\Country;
use App\Modules\Group\Actions\MemberAdd;
use App\Modules\Person\Models\Institution;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Person\Models\PrimaryOccupation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Group\Actions\MemberGrantPermissions;

/**
 * @group people
 * @group person
 * @group profile
 */
class UpdateProfileTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;
    
    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->user = User::factory()->create();
        $this->person = Person::factory()->create();
        $this->perm = Permission::factory()->create(['name' => 'people-manage']);
        $this->groupPerm = Permission::factory()->create(['name' => 'members-update', 'scope' => 'group']);

        $this->url = '/api/people/'.$this->person->uuid.'/profile';
    }

    /**
     * @test
     */
    public function a_person_can_update_their_own_profile_info()
    {
        [$institution, $race, $primaryOcc, $gender, $country] = $this->setupLookups();

        $data = [
            'institution_id' => $institution->id,
            'race_id' => $race->id,
            'primary_occupation_id' => $primaryOcc->id,
            'gender_id' => $gender->id,
            'country_id' => $country->id,
            'orcid_id' => 12345,
            'hypothesis_id' => 12345,
            'biography' => $this->faker->paragraph(),
            'timezone' => $this->faker->timezone()
        ];

        $this->person->update(['user_id' => $this->user->id]);
        Sanctum::actingAs($this->user);
        $response = $this->json('PUT', $this->url, $data);
        $response->assertStatus(200);
        $response->assertJsonFragment($data);

        $this->assertDatabaseHas('people', $data);
    }

    /**
     * @test
     */
    public function a_user_who_can_update_others_profile_can_update_a_profile()
    {
        [$institution, $race, $primaryOcc, $gender, $country] = $this->setupLookups();

        $data = [
            'institution_id' => $institution->id,
            'race_id' => $race->id,
            'primary_occupation_id' => $primaryOcc->id,
            'gender_id' => $gender->id,
            'country_id' => $country->id,
            'orcid_id' => 12345,
            'hypothesis_id' => 12345,
            'biography' => $this->faker->paragraph(),
            'timezone' => $this->faker->timezone()
        ];
        $this->user->givePermissionTo($this->perm);
        Sanctum::actingAs($this->user);
        $response = $this->json('PUT', $this->url, $data);
        $response->assertStatus(200);
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

        Sanctum::actingAs($this->user->fresh());
        $this->json('PUT', $this->url, ['biography' => 'I like beans and George Wendt.'])
            ->assertStatus(403);
    }
    
    /**
     * @test
     */
    public function another_user_without_permission_cannot_update_profile()
    {
        Sanctum::actingAs($this->user);
        $this->json('PUT', $this->url, ['biography' => 'I like beans and George Wendt.'])
            ->assertStatus(403);
    }
    

    /**
     * @test
     */
    public function validates_required_if_user_cannot_update_another_persons_profile()
    {
        $this->person->update(['user_id' => $this->user->id]);
        Sanctum::actingAs($this->user);
        $response = $this->json('PUT', $this->url, []);
        $response->assertStatus(422);
        $response->assertJsonFragment([
            'institution_id' => ['This is required.'],
            'primary_occupation_id' => ['This is required.'],
            'gender_id' => ['This is required.'],
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

        Sanctum::actingAs($this->user);
        $response = $this->json('PUT', $this->url, ['biography' => 'I\'m a little teapot.']);
        $response->assertStatus(200);
        $response->assertJsonFragment(['biography' => 'I\'m a little teapot.']);
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
            'timezone' => $this->faker->timezone()
        ];

        $this->user->givePermissionTo($this->perm);
        Sanctum::actingAs($this->user);
        $this->json('PUT', $this->url, $data)
            ->assertJsonFragment([
                'institution_id' => ['The selection is invalid.'],
                'race_id' => ['The selection is invalid.'],
                'primary_occupation_id' => ['The selection is invalid.'],
                'gender_id' => ['The selection is invalid.'],
                'country_id' => ['The selection is invalid.'],
            ]);
    }

    private function setupLookups()
    {
        $institution = \App\Modules\Person\Models\Institution::factory()->create();
        $race = \App\Modules\Person\Models\Race::factory()->create();
        $primaryOcc = \App\Modules\Person\Models\PrimaryOccupation::factory()->create();
        $gender = \App\Modules\Person\Models\Gender::factory()->create();
        $country = \App\Modules\Person\Models\Country::factory()->create();

        return [$institution, $race, $primaryOcc, $gender, $country];
    }
}
