<?php

namespace Tests\Feature\End2End\Person;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Actions\MemberAdd;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PersonDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->user = $this->setupUserWithPerson(permissions: ['people-manage']);
        $this->group = Group::factory()->create();
        $this->person = $this->user->person;
        $this->invite = Invite::factory()->create(['person_id' => $this->person->id]);
        $this->membership = app()->make(MemberAdd::class)->handle($this->group, $this->person);

        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_delete_person()
    {
        $this->user->revokePermissionTo('people-manage');

        $this->makeRequest()->assertStatus(403);
    }

    /**
     * @test
     */
    public function permissioned_user_can_delete_person_and_all_relations()
    {
        Carbon::setTestNow('2022-03-15');
        $this->makeRequest()
            ->assertStatus(200);

        $this->assertDatabaseHas('people', ['id' => $this->person->id, 'deleted_at' => Carbon::now()]);
        $this->assertDatabaseHas('invites', ['person_id' => $this->person->id, 'deleted_at' => Carbon::now()]);
        $this->assertDatabaseHas('group_members', ['id' => $this->membership->id, 'deleted_at' => Carbon::now()]);
        $this->assertDatabaseMissing('users', ['id' => $this->user->id]);
    }

    /**
     * @test
     */
    public function records_PersonDeleted_activity()
    {
        Carbon::setTestNow('2022-03-15');
        $this->makeRequest()
            ->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
            'activity_type' => 'person-deleted',
            'log_name' => 'people',
            'subject_type' => get_class($this->person),
            'subject_id' => $this->person->id
        ]);
    }



    private function makeRequest()
    {
        return $this->json('DELETE', '/api/people/'.$this->person->uuid);
    }
}
