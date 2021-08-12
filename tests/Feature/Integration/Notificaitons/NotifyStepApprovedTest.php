<?php

namespace Tests\Feature\Integration\Notificaitons;

use Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Jobs\ApproveStep;
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
            'expert_panel_type' => config('expert_panels.types.gcep.id')
        ]);
        $this->addContactToApplication($this->expertPanel);
        $this->addContactToApplication($this->expertPanel);
    }

    /**
     * @test
     */
    public function does_not_send_StepApprovedNotification_if_notify_contacts_is_false()
    {
        $job = new ApproveStep($this->expertPanel->uuid, Carbon::now(), false);
        Notification::fake();
        Bus::dispatch($job);

        Notification::assertNothingSent();
    }

    /**
     * @test
     */
    public function does_not_send_StepApprovedNotification_if_is_last_step()
    {
        $job = new ApproveStep($this->expertPanel->uuid, Carbon::now(), true);
        Notification::fake(UserDefinedMailNotification::class);

        Bus::dispatch($job);

        Notification::assertSentTo($this->expertPanel->contacts, UserDefinedMailNotification::class);
    }
    
    /**
     * @test
     */
    public function sends_StepApprovedNotification_if_not_last_step_and_notify_contacts_is_true()
    {
        $this->expertPanel->expert_panel_type_id = config('expert_panels.types.vcep.id');
        $this->expertPanel->save();
        $job = new ApproveStep($this->expertPanel->uuid, Carbon::now(), true);
        Notification::fake(UserDefinedMailNotification::class);

        Bus::dispatch($job);

        Notification::assertSentTo($this->expertPanel->contacts, UserDefinedMailNotification::class);
    }

    /**
     * @test
     */
    public function step_1_approved_email_carbon_copies_clingen_addresses()
    {
        $this->expertPanel->expert_panel_type_id = config('expert_panels.types.vcep.id');
        $this->expertPanel->current_step = 1;
        $this->expertPanel->save();

        Notification::fake();

        Bus::dispatch(new ApproveStep(
            expertPanelUuid: $this->expertPanel->uuid,
            dateApproved: '2020-01-01',
            notifyContacts: true,
        ));

        Notification::assertSentTo(
            $this->expertPanel->contacts,
            UserDefinedMailNotification::class,
            function ($notification) {
                return $notification->ccAddresses == config('expert_panels.notifications.cc.recipients');
            }
        );
    }

    /**
     * @test
     */
    public function step_4_approved_email_carbon_copies_clingen_addresses()
    {
        $this->expertPanel->expert_panel_type_id = config('expert_panels.types.vcep.id');

        $this->expertPanel->current_step = 4;
        $this->expertPanel->save();

        Notification::fake();

        Bus::dispatch(new ApproveStep(
            expertPanelUuid: $this->expertPanel->uuid,
            dateApproved: '2020-01-01',
            notifyContacts: true,
        ));

        Notification::assertSentTo(
            $this->expertPanel->contacts,
            UserDefinedMailNotification::class,
            function ($notification) {
                return $notification->ccAddresses == config('expert_panels.notifications.cc.recipients');
            }
        );
    }

    /**
     * @test
     */
    public function steps_2_approved_does_not_cc_clingen_addresses()
    {
        $this->expertPanel->expert_panel_type_id = config('expert_panels.types.vcep.id');

        $this->expertPanel->current_step = 2;
        $this->expertPanel->save();

        Notification::fake();

        Bus::dispatch(new ApproveStep(
            expertPanelUuid: $this->expertPanel->uuid,
            dateApproved: '2020-01-01',
            notifyContacts: true,
        ));

        Notification::assertSentTo(
            $this->expertPanel->contacts,
            UserDefinedMailNotification::class,
            function ($notification) {
                return $notification->ccAddresses == [];
            }
        );
    }

    /**
     * @test
     */
    public function steps_3_approved_does_not_cc_clingen_addresses()
    {
        $this->expertPanel->expert_panel_type_id = config('expert_panels.types.vcep.id');

        $this->expertPanel->current_step = 3;
        $this->expertPanel->save();

        Notification::fake();

        Bus::dispatch(new ApproveStep(
            expertPanelUuid: $this->expertPanel->uuid,
            dateApproved: '2020-01-01',
            notifyContacts: true,
        ));

        Notification::assertSentTo(
            $this->expertPanel->contacts,
            UserDefinedMailNotification::class,
            function ($notification) {
                return $notification->ccAddresses == [];
            }
        );
    }
}
