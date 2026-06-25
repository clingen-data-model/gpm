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
            'expertPanel',
            'type',
            'status',
            'members',
            'members.person' => function ($q) {
                $q->select([
                    'id',
                    'first_name', 'last_name', 'email',
                    'legacy_credentials',
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
