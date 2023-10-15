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
class RetireMemberTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpGroupPersonAndMember;

    public function setup(): void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->setupEntities()->setupMember();

        $this->url = 'api/groups/'.$this->group->uuid.'/members/'.$this->groupMember->id.'/retire/';
        Sanctum::actingAs($this->user);
        Carbon::setTestNow('2022-07-18');
    }

    /**
     * @test
     */
    public function can_retire_member_from_group(): void
    {
        $endDate = Carbon::now();
        $response = $this->json('POST', $this->url, [
            'start_date' => $endDate->format(DateTime::ATOM),
            'end_date' => $endDate->format(DateTime::ATOM),
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $this->groupMember->id,
            'end_date' => $endDate->format('Y-m-d\TH:i:s.000000\Z'),
        ]);

        $this->assertDatabaseHas('group_members', [
            'id' => $this->groupMember->id,
            'end_date' => $endDate->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @test
     */
    public function validates_required_information(): void
    {
        $response = $this->json('POST', $this->url, []);
        $response->assertStatus(422);

        $response->assertJsonFragment(['errors' => [
            'start_date' => ['This is required.'],
            'end_date' => ['This is required.'],
        ]]);
    }

    /**
     * @test
     */
    public function validates_start_and_end_date_is_a_valid_date(): void
    {
        $response = $this->json('POST', $this->url, ['start_date' => uniqid(), 'end_date' => uniqid()]);
        $response->assertStatus(422);

        $response->assertJsonFragment([
            'start_date' => ['The start date is not a valid date.'],
        ]);
        $response->assertJsonFragment([
            'end_date' => ['The end date is not a valid date.'],
        ]);
    }

    /**
     * @test
     */
    public function validates_end_date_is_gte_start_date(): void
    {
        $response = $this->json(
            'POST',
            $this->url,
            [
                'start_date' => (new DateTime)->format(DateTime::ATOM),
                'end_date' => (new DateTime('yesterday'))->format(DateTime::ATOM),
            ]
        );
        $response->assertStatus(422);

        $response->assertJsonFragment([
            'end_date' => ['The end date must be a date after or equal to the member\'s start date.'],
        ]);
    }

    /**
     * @test
     */
    public function logs_member_retired_activity(): void
    {
        $response = $this->json('POST', $this->url, [
            'start_date' => (new DateTime())->format(DateTime::ATOM),
            'end_date' => (new DateTime())->format(DateTime::ATOM),
        ]);
        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => get_class($this->group),
            'subject_id' => $this->group->id,
            'activity_type' => 'member-retired',
            'properties->group_member_id' => $this->groupMember->id,
            'properties->person->id' => $this->person->id,
            'properties->person->name' => $this->person->name,
            'properties->person->email' => $this->person->email,
        ]);
    }
}
