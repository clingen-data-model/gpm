<?php

namespace Tests\Feature\End2End\Comments;

use App\Actions\CommentCreate;
use App\Actions\CommentUpdate;
use App\Modules\Group\Models\Submission;

class ApplicationReviewSummaryTest extends CommentTestAbstract
{
    public function test_counts_are_independent_by_step_category_and_state_for_each_panel_type(): void
    {
        $group = $this->expertPanel->group;
        $first = Submission::factory()->create(['group_id' => $group->id, 'submission_type_id' => 1,
            'created_at' => '2026-01-01']);
        $fourth = Submission::factory()->create(['group_id' => $group->id, 'submission_type_id' => 2,
            'created_at' => '2026-03-01']);
        foreach (config('comments.types') as $type) {
            foreach ([false, true] as $resolved) {
                foreach ([1 => 1, 4 => 2] as $step => $count) {
                    for ($i = 0; $i < $count * $type['id'] + (int) $resolved; $i++) {
                        $this->createComment(['subject_type' => get_class($group), 'subject_id' => $group->id,
                            'comment_type_id' => $type['id'], 'resolved_at' => $resolved ? now() : null,
                            'created_at' => $step === 1 ? '2026-02-01' : '2026-04-01']);
                    }
                }
            }
        }
        $this->expertPanel->update(['current_step' => 4]);
        $first->delete(); // Deleted historical rounds still establish comment context.
        foreach (['vcep', 'scvcep', 'gcep'] as $panelType) {
            $group->update(['group_type_id' => config('groups.types.'.$panelType.'.id')]);
            $response = $this->getJson('/api/groups/'.$group->uuid.'/application/review-summary')->assertOk();
            foreach (['required_revision' => 3, 'internal_comment' => 1, 'suggestion' => 2] as $category => $typeId) {
                foreach (['outstanding', 'resolved'] as $state) {
                    $response->assertJsonPath('step1Summary.'.$category.'.'.$state, $typeId + (int) ($state === 'resolved'));
                    if ($panelType !== 'gcep') $response->assertJsonPath('step4Summary.'.$category.'.'.$state, 2 * $typeId + (int) ($state === 'resolved'));
                }
            }
            if ($panelType === 'gcep') $response->assertJsonPath('step4Summary', null);
        }
    }

    public function test_creation_records_submission_and_edit_preserves_context(): void
    {
        $group = $this->expertPanel->group;
        $submission = Submission::factory()->create(['group_id' => $group->id, 'submission_type_id' => 1,
            'created_at' => now()->subDays(2)]);
        $comment = app(CommentCreate::class)->handle(['subject_type' => get_class($group), 'subject_id' => $group->id,
            'content' => 'Initial comment', 'comment_type_id' => 3]);
        $this->assertSame($submission->id, $comment->metadata['review_submission_id']);
        Submission::factory()->create(['group_id' => $group->id, 'submission_type_id' => 2,
            'created_at' => now()->subDay()]);
        app(CommentUpdate::class)->handle($comment, ['content' => 'Edited', 'metadata' => ['review_step' => 4]]);
        $this->assertSame(1, $comment->fresh()->metadata['review_step']);
        $next = app(CommentCreate::class)->handle(['subject_type' => get_class($group), 'subject_id' => $group->id,
            'content' => 'Step four comment', 'comment_type_id' => 2]);
        $this->assertSame(4, $next->metadata['review_step']);
        $this->getJson('/api/groups/'.$group->uuid.'/application/review-summary')->assertOk()
            ->assertJsonPath('step1Summary.required_revision.outstanding', 1);
    }
}
