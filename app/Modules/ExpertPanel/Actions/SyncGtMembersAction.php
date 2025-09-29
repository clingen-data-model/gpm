<?php 

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Services\Api\GtApiService;
use Illuminate\Support\Facades\Log;

class SyncGtMembersAction
{
    public function __construct(private GtApiService $gt) {}

    public function handle(ExpertPanel $ep, string $mode = 'add'): array
    {
        $ep->loadMissing('members.person', 'members.roles');

        $coordinators = collect($ep->coordinatorsForAffils())
            ->map(fn ($c) => [
                'email'          => strtolower(trim((string)($c['coordinator_email'] ?? ''))),
                'name'           => trim((string)($c['coordinator_name'] ?? '')),
                'is_coordinator' => true,
                'is_curator'     => false,
            ]);

        // biocurators by role name
        $biocurators = $ep->members
            ->filter(fn ($m) => $m->roles?->pluck('name')->contains('biocurator'))
            ->map(fn ($m) => [
                'email'          => strtolower(trim((string)($m->person->email ?? ''))),
                'name'           => trim(implode(' ', array_filter([$m->person->first_name ?? null, $m->person->last_name ?? null]))),
                'is_coordinator' => false,
                'is_curator'     => true,
            ]);

        $members = $coordinators
            ->concat($biocurators)
            ->filter(fn ($m) => !empty($m['email']))   // require email
            ->reduce(function ($carry, $m) {           // merge flags if same email
                $email = $m['email'];
                if (!isset($carry[$email])) {
                    $carry[$email] = $m;
                } else {
                    $carry[$email]['is_coordinator'] = $carry[$email]['is_coordinator'] || $m['is_coordinator'];
                    $carry[$email]['is_curator']     = $carry[$email]['is_curator']     || $m['is_curator'];
                }
                return $carry;
            }, []);
        $members = array_values($members);

        if (empty($members)) {
            Log::info('GT member sync skipped (no members)', ['ep' => $ep->uuid]);
            return ['skipped' => true, 'reason' => 'no members'];
        }

        $payload = $this->gt->syncPanelMembers((int) $ep->affiliation_id, $members, $mode);

        Log::info('GT member sync sent', ['ep' => $ep->uuid, 'count' => count($members)]);
        return $payload;
    }
}
