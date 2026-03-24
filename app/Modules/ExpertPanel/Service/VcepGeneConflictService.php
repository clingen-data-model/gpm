<?php

namespace App\Modules\ExpertPanel\Service;

use App\Modules\ExpertPanel\Models\Gene as ScopeGene;
use App\Modules\Group\Models\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class VcepGeneConflictService
{
    /**
     * Check whether a GDM already exists on another VCEP group.
     *
     * Matching rules:
     * 1. If gt_curation_uuid is present, use that first.
     * 2. Otherwise, fall back to:
     *      - hgnc_id + mondo_id -> we want to grandfather the pre-existing data
     *      - if hgnc_id missing, gene_symbol + mondo_id -> highly unlikely but still possible fallback
     *
     * @param  Group  $currentGroup
     * @param  ScopeGene|array  $candidate
     * @return array{
     *   has_other_vcep_match: bool,
     *   has_approved_other_vcep_match: bool,
     *   other_vcep_matches: array<int, array{
     *     group_uuid: ?string,
     *     group_name: ?string,
     *     application_status: ?string,
     *     match_basis: string
     *   }>
     * }
     */

    public function check(Group $currentGroup, ScopeGene|array $candidate): array
    {
        $normalized = $this->normalizeCandidate($candidate);
        [$matches, $matchBasis] = $this->findMatches($currentGroup, $normalized);
        $items = $this->mapMatches($matches, $matchBasis);

        return [ 'vcep_conflict' => [
            'has_other_vcep_match' => !empty($items),
            'has_approved_other_vcep_match' => collect($items)->contains(
                fn (array $item) => (int) ($item['group_status_id'] ?? 0) === (int) config('groups.statuses.active.id')
            ),
            'other_vcep_matches' => array_values($items),
        ]];
    }

    /**
     * @param  ScopeGene|array  $candidate
     */
    protected function normalizeCandidate(ScopeGene|array $candidate): array
    {
        if ($candidate instanceof ScopeGene) {
            return [
                'gt_curation_uuid' => $candidate->gt_curation_uuid,
                'hgnc_id' => $candidate->hgnc_id ? (int) $candidate->hgnc_id : null,
                'gene_symbol' => $candidate->gene_symbol ? trim((string) $candidate->gene_symbol) : null,
                'mondo_id' => $candidate->mondo_id ? trim((string) $candidate->mondo_id) : null,
            ];
        }

        return [
            'gt_curation_uuid'  => filled($candidate['gt_curation_uuid'] ?? null)   ? trim((string) $candidate['gt_curation_uuid']) : null,
            'hgnc_id'           => filled($candidate['hgnc_id'] ?? null)            ? (int) $candidate['hgnc_id'] : null,
            'gene_symbol'       => filled($candidate['gene_symbol'] ?? null)        ? trim((string) $candidate['gene_symbol']) : null,
            'mondo_id'          => filled($candidate['mondo_id'] ?? null)           ? trim((string) $candidate['mondo_id']) : null,
        ];
    }

    /**
     * @return array{0: \Illuminate\Support\Collection<int, ScopeGene>, 1: string}
     */
    protected function findMatches(Group $currentGroup, array $candidate): array
    {
        if (filled($candidate['gt_curation_uuid'])) {
            $matches = $this->baseQuery($currentGroup)
                ->whereNotNull('gt_curation_uuid')
                ->where('gt_curation_uuid', $candidate['gt_curation_uuid'])
                ->get();
            return [$matches, 'Matched by GT Curation UUID'];
        }

        if (!filled($candidate['mondo_id'])) {
            return [collect(), 'fallback'];
        }

        $query = $this->baseQuery($currentGroup)->where('mondo_id', $candidate['mondo_id']);
        if (filled($candidate['hgnc_id'])) {
            $query->where('hgnc_id', $candidate['hgnc_id']);
            return [$query->get(), 'Matched by HGNC ID + Mondo ID'];
        }

        if (filled($candidate['gene_symbol'])) {
            $query->where('gene_symbol', $candidate['gene_symbol']);
            return [$query->get(), 'Matched by Gene Symbol + Mondo ID'];
        }
        return [collect(), 'fallback'];
    }

    protected function baseQuery(Group $currentGroup): Builder
    {
        return ScopeGene::query()
            ->with(['expertPanel.group.status'])
            ->whereHas('expertPanel.group', function (Builder $query) use ($currentGroup) {
                $query->where('id', '!=', $currentGroup->id)
                    ->where('group_type_id', config('groups.types.vcep.id'));
            });
    }

    /**
     * @param  Collection<int, ScopeGene>  $matches
     * @return array<int, array{
     *   group_uuid: ?string,
     *   group_name: ?string,
     *   application_status: ?string,
     *   match_basis: string
     * }>
     */
    protected function mapMatches(Collection $matches, string $matchBasis): array
    {
        return $matches
            ->map(fn (ScopeGene $gene) => $gene->expertPanel?->group)
            ->filter()
            ->unique('id')
            ->map(function (Group $group) use ($matchBasis) {
                return [
                    'group_uuid' => $group->uuid,
                    'group_name' => $group->name,
                    'group_status_id' => $group->group_status_id,
                    'application_status' => $group->status?->name,
                    'match_basis' => $matchBasis,
                ];
            })
            ->values()
            ->all();
    }
}