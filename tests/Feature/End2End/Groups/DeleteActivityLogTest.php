<?php

namespace Tests\Feature\End2End\Groups;

use App\Models\Activity;
use App\Modules\Group\Models\Group as GroupModel;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('groups')]
#[Group('activity-log')]
class DeleteActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->user = $this->setupUser(permissions: ['groups-manage']);
        $this->group = GroupModel::factory()->create();
        $this->logEntry = Activity::factory()->create([
            'subject_type' => GroupModel::class,
            'subject_id' => $this->group->id
        ]);

        $this->url = '/api/groups/'.$this->group->uuid.'/activity-logs/'.$this->logEntry->id;

        Sanctum::actingAs($this->user);
    }

    #[Test]
    public function unprivileged_user_cannot_delete_a_log_entry()
    {
        $this->user->RevokePermissionTo('groups-manage');
        $this->json('delete', $this->url)
            ->assertStatus(403);
    }

    #[Test]
    public function privileged_user_can_delete_a_log_entry()
    {
        $this->json('delete', $this->url)
            ->assertStatus(200);

        $this->assertDatabaseMissing('activity_log', [
            'id' => $this->logEntry->id
        ]);
    }
}
