<?php

namespace App\Services;

use App\Modules\Person\Models\Person;
use App\Modules\Person\Models\CocAttestation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CocService
{
    public function currentVersion(): string
    {
        return (string) config('coc.current_version');
    }

    public function warningDays(): int
    {
        return (int) config('coc.warning_days', 30);
    }

    public function renewalDays(): int
    {
        return (int) config('coc.renewal_days', 365);
    }

    public function renderContent(?string $version = null): array
    {
        $version ??= $this->currentVersion();
        $path = config("coc.definitions.$version");

        if (!$path) {
            return [
                'version' => $version,
                'content' => '',
                'links'   => config('coc.links', []),
            ];
        }

        $content = file_get_contents(resource_path($path)) ?: '';

        // Optional: substitute links into markdown
        $links = config('coc.links', []);
        $content = str_replace(
            ['{{full_link}}', '{{summary_link}}'],
            [$links['full'] ?? '', $links['summary'] ?? ''],
            $content
        );

        return [
            'version' => $version,
            'content' => $content,
            'links'   => $links,
        ];
    }

    public function statusFor(Person $person): array
    {
        /** @var CocAttestation|null $latest */
        $latest = $person->latestCocAttestation;

        $currentVersion = $this->currentVersion();
        $now = now();

        if (!$latest || !$latest->completed_at) {
            return $this->statusPayload('missing', $currentVersion, null, null);
        }

        if ($latest->version !== $currentVersion) {
            return $this->statusPayload('version_mismatch', $currentVersion, $latest->completed_at, $latest->expires_at);
        }

        $expiresAt = $latest->expires_at ?? Carbon::parse($latest->completed_at)->addDays($this->renewalDays());
        $daysRemaining = $now->diffInDays($expiresAt, false);

        if ($daysRemaining < 0) {
            return $this->statusPayload('expired', $currentVersion, $latest->completed_at, $expiresAt, $daysRemaining);
        }

        if ($daysRemaining <= $this->warningDays()) {
            return $this->statusPayload('expiring_soon', $currentVersion, $latest->completed_at, $expiresAt, $daysRemaining);
        }

        return $this->statusPayload('current', $currentVersion, $latest->completed_at, $expiresAt, $daysRemaining);
    }

    public function attest(Person $person): CocAttestation
    {
        $now = now();
        $expiresAt = $now->copy()->addDays($this->renewalDays());

        return $person->cocAttestations()->create([
            'uuid'         => (string) Str::uuid(),
            'version'      => $this->currentVersion(),
            'completed_at' => $now,
            'expires_at'   => $expiresAt,
            'data'         => json_encode([
                'links' => config('coc.links', []),
            ]),
        ]);
    }

    private function statusPayload(
        string $status,
        string $currentVersion,
        ?Carbon $completedAt,
        ?Carbon $expiresAt,
        ?int $daysRemaining = null
    ): array {
        return [
            'status'          => $status, // missing|version_mismatch|expired|expiring_soon|current
            'current_version' => $currentVersion,
            'completed_at'    => optional($completedAt)->toIso8601String(),
            'expires_at'      => optional($expiresAt)->toIso8601String(),
            'days_remaining'  => $daysRemaining,
        ];
    }
}
