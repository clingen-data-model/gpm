<?php

namespace App\Services;

use Illuminate\Support\Collection;

class GcepGeneCurations
{
    public function enrich($gene, Collection $entries, string $emptyStatus = 'Not Curated'): array
    {
        return array_merge($gene->attributesToArray(), [
            'details' => $entries->values()->all(),
            'statuses' => $entries->isEmpty() ? [$emptyStatus] : $entries->map(fn ($row) => ($row['curation_status'] ?? null) ?: 'Unknown')->unique()->values()->all(),
            'expert_panels' => $entries->pluck('expert_panel')->filter()->unique()->values()->all(),
        ]);
    }

    public function saved(Collection $genes): Collection
    {
        $genes->loadMissing('snapshots');
        return $genes->map(function ($gene) {
            $records = $gene->snapshots->sortBy('id')->filter(fn ($snapshot) => is_array($snapshot->payload) && count($snapshot->payload) > 0);
            return array_merge($this->enrich($gene, $records->pluck('payload'), 'No curation snapshot available'), [
                'curation_source' => 'snapshot',
                'snapshot_captured_at' => $records->pluck('captured_at')->filter()->map(fn ($date) => $date->toISOString())->unique()->values()->all(),
            ]);
        });
    }
}
