<?php

namespace App\Modules\Group\Actions;

use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsObject;

class ScopeOfWorkRevisionRefresh
{
    use AsObject;

    public function handle(Group $group, ?User $user = null, ?array $currentSnapshot = null): ?ScopeOfWorkVersion
    {
        $group->loadMissing('expertPanel');

        if (!$group->expertPanel) {
            return null;
        }

        $submittedRevision = ScopeOfWorkVersion::forGroup($group)
            ->where('status', ScopeOfWorkVersion::STATUS_SUBMITTED)
            ->with(['changes', 'latestSnapshot', 'baseVersion'])
            ->latest()
            ->first();

        if ($submittedRevision) {
            return $submittedRevision;
        }

        $approvedVersion = ScopeOfWorkVersion::forGroup($group)
            ->approved()
            ->with('latestSnapshot')
            ->orderByDesc('major_version')
            ->orderByDesc('minor_version')
            ->first();

        if (!$approvedVersion || !$approvedVersion->latestSnapshot) {
            $approvedVersion = ScopeOfWorkInitialVersionCreate::run($group, $user);
        }

        $beforeSnapshot = $approvedVersion->latestSnapshot->snapshot;
        $currentSnapshot = $currentSnapshot ?: ScopeOfWorkSnapshotBuild::run($group);

        $changes = ScopeOfWorkSnapshotCompare::run($group, $beforeSnapshot, $currentSnapshot);

        return DB::transaction(function () use ($group, $user, $approvedVersion, $currentSnapshot, $changes) {
            $draftVersion = $this->findDraftVersion($group);

            if (count($changes) === 0) {
                if ($draftVersion) {
                    $draftVersion->changes()->delete();
                    $draftVersion->snapshots()->delete();

                    $draftVersion->update([
                        'status' => ScopeOfWorkVersion::STATUS_DISCARDED,
                    ]);
                }

                return null;
            }

            [$majorVersion, $minorVersion] = $this->targetVersion($approvedVersion, $changes);

            if (!$draftVersion) {
                $draftVersion = ScopeOfWorkVersion::create([
                    'group_id' => $group->id,
                    'expert_panel_id' => $group->expertPanel->id,
                    'major_version' => $majorVersion,
                    'minor_version' => $minorVersion,
                    'status' => ScopeOfWorkVersion::STATUS_DRAFT,
                    'base_version_id' => $approvedVersion->id,
                    'created_by' => $user?->id,
                ]);
            } else {
                $draftVersion->update([
                    'major_version' => $majorVersion,
                    'minor_version' => $minorVersion,
                    'base_version_id' => $approvedVersion->id,
                ]);

                $draftVersion->changes()->delete();
                $draftVersion->snapshots()->delete();
            }

            foreach ($changes as $change) {
                $draftVersion->changes()->create($this->prepareChangeForStorage($change));
            }

            ScopeOfWorkSnapshotCreate::run($draftVersion, $group, $currentSnapshot);

            return $draftVersion->load(['changes', 'latestSnapshot']);
        });
    }

    private function findDraftVersion(Group $group): ?ScopeOfWorkVersion
    {
        return ScopeOfWorkVersion::forGroup($group)
            ->whereIn('status', [
                ScopeOfWorkVersion::STATUS_DRAFT,
                ScopeOfWorkVersion::STATUS_REVISIONS_REQUESTED,
            ])
            ->latest()
            ->first();
    }

    private function targetVersion(ScopeOfWorkVersion $approvedVersion, array $changes): array
    {
        $hasMajorChange = collect($changes)
            ->contains(fn ($change) => ($change['impact'] ?? null) === 'major');

        if ($hasMajorChange) {
            return [
                $approvedVersion->major_version + 1,
                0,
            ];
        }

        return [
            $approvedVersion->major_version,
            $approvedVersion->minor_version + 1,
        ];
    }

    private function prepareChangeForStorage(array $change): array
    {
        return [
            'rule_key' => $change['rule_key'] ?? null,
            'area' => $change['area'],
            'change_type' => $change['change_type'],
            'label' => $change['label'] ?? null,

            'entity_type' => $change['entity_type'] ?? null,
            'entity_uuid' => $change['entity_uuid'] ?? null,
            'entity_label' => $change['entity_label'] ?? null,

            'field_name' => $change['field_name'] ?? null,

            'before_value' => $this->jsonValue($change['before_value'] ?? null),
            'after_value' => $this->jsonValue($change['after_value'] ?? null),

            'impact' => $change['impact'] ?? null,
            'requires_approval' => $change['requires_approval'] ?? null,
            'approval_step' => $change['approval_step'] ?? null,
            'approvers' => $change['approvers'] ?? null,
            'condition' => $change['condition'] ?? null,
        ];
    }

    private function jsonValue(mixed $value): mixed
    {
        if (is_null($value) || is_array($value)) {
            return $value;
        }

        return [
            'value' => $value,
        ];
    }
}