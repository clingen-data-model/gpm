<?php
namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Service\AffilsClient;
use App\Modules\ExpertPanel\Service\CdwgResolver;
use App\Modules\Group\Events\ExpertPanelAffiliationIdUpdated;
use App\Modules\ExpertPanel\Models\AmAffiliationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AffiliationCreate
{
    public function __construct(
        private AffilsClient $client,
        private CdwgResolver $cdwg
    ) {}

    public function handle(ExpertPanel $ep): JsonResponse
    {
        if ($ep->affiliation_id) {
            return response()->json([
                'affiliation_id' => (int) $ep->affiliation_id,
                'message' => 'Affiliation already assigned.',
            ], 200);
        }

        // Check remote by UUID and sync if present.
        $existing = $this->client->detail((string) $ep->uuid);
        if ($existing) {
            $data = $this->normalizeClientResponse($existing);
            $affId = (int) ($data['expert_panel_id'] ?? 0);

            if ($affId > 0) {
                $ep->forceFill(['affiliation_id' => $affId])->save();
                $audit = AmAffiliationRequest::create([
                    'request_uuid'    => (string) Str::uuid(),
                    'expert_panel_id' => $ep->id,
                    'payload'         => ['synced_existing' => true, 'remote' => $affId],
                    'status'          => 'success',
                ]);
                event(new ExpertPanelAffiliationIdUpdated($ep->group, $affId));

                return response()->json([
                    'affiliation_id' => $affId,
                    'message' => 'Affiliation already exists (synced).',
                ], 200);
            }
        }

        // Build payload
        $cdwgId = (int) ($this->cdwg->resolveAmId($ep) ?? 1); // default 1 = 'None'
        $payload = [
            'uuid'        => (string) $ep->uuid,
            'full_name'   => $ep->long_base_name  ?: $ep->group?->name,
            'short_name'  => $ep->short_base_name ?: $ep->group?->name,
            'clinical_domain_working_group' => $cdwgId,
            'type'        => $this->deriveType($ep),   // GCEP | VCEP | SC_VCEP
            'status'      => $this->deriveStatus($ep), // APPLYING | ACTIVE | INACTIVE | RETIRED | REMOVED
            'members'      => $ep->memberNamesForAffils(),
            'coordinators' => $ep->coordinatorsForAffils(),
        ];
        
        $audit = AmAffiliationRequest::create([
            'request_uuid'    => (string) Str::uuid(),
            'expert_panel_id' => $ep->id,
            'payload'         => $payload,
            'status'          => 'pending',
        ]);

        try {
            $res  = $this->client->create($payload);
            $data = $this->normalizeClientResponse($res);

            $affId = (int) ($data['expert_panel_id'] ?? 0);
            if ($affId <= 0) {
                $msg = $data['message']
                    ?? (is_array($data['details'] ?? null) ? collect($data['details'])->flatten()->implode(' ') : 'Unknown error creating affiliation.');
                $status = $this->normalizeStatusCode((int) ($data['status'] ?? 422));
                $audit->markFailed($status, $msg, $data);
                return response()->json(['message' => $msg], $status);
            }

            $ep->forceFill(['affiliation_id' => $affId])->save();
            $audit->markSuccess(201, $data);
            event(new ExpertPanelAffiliationIdUpdated($ep->group, $affId));
            return response()->json([
                'affiliation_id' => $affId,
                'message' => 'Affiliation created successfully.',
            ], 201);

        } catch (\Throwable $e) {
            $http = $this->normalizeStatusCode((int) $e->getCode());
            $audit->markFailed($http, $e->getMessage());
            return response()->json(['message' => $e->getMessage()], $http);
        }
    }

    private function deriveType(ExpertPanel $ep): string
    {
        $groupType = strtoupper($ep->type?->display_name ?? '');
        if ($groupType === 'SCVCEP') return 'SC_VCEP';
        return ($groupType === 'VCEP') ? 'VCEP' : 'GCEP';
    }

    private function deriveStatus(ExpertPanel $ep): string
    {
        $n = strtoupper($ep->group?->status?->name ?? '');
        return in_array($n, ['APPLYING','ACTIVE','INACTIVE','RETIRED','REMOVED'], true) ? $n : 'INACTIVE';
    }

    private function normalizeClientResponse(mixed $res): array
    {
        // If AffilsClient already returns arrays, this just passes through.
        if (is_array($res)) {
            return $res;
        }
        // If it's an HTTP response object with ->json() or ->body()
        if (is_object($res)) {
            if (method_exists($res, 'json')) {
                return (array) $res->json();
            }
            if (method_exists($res, 'body')) {
                $decoded = json_decode($res->body(), true);
                return is_array($decoded) ? $decoded : [];
            }
        }
        return [];
    }

    private function normalizeStatusCode(int $code): int
    {
        return ($code >= 400 && $code <= 599) ? $code : 500;
    }
}
