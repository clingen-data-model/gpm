<?php

namespace Tests\Feature\Integration\Notificaitons;

use Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Application\Jobs\ApproveStep;
use App\Modules\Application\Models\Application;
use App\Modules\Application\Notifications\ApplicationStepApprovedNotification;
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
        
        $this->application = Application::factory()->create([
            'ep_type_id' => config('expert_panels.types.gcep.id')
        ]);
        $this->addContactToApplication($this->application);
        $this->addContactToApplication($this->application);
    }

    /**
     * @test
     */
    public function does_not_send_StepApprovedNotification_if_notify_contacts_is_false()
    {
        $job = new ApproveStep($this->application->uuid, Carbon::now(), false);
        Notification::fake();
        Bus::dispatch($job);

        Notification::assertNothingSent();
    }

    /**
     * @test
     */
    public function does_not_send_StepApprovedNotification_if_is_last_step()
    {
        $job = new ApproveStep($this->application->uuid, Carbon::now(), true);
        Notification::fake(ApplicationStepApprovedNotification::class);

        Bus::dispatch($job);

        Notification::assertNothingSent();
    }
    
    /**
     * @test
     */
    public function sends_StepApprovedNotification_if_not_last_step_and_notify_contacts_is_true()
    {
        $this->application->ep_type_id = config('expert_panels.types.vcep.id');
        $this->application->save();
        $job = new ApproveStep($this->application->uuid, Carbon::now(), true);
        Notification::fake(ApplicationStepApprovedNotification::class);

        Bus::dispatch($job);

        Notification::assertSentTo($this->application->contacts, ApplicationStepApprovedNotification::class);        
    }
    
    
}
