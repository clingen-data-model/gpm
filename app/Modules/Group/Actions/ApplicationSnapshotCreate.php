<?php

namespace App\Modules\Group\Actions;

use App\Service\ModelSnapshotter;
use Illuminate\Support\Facades\DB;
use App\Models\ApplicationSnapshot;
use App\Modules\Group\Models\Group;
use Lorisleiva\Actions\Concerns\AsListener;
use App\Modules\Group\Events\ApplicationStepSubmitted;

class ApplicationSnapshotCreate
{
    use AsListener;

    public function __construct(private ModelSnapshotter $snapshotter)
    {
    }

    public function handle(Group $group, int $submissionId = null)
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

        ApplicationSnapshot::create([
            'group_id' => $group->id,
            'submission_id' => $submissionId,
            'version' => $latestVersion+1,
            'snapshot' => $snapshot
        ]);
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
