<?php
namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\AmAffiliationRequest;
use App\Modules\ExpertPanel\Service\AffilsClient;
use App\Modules\Group\Events\ExpertPanelAffiliationIdUpdated;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class AffiliationCreate
{
    public function __construct(
        private AffilsClient $client,
    ) {}

    public function handle(ExpertPanel $ep): JsonResponse
    {
        // 1) Already has an ID locally → no-op
        if ($ep->affiliation_id) {
            return response()->json([
                'affiliation_id' => (int) $ep->affiliation_id,
                'message'        => 'Affiliation already assigned.',
            ], 200);
        }

        // 2) See if AM already has this UUID; if so, sync locally and return 200
        try {
            $maybe = $this->client->detail((string) $ep->uuid);             // may be Response or array
            $am    = $this->normalizeClientResponse($maybe);

            $existingId = (int) ($am['expert_panel_id'] ?? 0);
            if ($existingId > 0) {
                $ep->forceFill(['affiliation_id' => $existingId])->save();

                AmAffiliationRequest::create([
                    'request_uuid'    => (string) Str::uuid(),
                    'expert_panel_id' => $ep->id,
                    'payload'         => ['synced_existing' => true, 'remote' => $existingId],
                    'http_status'     => 200,
                    'response'        => $am,
                    'status'          => 'success',
                ]);

                event(new ExpertPanelAffiliationIdUpdated($ep->group, $existingId));

                return response()->json([
                    'affiliation_id' => $existingId,
                    'message'        => 'Affiliation already exists (synced).',
                ], 200);
            }
        } catch (\Throwable $e) {
            // If AM detail lookup fails, proceed to create below.
            // We intentionally do not fail the flow here.
        }

        $payload = $this->buildCreatePayload($ep);

        $audit = AmAffiliationRequest::create([
            'request_uuid'    => (string) Str::uuid(),
            'expert_panel_id' => $ep->id,
            'payload'         => $payload,
            'status'          => 'pending',
        ]);

        try {
            $res = $this->client->create($payload);
            $am  = $this->normalizeClientResponse($res);
            
            $amId = (int) ($am['expert_panel_id'] ?? 0);
            if ($amId <= 0) {
                $msg    = $this->extractMessage($am) ?: 'Unknown error creating affiliation.';
                $status = $this->normalizeStatus((int) ($am['status'] ?? 422));
                $audit->markFailed($status, $msg, $am);
                return response()->json(['message' => $msg], $status);
            }

            $ep->forceFill(['affiliation_id' => $amId])->save();
            $audit->update(['http_status' => 201, 'response' => $am,]);
            event(new ExpertPanelAffiliationIdUpdated($ep->group, $amId));

            return response()->json([
                'affiliation_id' => $amId,
                'message'        => 'Affiliation created successfully.',
            ], 201);

        } catch (\Throwable $e) {
            $status = $this->normalizeStatus((int) $e->getCode());
            $audit->markFailed($http, $e->getMessage());
            return response()->json(['message' => $e->getMessage()], $status);
        }
    }    

    private function buildCreatePayload(ExpertPanel $ep): array
    {        
        $cdwgId = (int) ($ep->group->parent->parent_id ?? 1); // Default CDWG to parent_id (or 1 = “None”)

        return [
            'uuid'        => (string) $ep->uuid,
            'full_name'   => $ep->long_base_name  ?: $ep->group?->name,
            'short_name'  => $ep->short_base_name ?: $ep->group?->name,
            'clinical_domain_working_group' => $cdwgId,
            'type'        => $this->deriveType($ep),   // GCEP | VCEP | SC_VCEP
            'status'      => $this->deriveStatus($ep), // APPLYING | ACTIVE | INACTIVE | RETIRED | REMOVED
        ];
    }

    private function deriveType(ExpertPanel $ep): string
    {
        $label = strtoupper($ep->type?->display_name ?? '');
        return $label === 'SCVCEP' ? 'SC_VCEP' : ($label === 'VCEP' ? 'VCEP' : 'GCEP');
    }

    private function deriveStatus(ExpertPanel $ep): string
    {
        $n = strtoupper($ep->group?->status?->name ?? '');
        return in_array($n, ['APPLYING','ACTIVE','INACTIVE','RETIRED','REMOVED'], true) ? $n : 'INACTIVE';
    }

    private function normalizeClientResponse(mixed $res): array
    {
        if (is_array($res)) return $res;

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

    private function extractMessage(array $am): ?string
    {
        if (!empty($am['message']) && is_string($am['message'])) {
            return $am['message'];
        }
        if (!empty($am['details']) && is_array($am['details'])) {
            $flat = collect($am['details'])->flatten()->implode(' ');
            if ($flat !== '') return $flat;
        }
        if (!empty($am['error']) && is_string($am['error'])) {
            return $am['error'];
        }
        return null;
    }

    private function normalizeStatus(int $code): int
    {
        return ($code >= 400 && $code <= 599) ? $code : 500;
    }
}
