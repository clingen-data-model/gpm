<?php

namespace Tests\Feature\End2End\Person;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PersonDemographicsTest extends TestCase
{
    use RefreshDatabase;
    use TestEventPublished;

    public function setup():void
    {
        parent::setup();
        $this->person = Person::factory()->create();
        $this->user = $this->setupUser(permissions: ['people-manage']);
        $this->fakeProfile = ['age' => 3.14159, 'occupation' => 'bean counter'];
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function unprivileged_users_cannot_upload_demographic_information()
    {
        $this->user->revokePermissionTo('people-manage');

        $this->makePost()
            ->assertStatus(403);

    }

    /**
     * @test
     */
    public function user_with_people_manage_can_upload_demographic_information()
    {
        $this->makePost()
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function person_can_upload_their_own_demographic_information()
    {
        $this->user->revokePermissionTo('people-manage');
        $this->person->update(['user_id' => $this->user->id]);

        $this->makePost()
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function stores_demographics_and_updates_person()
    {
        $this->makePost(['age' => 3.14159, 'occupation' => 'bean counter'])
            ->assertStatus(200);

        /* TODO: somehow getting extra escaping of json in db...
        $this->assertDatabaseHas('people', [
            'id' => $this->person->id,
            'profile_demographics' => $this->fakeProfile,
        ]);
        */

        $this->json('GET', '/api/people/'.$this->person->uuid.'/demographics')
            ->assertJson($this->fakeProfile);
    }


    /**
     * @test
     */
    public function unprivileged_users_cannot_get_demographic_data()
    {
        $this->user->revokePermissionTo('people-manage');
        $this->json('GET', '/api/people/'.$this->person->uuid.'/demographics')
            ->assertStatus(403);
    }


    private function makePost($data = null)
    {
        $json_in = ['demographics' => json_encode($data ?? $this->fakeProfile)];

        return $this->json('POST', '/api/people/'.$this->person->uuid.'/demographics', $json_in);
    }


}
