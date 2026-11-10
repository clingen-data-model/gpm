<?php

namespace App\Auth\CILogon;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The identity providers CILogon brokers, as {id: EntityID, name: DisplayName}.
 */
class CILogonIdpDirectory
{
    const CACHE_KEY = 'cilogon.idplist';

    public function common(): array
    {
        return config('services.cilogon.common_idps', []);
    }

    public function all(): array
    {
        if ($cached = Cache::get(self::CACHE_KEY)) {
            return $cached;
        }

        try {
            $response = Http::timeout(10)->acceptJson()->get(config('services.cilogon.idp_list_url'));
        } catch (\Exception $e) {
            Log::warning('CILogon idplist fetch failed', ['error' => $e->getMessage()]);

            return [];
        }
        if (! $response->successful() || ! is_array($response->json())) {
            Log::warning('CILogon idplist fetch failed', ['status' => $response->status()]);

            return [];
        }

        $idps = collect($response->json())
            ->filter(fn ($e) => ! empty($e['EntityID']))
            ->map(fn ($e) => ['id' => $e['EntityID'], 'name' => $e['DisplayName'] ?? $e['OrganizationName'] ?? $e['EntityID']])
            ->values()
            ->all();

        // Only successful fetches are cached, so an outage doesn't stick for a day.
        Cache::put(self::CACHE_KEY, $idps, now()->addDay());

        return $idps;
    }

    public function search(string $query, int $limit = 25): array
    {
        $q = Str::lower(trim($query));
        if ($q === '') {
            return [];
        }

        return collect($this->all())
            ->filter(fn ($idp) => str_contains(Str::lower($idp['name']), $q))
            ->sortBy(fn ($idp) => [str_starts_with(Str::lower($idp['name']), $q) ? 0 : 1, $idp['name']])
            ->take($limit)
            ->values()
            ->all();
    }

    public function isKnown(string $id): bool
    {
        return collect($this->common())->contains('id', $id)
            || collect($this->all())->contains('id', $id);
    }
}
