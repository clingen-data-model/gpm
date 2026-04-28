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
        $matches = $this->findMatches($currentGroup, $normalized);
        $items = $this->mapMatches($matches);

        return ['vcep_conflict' => [
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
    protected function findMatches(Group $currentGroup, array $candidate): Collection
    {
        $matches = collect();
        if (filled($candidate['gt_curation_uuid'])) {
            $this->baseQuery($currentGroup)
                ->whereNotNull('gt_curation_uuid')
                ->where('gt_curation_uuid', $candidate['gt_curation_uuid'])
                ->get()
                ->each(fn (ScopeGene $gene) => $matches->push([
                    'gene' => $gene,
                    'match_basis' => 'Matched by GT Curation UUID',
                ]));
        }

        if (!filled($candidate['mondo_id'])) {
            return $matches;
        }

        if (filled($candidate['hgnc_id'])) {
            $this->baseQuery($currentGroup)
                ->where('mondo_id', $candidate['mondo_id'])
                ->where('hgnc_id', $candidate['hgnc_id'])
                ->get()
                ->each(fn (ScopeGene $gene) => $matches->push([
                    'gene' => $gene,
                    'match_basis' => 'Matched by HGNC ID + Mondo ID',
                ]));
        } else if (filled($candidate['gene_symbol'])) {
            $this->baseQuery($currentGroup)
                ->where('mondo_id', $candidate['mondo_id'])
                ->where('gene_symbol', $candidate['gene_symbol'])
                ->get()
                ->each(fn (ScopeGene $gene) => $matches->push([
                    'gene' => $gene,
                    'match_basis' => 'Matched by Gene Symbol + Mondo ID',
                ]));
        }

        return $matches;
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
    protected function mapMatches(Collection $matches): array
    {
        return $matches
            ->map(function (array $match) {
                $group = $match['gene']->expertPanel?->group;
                if (!$group) { return null; }

                return [
                    'group_id' => $group->id,
                    'group_uuid' => $group->uuid,
                    'group_name' => $group->name,
                    'group_status_id' => $group->group_status_id,
                    'application_status' => $group->status?->name,
                    'match_basis' => $match['match_basis'],
                ];
            })
            ->filter()
            ->groupBy('group_id')
            ->map(function (Collection $groupMatches) {
                $first = $groupMatches->first();

                $first['match_basis'] = $groupMatches
                    ->pluck('match_basis')
                    ->unique()
                    ->implode('; ');

                unset($first['group_id']);

                return $first;
            })
            ->values()
            ->all();
    }
}