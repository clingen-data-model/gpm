<?php

namespace App\Modules\Group\Actions\ScopeOfWork;

use App\Modules\ExpertPanel\Actions\NextActionComplete;
use App\Modules\Group\Events\ApplicationStepSubmitted;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\ScopeOfWorkChange;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use App\Modules\Group\Models\Submission;
use App\Modules\Person\Models\Person;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsObject;

class RevisionSubmit
{
    use AsObject;
    use AsController;

    public function handle(
        Group $group,
        ScopeOfWorkVersion $revision,
        Person $submitter,
        ?string $notes = null
    ): ScopeOfWorkVersion {
        if ($revision->group_id !== $group->id) {
            abort(404);
        }

        if (!in_array($revision->status, [
            ScopeOfWorkVersion::STATUS_DRAFT,
            ScopeOfWorkVersion::STATUS_REVISIONS_REQUESTED,
        ], true)) {
            throw ValidationException::withMessages([
                'revision' => 'Only draft or revisions-requested Scope of Work revisions can be submitted.',
            ]);
        }

        $revision->load('changes');

        if ($revision->changes->isEmpty()) {
            throw ValidationException::withMessages([
                'revision' => 'This Scope of Work revision does not have any changes to submit.',
            ]);
        }

        $requiresApproval = $revision->changes->contains(function (ScopeOfWorkChange $change) {
            return in_array($change->requires_approval, [
                ScopeOfWorkChange::APPROVAL_YES,
                ScopeOfWorkChange::APPROVAL_CONDITIONAL,
            ], true);
        });

        if (!$requiresApproval) {
            throw ValidationException::withMessages([
                'revision' => 'This Scope of Work revision does not require approval and can be finalized directly.',
            ]);
        }

        return DB::transaction(function () use ($group, $revision, $submitter, $notes) {
            $submissionType = $this->resolveSubmissionType($revision);

            $submission = new Submission([
                'scope_of_work_version_id' => $revision->id,
                'submission_type_id' => $submissionType->id,
                'submission_status_id' => config('submissions.statuses.pending.id'),
                'notes' => $notes,
                'submitter_id' => $submitter->id,
                'data' => [
                    'context' => 'scope_of_work_revision',
                    'scope_of_work_version_id' => $revision->id,
                    'scope_of_work_version_uuid' => $revision->uuid,
                    'target_version' => $revision->version_label,
                    'base_version_id' => $revision->base_version_id,
                    'approval_step' => $this->approvalStep($revision),
                    'changes' => $revision->changes->map(fn ($change) => [
                        'id' => $change->id,
                        'rule_key' => $change->rule_key,
                        'label' => $change->label,
                        'entity_label' => $change->entity_label,
                        'impact' => $change->impact,
                        'requires_approval' => $change->requires_approval,
                        'approval_step' => $change->approval_step,
                        'approvers' => $change->approvers,
                    ])->values()->all(),
                ],
            ]);

            $submission = $group->submissions()->save($submission);
            $submission = $submission->fresh()->load(['status', 'type']);
            $submission->wasRecentlyCreated = true;

            $revision->update([
                'status' => ScopeOfWorkVersion::STATUS_SUBMITTED,
                'submission_id' => $submission->id,
                'submitted_by' => Auth::id(),
                'submitted_at' => now(),
            ]);

            $this->setReceivedDate($group, $this->approvalStep($revision));

            event(new ApplicationStepSubmitted($group, $submission));

            $group->expertPanel->nextActions()
                ->ofType(config('next_actions.types.make-revisions.id'))
                ->each(function ($nextAction) use ($group) {
                    app()->make(NextActionComplete::class)->handle(
                        expertPanel: $group->expertPanel,
                        nextAction: $nextAction,
                        dateCompleted: Carbon::now()
                    );
                });

            return $revision->fresh(['changes', 'latestSnapshot', 'baseVersion']);
        });
    }

    public function asController(ActionRequest $request, Group $group, ScopeOfWorkVersion $scopeOfWorkVersion)
    {
        $revision = $this->handle(
            group: $group,
            revision: $scopeOfWorkVersion,
            submitter: $request->user()->person,
            notes: $request->notes
        );

        return StatusGet::run($group);
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('updateApplicationAttribute', $request->group);
    }

    public function rules(): array
    {
        return [
            'notes' => ['required', 'string'],
        ];
    }

    private function resolveSubmissionType(ScopeOfWorkVersion $revision): object
    {
        return match ($this->approvalStep($revision)) {
            4 => (object) config('submissions.types.application.sustained-curation'),
            default => (object) config('submissions.types.application.definition'),
        };
    }

    private function approvalStep(ScopeOfWorkVersion $revision): int
    {
        return (int) $revision->changes
            ->pluck('approval_step')
            ->filter()
            ->min() ?: 1;
    }

    private function setReceivedDate(Group $group, int $approvalStep): void
    {
        if ($approvalStep === 1) {
            $group->expertPanel->step_1_received_date = Carbon::now();
        }

        if ($approvalStep === 4) {
            $group->expertPanel->step_4_received_date = Carbon::now();
        }

        $group->expertPanel->save();
    }
}