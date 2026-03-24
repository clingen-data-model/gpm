<?php

namespace App\Modules\Group\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\ExpertPanel\Service\VcepGeneConflictService;
use App\Modules\Group\Models\Group;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class GeneListController extends Controller
{
    public function index(Request $request, Group $group, VcepGeneConflictService $vcepConflictService)
    {
        if (!$group->isEp) {
            throw new ModelNotFoundException('Only expert panels have gene lists.');
        }

        // We want to differentiate list of Genes/GDMs between "applying" and other than applying.
        $isApplying = (int) $group->group_status_id === (int) config('groups.statuses.applying.id');

        $query  = $group->expertPanel->genes()
                    ->select('id', 'gt_curation_uuid', 'hgnc_id', 'gene_symbol', 'mondo_id', 'disease_name', 'moi', 'tier', 'plan', 'date_approved');

        // When the VCEP status is other than applying, we want to include the latest GT snapshot for each gene from the snapshot.
        if ($group->is_vcep && !$isApplying) {
            $query->with('latestSnapshot');
        }
        $genes = $query->get();

        if (!$group->is_vcep) {
            return $genes;
        }

        // VCEP Area, Status: Applying
        if ($isApplying) {
            return $genes->map(function ($gene) use ($group, $vcepConflictService) {
                return array_merge(
                    $gene->toArray(),
                    $vcepConflictService->check($group, $gene)
                );
            })->values();
        }

        // VCEP Area, Status: !Applying
        return $genes->map(function ($gene) {
            $base = $gene->toArray();

            $snapshotPayload = $gene->latestSnapshot?->payload ?? [];

            if (!is_array($snapshotPayload)) {
                $snapshotPayload = [];
            }

            $vcepConflict = $snapshotPayload['vcep_conflict'] ?? [
                'has_other_vcep_match' => false,
                'has_approved_other_vcep_match' => false,
                'other_vcep_matches' => [],
            ];

            $planFromSnapshot = $snapshotPayload;
            unset($planFromSnapshot['vcep_conflict']);

            return array_merge($base, [
                'plan' => !empty($planFromSnapshot) ? $planFromSnapshot : ($base['plan'] ?? []),
                'vcep_conflict' => $vcepConflict,
            ]);
        })->values();
    }

    public function checkOtherVcepMatch(Request $request, Group $group, VcepGeneConflictService $conflictService)
    {
        if (!$group->isEp) {
            throw new ModelNotFoundException('Only expert panels have gene lists.');
        }

        if (!$group->is_vcep) {
            return [
                'vcep_conflict' => [
                    'has_other_vcep_match' => false,
                    'has_approved_other_vcep_match' => false,
                    'other_vcep_matches' => [],
                ],
            ];
        }

        $data = $request->validate([
            'gt_curation_uuid' => ['nullable', 'uuid'],
            'hgnc_id' => ['nullable', 'integer'],
            'gene_symbol' => ['nullable', 'string', 'max:255'],
            'mondo_id' => ['nullable', 'string', 'max:255'],
        ]);

        return $conflictService->check($group, $data);
    }

}