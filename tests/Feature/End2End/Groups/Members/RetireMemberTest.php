<?php

namespace Tests\Feature\End2End\Groups\Members;

use DateTime;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Actions\MemberAdd;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group groups
 * @group members
 */
class RetireMemberTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpGroupPersonAndMember;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->setupEntities()->setupMember();

        $this->url = 'api/groups/'.$this->group->uuid.'/members/'.$this->groupMember->id.'/retire/';
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function can_retire_member_from_group()
    {
        $endDate = (new DateTime());
        $response = $this->json('POST', $this->url, [
            'start_date' => $endDate->format(DateTime::ATOM),
            'end_date' => $endDate->format(DateTime::ATOM),
        ]);

        $response->assertStatus(200);
        $response->assertJson($this->groupMember->fresh()->toArray());

        $this->assertDatabaseHas('group_members', [
            'id' => $this->groupMember->id,
            'end_date' => $endDate->format('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * @test
     */
    public function validates_required_information()
    {
        $response = $this->json('POST', $this->url, []);
        $response->assertStatus(422);

        $response->assertJsonFragment([ 'errors' => [
            'start_date' => ['The start date field is required.'],
            'end_date' => ['The end date field is required.'],
        ]]);
    }

    /**
     * @test
     */
    public function validates_start_and_end_date_is_a_valid_date()
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
    public function validates_end_date_is_gte_start_date()
    {
        $response = $this->json(
            'POST',
            $this->url,
            [
                'start_date' => (new DateTime)->format(DateTime::ATOM),
                'end_date' => (new DateTime('yesterday'))->format(DateTime::ATOM)
            ]
        );
        $response->assertStatus(422);

        $response->assertJsonFragment([
            'end_date' => ['The end date must be a date after or equal to the member\'s start date.']
        ]);
    }
    
    

    /**
     * @test
     */
    public function logs_member_retired_activity()
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
