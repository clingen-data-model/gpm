<?php

namespace App\Modules\Group\Actions;

use App\Service\ModelSnapshotter;
use Illuminate\Support\Facades\DB;
use App\Models\ApplicationSnapshot;
use App\Modules\Group\Models\Group;
use Lorisleiva\Actions\Concerns\AsListener;
use App\Modules\Group\Events\ApplicationStepSubmitted;
use App\Modules\Group\Models\Submission;

class ApplicationSnapshotCreate
{
    use AsListener;

    public function __construct(private ModelSnapshotter $snapshotter)
    {
    }

    public function handle(Group $group, int $submissionId = null): ApplicationSnapshot
    {
        $latestVersion = $this->getLatestVersion($group);

        $group->load([
            'expertPanel.genes',
            'expertPanel.curationReviewProtocol',
            'type',
            'parent',
            'status',
            'members',
            'members.person' => function ($q) {
                $q->select([
                    'id',
                    'first_name', 'last_name', 'email',
                    'legacy_credentials', 'institution_id',
                ]);
            },
            'members.person.institution',
            'members.person.credentials',
            'members.person.expertises',
            'members.roles',
            'comments',
            'comments.creator',
        ]);

        $snapshot =  $this->snapshotter->createSnapshot($group);
        $submission = $submissionId ? Submission::where('group_id', $group->id)->findOrFail($submissionId) : null;
        $step = $submission ? app(\App\Modules\Group\Services\ApplicationReviewPresentation::class)->step($submission) : (int) $group->expertPanel->current_step;
        $snapshot['review_context'] = ['schema_version' => 1, 'step' => $step,
            'submission_id' => $submissionId, 'group_type' => $group->type->name];
        if ($step === 4 && !$group->isGcep) {
            $members = $group->members->filter(fn ($member) => $member->end_date === null);
            $names = fn ($items) => $items->map(fn ($member) => ['name' => $member->person->name])->values()->all();
            $snapshot['review_state'] = [
                'evidence_summaries' => $group->expertPanel->evidenceSummaries()->with('gene')->orderBy('id')->get()->map(function ($summary) use ($group) {
                    $data = ['id' => $summary->id, 'gene' => $summary->gene?->gene_symbol, 'disease' => $summary->gene?->disease_name,
                        'mondo_id' => $summary->gene?->mondo_id, 'moi' => $summary->gene?->moi,
                        'variant' => $summary->variant, 'summary' => $summary->summary,
                        ($group->type->name === 'scvcep' ? 'CIViC URL' : 'VCI URL') => $summary->vci_url];
                    if ($group->type->name === 'scvcep') $data['assertion_id'] = $summary->assertion_id;
                    return $data;
                })->all(),
                'core_approval_members' => $members->filter(fn ($m) => $m->roles->contains('name', 'core-approval-member'))->map(fn ($m) => [
                    'name' => $m->person->name, 'attestation_completed' => $m->person->core_member_attestation_completed,
                    'attestation_completion_date' => $m->person->core_member_attestation_completion_date,
                    'attestation_responses' => $m->person->core_member_attestation_responses,
                ])->values()->all(),
                'biocurator_trainers' => $names($members->filter(fn ($m) => $m->roles->contains('name', 'biocurator-trainer'))),
                'trained_biocurators' => $names($members->filter(fn ($m) => $m->roles->contains('name', 'biocurator') && $m->training_level_1 && $m->training_level_2)),
            ];
        }

        $applicationSnapshot = ApplicationSnapshot::create([
            'group_id' => $group->id,
            'submission_id' => $submissionId,
            'version' => $latestVersion + 1,
            'snapshot' => $snapshot,
        ]);

        if ($submissionId) {
            $submission = Submission::find($submissionId);

            if ($submission) {
                $submissionData = $submission->data ?? [];                
                $submission->update([
                    'data' => array_merge($submissionData, [
                        'context' => $submissionData['context'] ?? 'application_submission',
                        'application_snapshot_id' => $applicationSnapshot->id,
                        'application_snapshot_version' => $applicationSnapshot->version,
                        'step' => $step,
                    ]),
                ]);
            }
        }

        return $applicationSnapshot;
    }

    public function asListener(ApplicationStepSubmitted $event)
    {
        $this->handle($event->group, $event->submission->id);
    }

    private function getLatestVersion(Group $group): int
    {
        $latestVersion = DB::table('application_snapshots')
                            ->selectRaw('MAX(version) as version')
                            ->where('group_id', $group->id)
                            ->sole();

        return $latestVersion->version ?? 0;
    }

}
