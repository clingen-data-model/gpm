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
        if ((int) $submission->submission_type_id !== (int) config('submissions.types.application.definition.id')
            || (isset($data['approval_step']) && (int) $data['approval_step'] === 4)
            || (isset($data['step']) && (int) $data['step'] === 4)) {
            return 'excluded';
        }
        foreach (['approval_step', 'step'] as $field) {
            if (isset($data[$field]) && (string) $data[$field] !== '1') return 'ambiguous';
        }
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
        return in_array($context, [null, 'application_submission'], true) ? 'initial_application' : 'ambiguous';
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
            $key = $kind === 'initial_application' ? $kind : 'scope_of_work:'.$submission->scope_of_work_version_id;
            $cycles[$key] ??= ['kind' => $kind, 'submissions' => []];
            $cycles[$key]['submissions'][] = $submission;
        }
        $result = [];
        $initialVersion = ScopeOfWorkVersion::where('group_id', $group->id)->where('major_version', 1)
            ->where('minor_version', 0)->where('status', 'approved')->first();
        $initialLink = false;
        foreach ($cycles as $key => $cycle) {
            $items = collect($cycle['submissions']);
            $initial = $cycle['kind'] === 'initial_application';
            $version = $initial ? null : ScopeOfWorkVersion::withTrashed()->find($items->first()->scope_of_work_version_id);
            $approved = $items->where('submission_status_id', config('submissions.statuses.approved.id'));
            $linked = $version && $version->status === 'approved'
                ? $approved->firstWhere('id', $version->submission_id) : null;
            $initialLinked = $initial && $initialVersion?->submission_id
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
                    return array_merge($round['detail'], ['comparison' => $round['detail']['availability'] === 'available'
                        ? app(ReviewRoundComparisonGet::class)->handle($group, $submission) : null]);
                }
                $after = $this->snapshots->resolve($submission)['snapshot'];
                if ($round['detail']['mode'] === 'submitted_state') {
                    return array_merge($round['detail'], ['submitted_state' => $after ? $this->projection($after->snapshot) : null]);
                }
                $previous = Submission::withTrashed()->where('group_id', $group->id)->findOrFail($round['detail']['previous_submission_id']);
                $before = $this->snapshots->resolve($previous)['snapshot'];
                return array_merge($round['detail'], ['comparison' => app(ScopeOfWorkDisplayComparison::class)->handle($before?->snapshot, $after?->snapshot)]);
            }
        }
        abort(404);
    }

    private function projection(array $snapshot): array
    {
        // Whitelist captured values only. Never hydrate live models or load missing relations.
        $paths = ['Panel name' => 'attributes.name', 'Website description' => 'attributes.description',
            'Long name' => 'relations.expertPanel.attributes.long_base_name',
            'Short name' => 'relations.expertPanel.attributes.short_base_name',
            'Description of scope' => 'relations.expertPanel.attributes.scope_description',
            'Membership description' => 'relations.expertPanel.attributes.membership_description'];
        $sections = [];
        foreach ($paths as $label => $path) {
            $sections[] = ['label' => $label, 'available' => \Illuminate\Support\Arr::has($snapshot, $path), 'value' => data_get($snapshot, $path)];
        }
        foreach (['Genes' => 'relations.expertPanel.relations.genes', 'Members' => 'relations.members'] as $label => $path) {
            $values = data_get($snapshot, $path);
            $sections[] = ['label' => $label, 'available' => is_array($values), 'value' => is_array($values)
                ? array_map(function ($item) use ($label) {
                    if ($label === 'Genes') return \Illuminate\Support\Arr::only($item['attributes'] ?? [],
                        ['gene_symbol', 'hgnc_id', 'mondo_id', 'disease_name', 'moi', 'tier', 'plan']);
                    $person = \Illuminate\Support\Arr::only(data_get($item, 'relations.person.attributes', []), ['first_name', 'last_name', 'email']);
                    $person['roles'] = isset($item['relations']['roles']) ? array_map(fn ($role) => $role['attributes']['display_name']
                        ?? $role['attributes']['name'] ?? null, $item['relations']['roles']) : 'Unavailable';
                    $person['retirement'] = array_key_exists('end_date', $item['attributes'] ?? [])
                        ? ($item['attributes']['end_date'] === null ? 'Active' : 'Retired') : 'Unavailable';
                    return $person;
                }, $values) : null];
        }
        foreach (['Curation review protocol' => 'curation_review_protocol_id', 'Other protocol details' => 'curation_review_protocol_other',
            'GCEP attestation signed' => 'gcep_attestation_date', 'GCI training date' => 'gci_training_date',
            'NHGRI attestation signed' => 'nhgri_attestation_date', 'Reanalysis attestation signed' => 'reanalysis_attestation_date'] as $label => $field) {
            $path = 'relations.expertPanel.attributes.'.$field;
            $sections[] = ['label' => $label, 'available' => \Illuminate\Support\Arr::has($snapshot, $path), 'value' => data_get($snapshot, $path)];
        }
        return $sections;
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
