<?php

namespace App\Modules\Group\Services;

use App\Modules\Group\Actions\ScopeOfWork\ReviewRoundComparisonGet;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use App\Modules\Group\Models\Submission;

class ApplicationReviewHistory
{
    public function __construct(private ApplicationSnapshotResolver $snapshots) {}

    public function classification(Submission $submission): string
    {
        $data = $submission->data ?? [];
        $step = app(ApplicationReviewPresentation::class)->step($submission);
        $typeStep = (int) $submission->submission_type_id === (int) config('submissions.types.application.sustained-curation.id') ? 4
            : ((int) $submission->submission_type_id === (int) config('submissions.types.application.definition.id') ? 1 : null);
        if ($typeStep === null) return 'excluded';
        if ($step !== $typeStep || (isset($data['step']) && (int) $data['step'] !== $typeStep)) return 'ambiguous';
        if ($step === 4 && $submission->group->isGcep) return 'excluded';
        $context = $data['context'] ?? null;
        if ($submission->scope_of_work_version_id || $context === 'scope_of_work_revision'
            || isset($data['scope_of_work_version_id'])) {
            $version = ScopeOfWorkVersion::withTrashed()->find($submission->scope_of_work_version_id);
            if (!$version || $version->group_id !== $submission->group_id
                || $context !== 'scope_of_work_revision'
                || (isset($data['scope_of_work_version_id']) && (int) $data['scope_of_work_version_id'] !== $version->id)) {
                return 'ambiguous';
            }
            return 'scope_of_work';
        }
        return in_array($context, [null, 'application_submission'], true) ? ($step === 4 ? 'sustained_curation' : 'initial_application') : 'ambiguous';
    }

    public function handle(Group $group): array
    {
        $submissions = Submission::withTrashed()->where('group_id', $group->id)
            ->with(['submitter', 'judgements.person'])->orderBy('created_at')->orderBy('id')->get();
        $cycles = [];
        $ambiguous = [];
        foreach ($submissions as $submission) {
            $kind = $this->classification($submission);
            if ($kind === 'excluded') continue;
            if ($kind === 'ambiguous') {
                $ambiguous[] = ['submission_id' => $submission->id, 'submitted_at' => $submission->created_at?->toISOString(),
                    'reason' => 'Submission review cycle metadata is ambiguous.'];
                continue;
            }
            $key = $kind !== 'scope_of_work' ? $kind : 'scope_of_work:'.$submission->scope_of_work_version_id.':step:'.app(ApplicationReviewPresentation::class)->step($submission);
            $cycles[$key] ??= ['kind' => $kind, 'submissions' => []];
            $cycles[$key]['submissions'][] = $submission;
        }
        $result = [];
        $initialVersion = ScopeOfWorkVersion::where('group_id', $group->id)->where('major_version', 1)
            ->where('minor_version', 0)->where('status', 'approved')->first();
        $initialLink = false;
        foreach ($cycles as $key => $cycle) {
            $items = collect($cycle['submissions']);
            $initial = $cycle['kind'] !== 'scope_of_work';
            $step = app(ApplicationReviewPresentation::class)->step($items->first());
            $step4 = $step === 4;
            $version = $initial ? null : ScopeOfWorkVersion::withTrashed()->find($items->first()->scope_of_work_version_id);
            $approved = $items->where('submission_status_id', config('submissions.statuses.approved.id'));
            $linked = $version && $version->status === 'approved'
                ? $approved->firstWhere('id', $version->submission_id) : null;
            $initialLinked = $initial && !$step4 && $initialVersion?->submission_id
                ? $approved->firstWhere('id', $initialVersion->submission_id) : null;
            $initialLink = $initialLink || (bool) $initialLinked;
            $rounds = [];
            foreach ($items as $index => $submission) {
                $resolved = $this->snapshots->resolve($submission);
                $previous = $index ? $items[$index - 1] : null;
                $detail = $this->detailMetadata($group, $submission, $initial, $previous, $resolved);
                $rounds[] = [
                    'submission_id' => $submission->id, 'review_round' => $index + 1,
                    'submitted_at' => $submission->created_at?->toISOString(),
                    'status' => $submission->status?->name, 'closed_at' => $submission->closed_at?->toISOString(),
                    'is_current_review' => !$submission->trashed() && $submission->is_pending && !$submission->closed_at
                        && ($initial ? $submission->id === $items->last()->id
                            : $version->status === 'submitted' && $version->submission_id === $submission->id),
                    'submitted_by' => $this->person($submission->submitter),
                    'submitter_notes' => $submission->notes, 'revisions_requested_notes' => $submission->response_content,
                    'reviewed_by' => $linked?->id === $submission->id && $version?->approver
                        ? ['user_id' => $version->approver->id, 'name' => $version->approver->name] : null,
                    'reviewer_judgements' => $submission->judgements->map(fn ($judgement) => [
                        'id' => $judgement->id, 'decision' => $judgement->decision, 'notes' => $judgement->notes,
                        'reviewer' => $this->person($judgement->person),
                    ])->values()->all(),
                    'snapshot' => ['id' => $resolved['snapshot']?->id, 'availability' => $resolved['availability']],
                    'detail' => $detail,
                ];
            }
            $result[] = [
                'key' => $key, 'kind' => $cycle['kind'],
                'title' => $initial ? 'Initial Application' : 'Scope of Work Version '.$version->version_label,
                'lifecycle_key' => $initial ? 'initial_application' : 'scope_of_work:'.$version->id,
                'step' => $step, 'step_name' => app(ApplicationReviewPresentation::class)->stepName($step),
                'scope_of_work_version' => $this->version($initialLinked ? $initialVersion : $version),
                'base_version' => $version && $version->baseVersion?->group_id === $group->id ? $this->version($version->baseVersion) : null,
                'approval' => ['submission_id' => $initial ? ($initialLinked?->id ?? ($approved->count() === 1 ? $approved->first()->id : null)) : $linked?->id,
                    'relationship' => $initial ? ($initialLinked ? 'explicit' : ($approved->isNotEmpty() ? 'unrecorded' : 'not_approved'))
                        : ($linked ? 'explicit' : ($version->status === 'approved' ? 'unavailable' : 'not_approved'))],
                'rounds' => $rounds,
            ];
        }
        return ['group_uuid' => $group->uuid, 'cycles' => array_reverse($result), 'ambiguous_submissions' => $ambiguous,
            'initial_scope_of_work_version' => $this->version($initialVersion),
            'initial_version_relationship' => $initialLink ? 'explicit' : 'unrecorded',
            'initial_step_1_approval' => ['approved_at' => $group->expertPanel?->step_1_approval_date,
                'relationship' => 'unrecorded']];
    }

