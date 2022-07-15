<?php

namespace Tests\Feature\End2End\Groups;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Comment;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Collection;
use App\Modules\Group\Models\Group;
use Illuminate\Testing\TestResponse;
use App\Modules\Person\Models\Person;
use Database\Seeders\CommentTypesSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Database\Seeders\NextActionTypesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Group\Actions\ApplicationSubmitStep;
use Database\Seeders\NextActionAssigneesTableSeeder;
use Database\Seeders\SubmissionTypeAndStatusSeeder;

class ApplicationSendToChairsTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        (new NextActionTypesTableSeeder)->run();
        (new NextActionAssigneesTableSeeder)->run();
        
        $this->user = $this->setupUser(permissions: ['ep-applications-manage']);
        Sanctum::actingAs($this->user);
        $this->expertPanel = ExpertPanel::factory()->vcep()->create();
        $this->group = $this->expertPanel->group;
    }

    /**
     * @test
     */
    public function unpermissioned_user_cannot_send_to_chairs()
    {
        $this->user->revokePermissionTo('ep-applications-manage');
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function next_action_assigned_to_oc_chairs()
    {
        $this->makeRequest()
            ->assertStatus(200);

        $this->assertDatabaseHas('next_actions', [
            'expert_panel_id' => $this->expertPanel->id,
            'entry' => 'Review Step '.$this->expertPanel->current_step.' application.',
            'assignee_id' => config('next_actions.assignees.chairs.id'),
            'type_id' => config('next_actions.types.chair-review.id')
        ]);
    }

    /**
     * @test
     */
    public function logs_that_chairs_sent_application()
    {
        $comments = $this->setupComments();
        $this->makeRequest()
            ->assertStatus(200);

        $this->assertLoggedActivity(
            activity_type: 'application-sent-to-chairs',
            subject: $this->group,
            description: 'Sent to CDWG OC Chairs: Step '.$this->expertPanel->current_step.' application',
            properties: [
                'additional_comments' =>  'These are my additional notes.',
                'comment_ids' => $comments->filter(fn($c) => is_null($c->resolved_at))->pluck('id'),
                'step' => $this->expertPanel->current_step
            ]
        );
    }
    
    /**
     * @test
     */
    public function pending_submission_status_updated_to_under_chair_review()
    {
        $this->runSeeder(SubmissionTypeAndStatusSeeder::class);
        $person = Person::factory()->create();
        $submission = app()->make(ApplicationSubmitStep::class)->handle($this->group, $person);
        $this->makeRequest();

        $this->assertDatabaseHas('submissions', [
            'id' => $submission->id,
            'submission_status_id' => config('submissions.statuses.under-chair-review.id')
        ]);
    }
    
    
    
    private function makeRequest(): TestResponse
    {
        return $this->json('POST', '/api/groups/'.$this->group->uuid.'/command', [
            'command' => 'app.modules.group.actions.applicationSendToChairs',
            'additionalComments' => 'These are my additional notes.'
        ]);
    }
    
    private function setupComments(): Collection
    {
        (new CommentTypesSeeder)->run();
        return collect([
            Comment::factory()->create([
                'subject_type' => get_class($this->group), 
                'subject_id' => $this->group->id, 
                'comment_type_id' => config('comments.types.internal-comment.id'),
                'metadata' => ['section' => 'membership'],
            ]),
            Comment::factory()->create([
                'subject_type' => get_class($this->group), 
                'subject_id' => $this->group->id, 
                'comment_type_id' => config('comments.types.suggestion.id'),
                'metadata' => ['section' => 'scope'],
            ]),
            Comment::factory()->create([
                'subject_type' => get_class($this->group), 
                'subject_id' => $this->group->id, 
                'comment_type_id' => config('comments.types.required-revision.id'),
                'metadata' => ['section' => 'scope'],
            ]),
            Comment::factory()->create([
                'subject_type' => get_class($this->group), 
                'subject_id' => $this->group->id, 
                'comment_type_id' => config('comments.types.required-revision.id'),
                'metadata' => ['section' => 'attestations'],
                'resolved_at' => Carbon::now()
            ]),
        ]);
    }
    
}
