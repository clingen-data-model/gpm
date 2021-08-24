<?php

namespace Tests\Feature\Integration\Notificaitons;

use Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Actions\StepApprove;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Notifications\UserDefinedMailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;

class NotifyStepApprovedTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        
        $this->expertPanel = ExpertPanel::factory()->create([
            'expert_panel_type_id' => config('expert_panels.types.gcep.id')
        ]);
        $this->addContactToApplication($this->expertPanel);
        $this->addContactToApplication($this->expertPanel);
    }

    /**
     * @test
     */
    public function does_not_send_StepApprovedNotification_if_notify_contacts_is_false()
    {
        Notification::fake();
        StepApprove::run($this->expertPanel->uuid, Carbon::now(), false);
        Notification::assertNothingSent();
    }

    /**
     * @test
     */
    public function does_not_send_StepApprovedNotification_if_is_last_step()
    {
        Notification::fake(UserDefinedMailNotification::class);
        StepApprove::run($this->expertPanel->uuid, Carbon::now(), true);
        Notification::assertNotSentTo($this->expertPanel->contacts, UserDefinedMailNotification::class);
    }
    
    /**
     * @test
     */
    public function sends_StepApprovedNotification_if_not_last_step_and_notify_contacts_is_true()
    {
        $this->expertPanel->update(['expert_panel_type_id' => config('expert_panels.types.vcep.id')]);

        Notification::fake(UserDefinedMailNotification::class);
        
        StepApprove::run($this->expertPanel->uuid, Carbon::now(), true);

        Notification::assertSentTo($this->expertPanel->contacts()->with('person')->get()->pluck('person'), UserDefinedMailNotification::class);
    }

    /**
     * @test
     */
    public function step_1_approved_email_carbon_copies_clingen_addresses()
    {
        $this->expertPanel->expert_panel_type_id = config('expert_panels.types.vcep.id');

        $clingenAddresses = config('expert-panels.cc_on_step_approved');

        $mailMessage = (new UserDefinedMailNotification($this->expertPanel, 1, false, ccAddresses: $clingenAddresses))
                            ->toMail($this->expertPanel->contacts->first());


        $this->assertEquals($clingenAddresses, $mailMessage->cc);
    }

    /**
     * @test
     */
    public function steps_2_through_4_approved_does_not_cc_clingen_addresses()
    {
        $this->expertPanel->expert_panel_type_id = config('expert_panels.types.vcep.id');

        $mailMessage = (new UserDefinedMailNotification($this->expertPanel, 2, false))
                            ->toMail($this->expertPanel->contacts->first());

        $this->assertEquals([], $mailMessage->cc);

        $mailMessage = (new UserDefinedMailNotification($this->expertPanel, 3, false))
                            ->toMail($this->expertPanel->contacts->first());

        $this->assertEquals([], $mailMessage->cc);

        $mailMessage = (new UserDefinedMailNotification($this->expertPanel, 4, false))
                            ->toMail($this->expertPanel->contacts->first());

        $this->assertEquals([], $mailMessage->cc);
    }
}
