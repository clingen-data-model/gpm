<?php

namespace Tests\Feature\Integration\Notificaitons;

use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Mail\UserDefinedMailable;

use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Actions\StepApprove;
use App\Notifications\UserDefinedMailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Application\Notifications\ApplicationStepApprovedNotification;

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
        StepApprove::run($this->expertPanel, Carbon::now(), false);
        Notification::assertNothingSent();
    }

    /**
     * @test
     */
    public function does_not_send_StepApprovedNotification_if_is_last_step()
    {
        Notification::fake(UserDefinedMailNotification::class);
        StepApprove::run($this->expertPanel, Carbon::now(), true);
        Notification::assertNotSentTo($this->expertPanel->contacts, UserDefinedMailNotification::class);
    }
    
    /**
     * @test
     */
    public function sends_StepApprovedNotification_if_not_last_step_and_notify_contacts_is_true()
    {
        $this->expertPanel->update(['expert_panel_type_id' => config('expert_panels.types.vcep.id')]);

        Mail::fake();
        
        $this->runApproveStep();

        Mail::assertSent(
            UserDefinedMailable::class,
            function ($mail) {
                $contacts = $this->expertPanel->contacts()->with('person')->get()->pluck('person');
                foreach ($contacts as $contact) {
                    if (!$mail->hasTo($contact->email)) {
                        return false;
                    }
                }
                return true;
            }
        );
    }

    /**
     * @test
     */
    public function step_1_approved_email_carbon_copies_clingen_addresses()
    {
        $this->expertPanel->expert_panel_type_id = config('expert_panels.types.vcep.id');
        $this->expertPanel->current_step = 1;
        $this->expertPanel->save();

        $contacts = $this->expertPanel->contacts->pluck('person');

        Mail::fake();

        $this->runApproveStep();

        Mail::assertSent(
            UserDefinedMailable::class,
            function ($mail) {
                foreach (config('expert-panels.notifications.cc.recipients') as $cc) {
                    if (!$mail->hasCc($cc)) {
                        return false;
                    }
                }
                return true;
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
        
        Mail::fake();
        $this->runApproveStep();
        
        $expectedCcs = config('expert-panels.notifications.cc.recipients');
        array_push($expectedCcs, ['clinvar@ncbi.nlm.nih.gov', 'ClinVar']);
        
        
        Mail::assertSent(
            UserDefinedMailable::class,
            function ($mail) use ($expectedCcs) {
                foreach ($expectedCcs as $cc) {
                    if (!$mail->hasCc($cc)) {
                        return false;
                    }
                }
                return true;
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


        Mail::fake();

        $this->runApproveStep();

        Mail::assertSent(
            UserDefinedMailable::class,
            function ($mail) {
                return $mail->cc == [];
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

        Mail::fake();

        $this->runApproveStep();

        Mail::assertSent(
            UserDefinedMailable::class,
            function ($mail) {
                return $mail->cc == [];
            }
        );
    }

    private function runApproveStep($data = null)
    {
        $data = $data ?? [
            'expertPanel' => $this->expertPanel,
            'dateApproved' => Carbon::now(),
            'notifyContacts' => true,
            'subject' => 'Some subject',
            'body' => 'some body'
        ];
        StepApprove::run(...$data);

        return $data;
    }
}
