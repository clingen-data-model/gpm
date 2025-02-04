<?php

namespace Tests\Feature\End2End\ExpertPanels;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Document;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use App\Modules\ExpertPanel\Models\Coi;
use App\Modules\Group\Actions\MemberAdd;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

/**
 * @group coi
 */
class SimpleCoiEndpointTest extends TestCase
{
    use FastRefreshDatabase;

    private $group;
    private $groupMember;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->group = Group::factory()->create();
        $user = $this->setupUserWithPerson();
        $this->groupMember = GroupMember::factory()->create(['group_id' => $this->group, 'person_id' => $user->person]);
        $this->actingAs($user);
    }

    /**
     * @test
     */
    public function can_get_group_from_coi_code()
    {
        $this->json('GET', '/api/coi/'.$this->group->coi_code.'/group')
            ->assertStatus(200)
            ->assertJson(['id' => $this->group->id, 'name' => $this->group->name]);
    }

    /**
     * @test
     */
    public function returns_404_if_group_not_found_for_code()
    {
        $this->json('GET', '/api/coi/some-fake-code/group')
            ->assertStatus(404);
    }

    private function signInAsMember()
    {
        $user = $this->loginWithPerson();
        (new (app(MemberAdd::class)))()->handle($this->group, $user->person);
    }
}
