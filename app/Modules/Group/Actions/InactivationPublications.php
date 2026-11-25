<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;

class InactivationPublications
{
    public function snapshot(Group $group): array
    {
        return $group->publications()->orderBy('id')->get()->map(fn ($publication) => [
            'publication_id' => $publication->uuid,
            'pmid' => $publication->source === 'pmid' ? $publication->identifier : data_get($publication->meta, 'pmid.id'),
            'pmcid' => $publication->source === 'pmcid' ? $publication->identifier : data_get($publication->meta, 'pmcid.id'),
            'title' => $publication->meta['title'] ?? '',
            'pmc_compliant' => 'unknown', // PMCID is an identifier, not a compliance determination.
        ])->all();
    }

    public function signature(array $publications): array
    {
        $rows = array_map(fn ($p) => [
            'publication_id' => $p['publication_id'] ?? null, 'pmid' => $p['pmid'] ?? null,
            'pmcid' => $p['pmcid'] ?? null, 'title' => $p['title'] ?? '',
        ], array_filter($publications, 'is_array'));
        usort($rows, fn ($a, $b) => strcmp((string) $a['publication_id'], (string) $b['publication_id']));
        return $rows;
    }

    public function merge(Group $group, array $answers, array $previous = []): array
    {
        $current = $this->snapshot($group);
        $incoming = is_array($answers['publications'] ?? null) ? $answers['publications'] : [];
        $saved = is_array($previous['publications'] ?? null) ? $previous['publications'] : [];
        $archive = $previous['legacy_publications'] ?? [];
        foreach ($current as &$row) {
            // UUID first, then PMID for older snapshots without a UUID.
            foreach ([$incoming, $saved, $archive] as $source) {
                $match = collect($source)->first(fn ($p) => is_array($p) && (
                    (!empty($p['publication_id']) && $p['publication_id'] === $row['publication_id'])
                    || (empty($p['publication_id']) && !empty($p['pmid']) && (string) $p['pmid'] === (string) $row['pmid'])
                ));
                if ($match) {
                    $row['pmc_compliant'] = $match['pmc_compliant'] ?? 'unknown';
                    break;
                }
            }
        }
        unset($row);
        // Keep old request-only/deleted publication entries for recovery, not as current group publications.
        $ids = array_column($current, 'publication_id');
        foreach ($saved as $old) {
            if (is_array($old) && !in_array($old['publication_id'] ?? null, $ids, true) && !in_array($old, $archive, true)) $archive[] = $old;
        }
        $changed = $this->signature($incoming) !== $this->signature($current);
        $answers['publications'] = $current;
        $answers['publications_acknowledged'] = $changed ? false : ($answers['publications_acknowledged'] ?? false);
        $answers['legacy_publications'] = $archive;
        return $answers;
    }
}
