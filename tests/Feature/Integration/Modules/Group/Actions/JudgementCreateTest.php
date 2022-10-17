<?php

namespace Tests\Feature\Integration\Modules\Group\Actions;

use Tests\TestCase;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use App\Modules\Group\Models\Submission;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Group\Actions\JudgementCreate;
use App\Modules\Group\Events\JudgementCreated;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JudgementCreateTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->expertPanel = ExpertPanel::factory()->create();
        $this->submission = Submission::factory()->create(['group_id' => $this->expertPanel->group_id]);
        $this->person = Person::factory()->create();
    }


    /**
     * @test
     */
    public function fires_JudgementCreated_test()
    {
        $action = app()->make(JudgementCreate::class);

        Event::fake();
        $action->handle($this->submission->group, $this->person, 'approve');

        Event::assertDispatched(JudgementCreated::class);

    }

}
