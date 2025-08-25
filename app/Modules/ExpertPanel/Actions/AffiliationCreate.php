<?php
namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Service\AffilsClient;
use App\Modules\ExpertPanel\Service\CdwgResolver;
use App\Modules\Group\Events\ExpertPanelAffiliationIdUpdated;
use App\Modules\ExpertPanel\Models\AmAffiliationRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AffiliationCreate
{
    public function __construct(
        private AffilsClient $client,
        private CdwgResolver $cdwg
    ) {}

    public function handle(ExpertPanel $ep): JsonResponse
    {
        if ($ep->affiliation_id) {
            return ['status'=>409,'payload'=>['affiliation_id'=>$ep->affiliation_id]];
        }

        $cdwgId = (int) $this->cdwg->resolveAmId($ep) ?? 1; // default 1 = 'None';
        $payload = [
            'uuid'        => (string)$ep->uuid,
            'full_name'   => $ep->long_base_name  ?: $ep->group?->name,
            'short_name'  => $ep->short_base_name ?: $ep->group?->name,
            'clinical_domain_working_group' => $cdwgId,
            'type'        => $this->deriveType($ep),   // GCEP | VCEP | SC_VCEP
            'status'      => $this->deriveStatus($ep), // APPLYING | ACTIVE | INACTIVE | RETIRED | REMOVED
            'members'      => $ep->memberNamesForAffils(),     // "Jane Smith, John Doe"
            'coordinators' => $ep->coordinatorsForAffils(), // [{ coordinator_name, coordinator_email }, ...]
        ];

        $audit = AmAffiliationRequest::create([
                    'request_uuid'    => (string) $ep->uuid,   // unique per attempt
                    'expert_panel_id' => $ep->id,
                    'payload'         => $payload,
                    'status'          => 'pending',
                ]);
                

        try {
            $res = $this->client->create($payload);
            // $res['expert_panel_id'] = 40165;
            $body = json_decode($res->body(), true);

            $affID = (int) ($res['expert_panel_id'] ?? 0);
            Log::info('AffiliationCreate: received response', ['response' => $res, 'affilId' => $affID]);
            if ($affID <= 0) {                 
                $details = $body['details'] ?? [];
                $msg = collect($details)->flatten()->implode(' ');
                $audit->markFailed(0, $msg, $body);
                throw new \RuntimeException($msg, $res->status());
            }

            $ep->forceFill(['affiliation_id' => $affID])->save();
            $audit->markSuccess(201, $body);
            event(new ExpertPanelAffiliationIdUpdated($ep->group, $affID));
            return response()->json(['affiliation_id' => $affID, "message" => "Affiliation created successfully."], 201);

        } catch (\Throwable $e) {           
            $code = (int) ($e->getCode() ?: 0);
            $audit->markFailed($code, $e->getMessage());
            return response()->json(["message" => $e->getMessage()], $code);
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
}
