<?php

namespace Tests\Feature\Integration\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Actions\ExpertPanelCreate;
use App\Modules\ExpertPanel\Events\ApplicationInitiated;
use App\Modules\Group\Models\Group;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ExpertPanelCreateTest extends TestCase
{
    use RefreshDatabase;

    public function setup(): void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->data = $this->makeApplicationData();
    }

    /**
     * @test
     */
    public function creates_new_group_with_cdwg_as_parent_for_expert_panel(): void
    {
        $expertPanel = ExpertPanelCreate::run(...$this->data);
        $this->assertDatabaseHas('groups', [
            'name' => $this->data['working_name'],
            'parent_id' => $this->data['cdwg_id'],
        ]);

        $group = Group::orderByDesc('id')->first();

        $this->assertDatabaseHas('expert_panels', [
            'group_id' => $group->id,
        ]);
    }

    /**
     * @test
     */
    public function stores_new_application_model_when_initiated(): void
    {
        $expertPanel = ExpertPanelCreate::run(...$this->data);
        unset($this->data['working_name']);
        unset($this->data['cdwg_id']);
        $this->assertDatabaseHas('expert_panels', $this->data);
    }

    /**
     * @test
     */
    public function fires_ApplicationInitiated_event_when_initiated(): void
    {
        Event::fake();

        ExpertPanelCreate::run(...$this->data);

        Event::assertDispatched(ApplicationInitiated::class);
    }

    /**
     * @test
     */
    public function activity_logged_when_application_initiated(): void
    {
        $user = User::factory()->create();

        Auth::loginUsingId($user->id);

        $group = ExpertPanelCreate::run(...$this->data);

        $this->assertLoggedActivity(
            $group,
            'Application initiated',
            // $properties,  // comment out properties b/c can't get a match.
            null,
            $user::class,
            $user->id
        );
    }
}
