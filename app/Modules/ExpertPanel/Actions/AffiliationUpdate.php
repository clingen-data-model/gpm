<?php
namespace App\Modules\ExpertPanel\Actions;

use Illuminate\Http\JsonResponse;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Service\AffiliationMicroserviceClient;
use App\Modules\ExpertPanel\Models\AffiliationMicroserviceRequest;
use Illuminate\Support\Str;
use App\Modules\ExpertPanel\Enums\AffiliationStatus;
use Illuminate\Support\Facades\Log;

class AffiliationUpdate
{
    public function __construct(private AffiliationMicroserviceClient $client) {}

    public function handle(ExpertPanel $ep, array $overrides = []): JsonResponse
    {
        $affId = (int) $ep->affiliation_id;
        if ($affId <= 0) {
            return response()->json([
                'message' => 'Cannot update Affiliation Microservice: expert panel has no affiliation_id yet.',
            ], 409);
        }
        $base = [
            'uuid'        => (string) $ep->uuid,
            'full_name'   => $ep->long_base_name  ?: $ep->group?->name,
            'short_name'  => $ep->short_base_name ?: $ep->group?->name,            
            'clinical_domain_working_group' => (int) ($ep->group->parent->parent_id ?? 1),
            'status'      => $this->deriveStatus($ep),
            'members'      => $ep->memberNamesForAffils() ?? '',
            'coordinators' => $ep->coordinatorsForAffils() ?? [],
        ];

        $payload = array_replace($base, $overrides);
        
        $audit = AffiliationMicroserviceRequest::create([
            'request_uuid'    => (string) Str::uuid(),
            'expert_panel_id' => $ep->id,
            'payload'         => $payload,
            'status'          => 'pending',
        ]);

        try {
            $res   = $this->client->updateByEpID((string) $affId, $payload);
            $data  = $this->normalizeClientResponse($res);
            $code  = $this->extractStatusCode($res, 200);

            // 200 -299 => success; treat 4xx/5xx => errors.
            if ($code < 200 || $code >= 300) {
                $msg = $this->extractMessage($data) ?: 'Affiliation update failed.';
                $audit->update([
                    'http_status' => $code,
                    'response'    => $data,
                    'status'      => 'failed',
                    'error'       => $msg,
                ]);

                return response()->json([
                    'message' => $msg,
                    'details' => $data ?: null,
                ], $code);
            }

            $audit->markSuccess($code, $data);
            return response()->json([
                'affiliation_id' => $affId,
                'message'        => 'Affiliation updated.',
            ], 200);

        } catch (\Throwable $e) {
            $code = $this->normalizeStatus((int) $e->getCode());
            $audit->markFailed($code, "failed", $e->getMessage());

            return response()->json([
                'message' => $e->getMessage() ?: 'Affiliation update failed.',
            ], $code);
        }
    }

    /** Convenience: set status ACTIVE and push latest names/members. */
    public function activate(ExpertPanel $ep): JsonResponse
    {
        return $this->handle($ep, ['status' => AffiliationStatus::ACTIVE->value]);
    }

    private function deriveStatus(ExpertPanel $ep): string
    {
        return AffiliationStatus::fromGroupStatusName($ep->group?->status?->name)->value;
    }

    private function normalizeClientResponse(mixed $res): array
    {
        if (is_array($res)) return $res;

        if (is_object($res)) {
            if (method_exists($res, 'json')) {
                return (array) $res->json();
            }
            if (method_exists($res, 'body')) {
                $j = json_decode($res->body(), true);
                return is_array($j) ? $j : [];
            }
        }
        return [];
    }

    private function extractStatusCode(mixed $res, int $fallback = 200): int
    {
        if (is_object($res) && method_exists($res, 'status')) { return (int) $res->status(); }
        return $fallback;
    }

    private function extractMessage(array $am): ?string
    {
        if (!empty($am['message']) && is_string($am['message'])) { return $am['message']; }
        if (!empty($am['details']) && is_array($am['details'])) { 
            $flat = collect($am['details'])->flatten()->implode(' ');
            if ($flat !== '') return $flat;
        }
        if (!empty($am['error']) && is_string($am['error'])) { return $am['error']; }
        return null;
    }

    private function normalizeStatus(int $code): int
    {
        return ($code >= 400 && $code <= 599) ? $code : 500;
    }
}
