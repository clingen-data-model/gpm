<?php

namespace Tests\Feature\End2End\Groups\Submissions;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Mail\UserDefinedMailable;
use Illuminate\Support\Facades\Mail;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Models\Submission;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Actions\ContactAdd;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\SubmissionTypeAndStatusSeeder;

class RejectSubmissionTest extends TestCase
{
    use RefreshDatabase;

    const NOTE = 'This is a note about the don\'t-call-it-a-rejection.';

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();    
        $this->expertPanel = ExpertPanel::factory()->create();
        $this->admin = $this->setupUserWithPerson(null, ['ep-applications-manage']);
        
        (new SubmissionTypeAndStatusSeeder)->run();
        $this->submission = Submission::factory()
                                ->create([
                                    'group_id' => $this->expertPanel->group_id,
                                    'submission_type_id' => config('submissions.types.application.definition.id'),
                                    'submitter_id' => $this->admin->person->id
                                ]);
        
        Sanctum::actingAs($this->admin);
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_reject_submission()
    {
        $this->admin->revokePermissionTo('ep-applications-manage');

        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function permissioned_user_can_reject_a_submission()
    {
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJson([
                'id' => $this->submission->id,
                'submission_status_id' => config('submissions.statuses.revise-and-resubmit.id'),
            ]);
        
        $this->assertDatabaseHas('submissions', [
            'id' => $this->submission->id,
            'submission_status_id' => config('submissions.statuses.revise-and-resubmit'),
        ]);
    }

    /**
     * @test
     */
    public function emails_group_contacts_when_specified()
    {
        Mail::fake();
        $data = $this->makeDefaultData(['notify_contacts' => true]);

        $person1 = Person::factory()->create();
        ContactAdd::run($this->expertPanel->uuid, $person1->uuid);

        $person2 = Person::factory()->create();
        ContactAdd::run($this->expertPanel->uuid, $person2->uuid);

        $this->makeRequest($data);

        Mail::assertSent(
            UserDefinedMailable::class,
            function ($mail) use ($data, $person1, $person2) {
                return $mail->subject == $data['subject']
                    && $mail->body == $data['body']
                    && $mail->attachments == []
                    && $mail->hasTo($person1->email)
                    && $mail->hasTo($person2->email)
                ;
            }
        );
    }
    
    

    private function makeRequest($data = null)
    {
        $data = $data ?? $this->makeDefaultData();

        return $this->json('POST', '/api/groups/'.$this->expertPanel->group->uuid.'/application/submission/'.$this->submission->id.'/rejection', $data);
    }

    private function makeDefaultData($mergeData = [])
    {
        return array_merge([
            'notify_contacts' => false,
            'subject' => 'Revise and resubmit your application for '.$this->expertPanel->group->name,
            'notes' => static::NOTE,
            'body' => static::NOTE
        ], $mergeData);
    }
    
    
    
    
}