    private function detailMetadata(Group $group, Submission $submission, bool $initial, ?Submission $previous, array $resolved): array
    {
        if (!$initial) {
            $comparison = app(ReviewRoundComparisonGet::class)->handle($group, $submission);
            $previousId = $comparison['before']['submission_id'];
            $previousRound = $previousId ? Submission::withTrashed()->find($previousId) : null;
            $previousAllowed = !$previousRound || $this->classification($previousRound) === 'scope_of_work';
            return ['mode' => $comparison['mode'], 'previous_submission_id' => $comparison['before']['submission_id'],
                'availability' => $previousAllowed && $comparison['before']['snapshot_id'] && $comparison['after']['snapshot_id'] ? 'available' : 'unavailable'];
        }
        $before = $previous ? $this->snapshots->resolve($previous) : null;
        return ['mode' => $previous ? 'previous_review_round' : 'submitted_state',
            'previous_submission_id' => $previous?->id,
            'availability' => $resolved['availability'] !== 'available' ? $resolved['availability']
                : ($before ? $before['availability'] : 'available')];
    }

    public function detail(Group $group, Submission $submission): array
    {
        abort_unless($submission->group_id === $group->id, 404);
        $history = $this->handle($group);
        foreach ($history['cycles'] as $cycle) {
            foreach ($cycle['rounds'] as $round) {
                if ($round['submission_id'] !== $submission->id) continue;
                if ($cycle['kind'] === 'scope_of_work') {
                    $comparison = $round['detail']['availability'] === 'available'
                        ? app(ReviewRoundComparisonGet::class)->handle($group, $submission) : null;
                    return array_merge($round['detail'], ['comparison' => $comparison, 'group_type' => $comparison['group_type'] ?? null]);
                }
                $after = $this->snapshots->resolve($submission)['snapshot'];
                if ($round['detail']['mode'] === 'submitted_state') {
                    return array_merge($round['detail'], ['initial_application' => true, 'step' => $cycle['step'], 'group_type' => $after ? app(ApplicationReviewPresentation::class)->type($after->snapshot) : null,
                        'submitted_state' => $after ? app(ApplicationReviewPresentation::class)->sections($after->snapshot, $cycle['step']) : null,
                        'comparison' => $after ? app(ApplicationReviewPresentation::class)->comparison($after->snapshot, $after->snapshot, $cycle['step']) : null]);
                }
                $previous = Submission::withTrashed()->where('group_id', $group->id)->findOrFail($round['detail']['previous_submission_id']);
                $before = $this->snapshots->resolve($previous)['snapshot'];
                return array_merge($round['detail'], ['initial_application' => true, 'step' => $cycle['step'], 'group_type' => $after ? app(ApplicationReviewPresentation::class)->type($after->snapshot) : null,
                    'comparison' => app(ApplicationReviewPresentation::class)->comparison($before?->snapshot, $after?->snapshot, $cycle['step'])]);
            }
        }
        abort(404);
    }

    private function person($person): ?array
    {
        return $person ? ['person_id' => $person->id, 'name' => trim($person->first_name.' '.$person->last_name)] : null;
    }

    private function version(?ScopeOfWorkVersion $version): ?array
    {
        return $version ? ['id' => $version->id, 'label' => $version->version_label, 'status' => $version->status,
            'approved_at' => $version->approved_at?->toISOString()] : null;
    }
}
