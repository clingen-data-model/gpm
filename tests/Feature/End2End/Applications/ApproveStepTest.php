<?php

namespace Tests\Feature\End2End\Applications;

use Tests\TestCase;
use App\Models\User;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Carbon;
use App\Modules\Person\Models\Person;
use App\Modules\Application\Jobs\AddContact;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use App\Modules\Application\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Application\Notifications\ApplicationStepApprovedNotification;

class ApproveStepTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->application = Application::factory()->vcep()->create();
        $this->user = User::factory()->create();
    }

    /**
     * @test
     */
    public function can_approve_step()
    {
        $approvalData = [
            'date_approved' => Carbon::now(),
        ];

        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $this->json('POST', '/api/applications/'.$this->application->uuid.'/current-step/approve', $approvalData)
            ->assertStatus(200)
            ->assertJson($this->application->fresh()->toArray());

        $this->assertDatabaseHas('applications', [
            'uuid' => $this->application->uuid,
            'current_step' => (string)2,
            'approval_dates' => json_encode([
                'step 1' => $approvalData['date_approved'],
            ]),
        ]);
    }
    
    /**
     * @test
     */
    public function returns_404_if_application_not_found()
    {
        $approvalData = [
            'date_approved' => Carbon::now(),
        ];

        $badUuid = Uuid::uuid4();

        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $this->json('POST', '/api/applications/'.$badUuid.'/current-step/approve', $approvalData)
            ->assertStatus(404);
    }
    
    /**
     * @test
     */
    public function validates_date_approved()
    {
        $approvalData = [
            'date_approved' => 'Carbon::now()',
        ];

        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $this->json('POST', '/api/applications/'.$this->application->uuid.'/current-step/approve', $approvalData)
            ->assertStatus(422)
            ->assertJsonFragment(['date_approved' => ['The date approved is not a valid date.']]);
    }
    
    /**
     * @test
     */
    public function sends_no_notifications_if_not_specified()
    {
        $approvalData = [
            'date_approved' => Carbon::now(),
        ];

        Notification::fake();
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $this->json('POST', '/api/applications/'.$this->application->uuid.'/current-step/approve', $approvalData)
            ->assertStatus(200);

        Notification::assertNothingSent();
    }

    /**
     * @test
     */
    public function sends_notification_to_contacts_if_specified()
    {
        $person = Person::factory()->create();
        AddContact::dispatch($this->application->uuid, $person->uuid);

        $approvalData = [
            'date_approved' => Carbon::now(),
            'notify_contacts' => true
        ];

        Notification::fake();
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $this->json('POST', '/api/applications/'.$this->application->uuid.'/current-step/approve', $approvalData)
            ->assertStatus(200);

        Notification::assertSentTo($person, ApplicationStepApprovedNotification::class);
    }
    
    /**
     * @test
     */
    // public function sends_notification_to_system_admins_if_specified()
    // {
    //     $person = Person::factory()->create();
    //     AddContact::dispatch($this->application->uuid, $person->uuid);

    //     $approvalData = [
    //         'date_approved' => Carbon::now(),
    //         'notify_contacts' => true
    //     ];

    //     Notification::fake();
    //     \Laravel\Sanctum\Sanctum::actingAs($this->user);
    //     $this->json('POST', '/api/applications/'.$this->application->uuid.'/current-step/approve', $approvalData)
    //         ->assertStatus(200);

    //     Notification::assertSentTo($person, ApplicationStepApprovedNotification::class);
    // }
    
    
}
