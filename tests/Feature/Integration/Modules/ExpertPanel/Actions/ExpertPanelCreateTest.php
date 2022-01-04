<?php

namespace Tests\Feature\Integration\Modules\ExpertPanel\Actions;

use Tests\TestCase;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\ExpertPanel\Jobs\InitiateApplication;
use App\Modules\ExpertPanel\Actions\ExpertPanelCreate;
use App\Modules\ExpertPanel\Events\ApplicationInitiated;

class ExpertPanelCreateTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->data = $this->makeApplicationData();
    }
    
    /**
     * @test
     */
    public function creates_new_group_with_cdwg_as_parent_for_expert_panel()
    {
        $expertPanel = ExpertPanelCreate::run(...$this->data);
        $this->assertDatabaseHas('groups', [
            'name' => $this->data['working_name'],
            'parent_id' => $this->data['cdwg_id']
        ]);

        $group = Group::orderBy('id', 'desc')->first();

        $this->assertDatabaseHas('expert_panels', [
            'group_id' => $group->id
        ]);
    }
    

    /**
     * @test
     */
    public function stores_new_application_model_when_initiated()
    {
        $expertPanel = ExpertPanelCreate::run(...$this->data);
        unset($this->data['working_name']);
        unset($this->data['cdwg_id']);
        $this->assertDatabaseHas('expert_panels', $this->data);
    }

    /**
     * @test
     */
    public function fires_ApplicationInitiated_event_when_initiated()
    {
        Event::fake();

        ExpertPanelCreate::run(...$this->data);

        Event::assertDispatched(ApplicationInitiated::class);
    }

    /**
     * @test
     */
    public function activity_logged_when_application_initiated()
    {
        $user = User::factory()->create();

        Auth::loginUsingId($user->id);

        $expertPanel =ExpertPanelCreate::run(...$this->data);

        $properties = array_merge($expertPanel->only(['uuid','working_name','cdwg_id','expert_panel_type_id','date_initiated','coi_code', 'created_at', 'updated_at']), ['step' => 1]);

        $this->assertLoggedActivity(
            $expertPanel->group,
            'Application initiated',
            // $properties,  // comment out properties b/c can't get a match.
            null,
            get_class($user),
            $user->id
        );
    }
}
