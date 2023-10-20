<?php

namespace Tests\Feature\End2End\Groups\Members;

use App\Modules\Group\Models\Group;
use Carbon\Carbon;
use DateTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * @group groups
 * @group members
 */
class RemoveMemberTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpGroupPersonAndMember;

    public function setup(): void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->user = $this->setupUser();
        $this->setupEntities()->setupMember();

        $this->url = 'api/groups/'.$this->group->uuid.'/members/'.$this->groupMember->id;
        Sanctum::actingAs($this->user);
        Carbon::setTestNow('2022-09-22');
    }

    /**
     * @test
     */
    public function can_remove_member_from_group(): void
    {
        $endDate = Carbon::now();
        $response = $this->json('DELETE', $this->url, [
            'end_date' => $endDate->format(DateTime::ATOM),
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('group_members', [
            'id' => $this->groupMember->id,
            'deleted_at' => $endDate->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @test
     */
    public function validates_required_information(): void
    {
        $response = $this->json('DELETE', $this->url, []);
        $response->assertStatus(422);

        $response->assertJsonFragment(['errors' => [
            'end_date' => ['This is required.'],
        ]]);
    }

    /**
     * @test
     */
    public function validates_end_date_is_a_valid_date(): void
    {
        $response = $this->json('DELETE', $this->url, ['end_date' => uniqid()]);
        $response->assertStatus(422);

        $response->assertJsonFragment([
            'end_date' => ['The end date is not a valid date.'],
        ]);
    }

    /**
     * @test
     */
    public function logs_member_retired_activity(): void
    {
        $response = $this->json('DELETE', $this->url, [
            'start_date' => (new DateTime())->format(DateTime::ATOM),
            'end_date' => (new DateTime())->format(DateTime::ATOM),
        ]);
        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => $this->group::class,
            'subject_id' => $this->group->id,
            'activity_type' => 'member-removed',
            'properties->group_member_id' => $this->groupMember->id,
            'properties->person->id' => $this->person->id,
            'properties->person->name' => $this->person->name,
            'properties->person->email' => $this->person->email,
        ]);
    }
}
