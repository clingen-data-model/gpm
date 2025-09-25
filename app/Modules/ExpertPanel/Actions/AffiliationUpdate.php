<?php
namespace App\Modules\ExpertPanel\Actions;

use Illuminate\Http\JsonResponse;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Service\AffilsClient;
use App\Modules\ExpertPanel\Models\AmAffiliationRequest;
use Illuminate\Support\Str;

class AffiliationUpdate
{
    public function __construct(private AffilsClient $client) {}

    public function handle(ExpertPanel $ep, array $overrides = []): JsonResponse
    {
        $affId = (int)$ep->affiliation_id;
        if ($affId <= 0) {
            return response()->json([
                'message' => 'Cannot update AM: expert panel has no affiliation_id yet.'
            ], 409);
        }

        $base = [
            'uuid'        => (string)$ep->uuid,
            'full_name'   => $ep->long_base_name  ?: $ep->group?->name,
            'short_name'  => $ep->short_base_name ?: $ep->group?->name,
            'clinical_domain_working_group' => (int)($ep->group->parent->parent_id ?? 1),
            'status'      => $this->deriveStatus($ep),
            'members'      => $ep->memberNamesForAffils(),
            'coordinators' => $ep->coordinatorsForAffils(),
        ];

        $payload = array_replace($base, $overrides);

        $audit = AmAffiliationRequest::create([
            'request_uuid'    => (string) Str::uuid(),
            'expert_panel_id' => $ep->id,
            'payload'         => $payload,
            'status'          => 'pending',
        ]);
        
        $res = $this->client->updateByEpID((string)$affId, $payload);        

        if (method_exists($res, 'failed') && $res->failed()) {
            $body = method_exists($res, 'json') ? $res->json() : null;           
            $audit->markFailed($res->status(), $body['message'], $body);
            return response()->json([
                'message' => $body['message'] ?? 'Affiliation update failed.',
                'details' => $body,
            ], (int)($res->status() ?? 422));
        }

        $audit->markSuccess((int)($res->status() ?: 200), $res->json() ?? []);
        return response()->json([
            'affiliation_id' => $affId,
            'message'        => 'Affiliation updated.',
        ], 200);
    }

    /** Convenience: set status ACTIVE and push latest names/members. */
    public function activate(ExpertPanel $ep): JsonResponse
    {
        return $this->handle($ep, ['status' => 'ACTIVE']);
    }

    private function deriveStatus(ExpertPanel $ep): string
    {
        $n = strtoupper($ep->group?->status?->name ?? '');
        return in_array($n, ['APPLYING','ACTIVE','INACTIVE','RETIRED','REMOVED'], true) ? $n : 'INACTIVE';
    }
}
