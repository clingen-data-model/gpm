<?php

namespace Tests\Feature\Integration\DX\Actions;

use App\DataExchange\Actions\ClassifiedRulesApprovedProcessor;
use App\DataExchange\Exceptions\DataSynchronizationException;
use App\DataExchange\Models\IncomingStreamMessage;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ClassifiedRulesApprovedProcessorTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->expertPanel = ExpertPanel::factory()->vcep()->create(['affiliation_id' => '50666']);
        $this->message = IncomingStreamMessage::factory()->draftApproved()->make();
        
        $payload = (array)$this->message->payload;
        $payload = array_merge($payload, [
            'uuid' => $this->expertPanel->uuid,
            'affiliationId' => $this->expertPanel->affiliation_id
        ]);

        $this->message->payload = (object)$payload;

        $this->action = app()->make(ClassifiedRulesApprovedProcessor::class);
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
            $expectedMessage = 'Received classified-rules-approved message, but expert panel '.$this->expertPanel->displayName.' is not definition approved.';
            $this->assertEquals($expectedMessage, $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function it_throws_exception_if_expert_panel_not_draft_approved_current_step_not_2()
    {
        $this->expertPanel->step_1_approval_date = Carbon::parse('2020-01-01');
        $this->expertPanel->save();

        try {
            $this->action->handle($this->message);
            $this->fail('Expected DataSynchronization Exception.  None thrown.');
        } catch (DataSynchronizationException $e) {
            $expectedMessage = 'Received classified-rules-approved message, but expert panel '.$this->expertPanel->displayName.' has current_step == 1 even though has step_1_approval_date.';
            $this->assertEquals($expectedMessage, $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function it_does_nothing_when_expert_panel_already_has_approved_draft()
    {
        $this->expertPanel->step_1_approval_date = Carbon::parse('2020-01-01');
        $this->expertPanel->step_2_approval_date = Carbon::parse('2021-01-01');
        $this->expertPanel->current_step = 3;
        $this->expertPanel->save();

        Carbon::setTestNow('2022-01-01');
        $this->action->handle($this->message);

        $this->assertDatabaseHas('expert_panels', [
            'id' => $this->expertPanel->id,
            'step_1_approval_date' => Carbon::parse('2020-01-01'),
            'step_2_approval_date' => Carbon::parse('2021-01-01')
        ]);
    }

    /**
     * @test
     */
    public function it_approves_draft_specifications_if_ep_defApproved_and_not_draftSpecApproved()
    {
        $this->expertPanel->step_1_approval_date = Carbon::parse('2020-01-01');
        $this->expertPanel->current_step = 2;
        $this->expertPanel->save();
 
        Carbon::setTestNow('2022-01-01');

        $this->message->timestamp = Carbon::now()->timestamp;
        $this->action->handle($this->message);

        $this->assertDatabaseHas('expert_panels', [
            'id' => $this->expertPanel->id,
            'step_1_approval_date' => Carbon::parse('2020-01-01'),
            'step_2_approval_date' => Carbon::now()
        ]);
    }
}
