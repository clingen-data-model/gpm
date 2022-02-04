<?php

namespace Tests\Feature\Integration\DX\Actions;

use Carbon\Carbon;
use Tests\TestCase;
use App\Modules\Group\Models\Group;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\DataExchange\Models\IncomingStreamMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\DataExchange\Actions\PilotRulesApprovedProcessor;
use App\DataExchange\Exceptions\DataSynchronizationException;

class PilotRulesApprovedProcessorTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->expertPanel = ExpertPanel::factory()->vcep()->create(['affiliation_id' => '50666']);
        $this->message = IncomingStreamMessage::factory()->pilotApproved()->make();
        
        $payload = (array)$this->message->payload;
        $payload = array_merge($payload, [
            'uuid' => $this->expertPanel->uuid,
            'affiliationId' => $this->expertPanel->affiliation_id
        ]);

        $this->message->payload = (object)$payload;

        $this->action = app()->make(PilotRulesApprovedProcessor::class);
    }

    /**
     * @test
     */
    public function it_throws_exception_if_expert_panel_is_not_draft_approved()
    {
        try {
            $this->action->handle($this->message);
            $this->fail('Expected DataSynchronization Exception.  None thrown.');
        } catch (DataSynchronizationException $e) {
            $expectedMessage = 'Received classified-rules-approved message, but expert panel '.$this->expertPanel->displayName.' is not draft approved.';
            $this->assertEquals($expectedMessage, $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function it_creates_a_new_SustainedCurationReviewTask_when_expert_panel_already_has_approved_pilot()
    {
        $this->expertPanel->step_1_approval_date = Carbon::parse('2020-01-01');
        $this->expertPanel->step_2_approval_date = Carbon::parse('2021-01-01');
        $this->expertPanel->step_3_approval_date = Carbon::parse('2021-06-01');
        $this->expertPanel->current_step = 3;
        $this->expertPanel->save();

        Carbon::setTestNow('2022-01-01');
        $this->message->timestamp = Carbon::now()->timestamp;
        $this->action->handle($this->message);

        $this->assertDatabaseHas('expert_panels', [
            'id' => $this->expertPanel->id,
            'step_1_approval_date' => Carbon::parse('2020-01-01'),
            'step_2_approval_date' => Carbon::parse('2021-01-01'),
            'step_3_approval_date' => Carbon::parse('2021-06-01'),
        ]);

        $this->assertDatabaseHas('tasks', [
            'assignee_type' => Group::class,
            'assignee_id' => $this->expertPanel->group_id,
            'task_type_id' => config('tasks.types.sustained-curation-review.id')
        ]);
    }

    /**
     * @test
     */
    public function it_approves_pilot_specifications_if_ep_draftApproved_and_not_pilotApproved()
    {
        $this->expertPanel->step_1_approval_date = Carbon::parse('2020-01-01');
        $this->expertPanel->step_2_approval_date = Carbon::parse('2021-01-01');
        $this->expertPanel->current_step = 3;
        $this->expertPanel->save();
 
        Carbon::setTestNow('2022-01-01');

        $this->message->timestamp = Carbon::now()->timestamp;
        $this->action->handle($this->message);

        $this->assertDatabaseHas('expert_panels', [
            'id' => $this->expertPanel->id,
            'step_1_approval_date' => Carbon::parse('2020-01-01'),
            'step_2_approval_date' => Carbon::parse('2021-01-01'),
            'step_3_approval_date' => Carbon::now(),
            'current_step' => 4
        ]);
    }
}
