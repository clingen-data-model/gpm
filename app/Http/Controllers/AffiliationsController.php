<?php

namespace App\Http\Controllers;

use App\Http\Requests\AffilsCreateFromExpertPanelRequest;
use App\Http\Requests\AffilsUpdateRequest;
use App\DataTransferObjects\AffilsCreateDto;
use App\DataTransferObjects\AffilsUpdateDto;
use App\Services\AffilsClient;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\AffilsUpdateByExpertPanelIdRequest;

class AffiliationsController extends Controller
{
    public function __construct(private AffilsClient $client) {}

    /** Search/paginate AM list for the modal (local pagination over cached data) */
    public function search(Request $request)
    {
        $q    = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $per  = min(100, max(10, (int) $request->query('per_page', 25)));

        $raw  = $this->client->listRaw();
        $rows = $this->flattenAffils($raw);

        if ($q !== '') {
            $qLower = mb_strtolower($q);
            $rows = array_values(array_filter($rows, function ($r) use ($qLower) {
                return str_contains(mb_strtolower($r['full_name']), $qLower)
                    || str_contains(mb_strtolower($r['short_name'] ?? ''), $qLower)
                    || str_contains((string) $r['affiliation_id'], $qLower);
            }));
        }

        $total = count($rows);
        $items = array_slice($rows, ($page - 1) * $per, $per);

        return response()->json(new LengthAwarePaginator($items, $total, $per, $page));
    }

    /** Detail proxy to AM */
    public function show(int $affiliationId)
    {
        $detail = $this->client->detail("", $affiliationId);
        return response()->json($detail);
    }

    public function createFromExpertPanel(AffilsCreateFromExpertPanelRequest $req, ExpertPanel $expertPanel)
    {
        if ($expertPanel->affiliation_id) {
            return response()->json([
                'message' => 'Already linked',
                'affiliation_id' => $expertPanel->affiliation_id, // AM.expert_panel_id
            ], 409);
        }

        // Enforce provided uuid matches our EP.uuid
        if ((string)$req->input('uuid') !== (string)$expertPanel->uuid) {
            return response()->json([
                'message' => 'UUID mismatch: provided uuid does not match Expert Panel uuid.',
            ], 422);
        }

        // CDWG: take provided or infer from parent CDWG group
        $cdwgID = $req->input('clinical_domain_working_group');
        if (!$cdwgID) {
            $candidate = Group::query()->find($expertPanel->group_id);

            if ($candidate && (int) $candidate->group_type_id !== 2 && $candidate->parent_id) {
                $candidate = Group::query()->find($candidate->parent_id);
            }

            if (!$candidate || (int) $candidate->group_type_id !== 2 || empty($candidate->affiliation_id)) {
                return response()->json([
                    'message' => 'clinical_domain_working_group is required and could not be inferred from the Expert Panel group hierarchy.',
                ], 422);
            }

            $cdwgID = (int) $candidate->affiliation_id; // AM.affiliation_id for the CDWG
        } else {
            $cdwgID = 1; // 'None' on AM
        }

        $dto = new AffilsCreateDto(
            uuid:  (string)$req->input('uuid'),
            full_name: (string)$req->input('full_name'),
            clinical_domain_working_group: (int)$cdwgID,
            type:   (string)$req->input('type'),
            status: (string)$req->input('status'),
            members: $req->input('members'),
            coordinators: $req->input('coordinators'),
            approvers: $req->input('approvers'),
            clinvar_submitter_ids: $req->input('clinvar_submitter_ids'),
            short_name: $req->input('short_name'),
        );

        // Audit
        $auditId = DB::table('am_affiliation_requests')->insertGetId([
            'request_uuid'    => $expertPanel->uuid,
            'expert_panel_id' => $expertPanel->id,
            'payload'         => json_encode($dto->toArray()),
            'status'          => 'pending',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        try {
            $resp = $this->client->create($dto);
        } catch (\Throwable $e) {
            DB::table('am_affiliation_requests')->where('id', $auditId)->update([
                'status'      => 'failed',
                'http_status' => 0,
                'response'    => null,
                'error'       => $e->getMessage(),
                'updated_at'  => now(),
            ]);
            return response()->json([
                'message'  => 'AM create failed',
                'am_error' => $e->getMessage(),
            ], 502);
        }

        $amExpertPanelId = (int) ($resp['expert_panel_id'] ?? 0);
        if (!$amExpertPanelId) {
            DB::table('am_affiliation_requests')->where('id', $auditId)->update([
                'status'      => 'failed',
                'http_status' => 200,
                'response'    => json_encode($resp),
                'error'       => 'Missing expert_panel_id in AM response',
                'updated_at'  => now(),
            ]);
            return response()->json(['message' => 'Invalid AM response'], 502);
        }

        $expertPanel->forceFill(['affiliation_id' => $amExpertPanelId])->save();

        DB::table('am_affiliation_requests')->where('id', $auditId)->update([
            'status'      => 'success',
            'http_status' => 201,
            'response'    => json_encode($resp),
            'updated_at'  => now(),
        ]);

        return response()->json([
            'affiliation_id' => $amExpertPanelId, // AM.expert_panel_id
            'am_response'    => $resp,
        ], 201);
    }

    public function updateByExpertPanelId(AffilsUpdateByExpertPanelIdRequest $req, int $expertPanelId)
    {
        $payload = $req->validated();

        // Call AM using the ID in the URL, payload contains only fields to change (e.g., status)
        $resp = $this->client->updateByExpertPanelId($expertPanelId, $payload);
        return response()->json($resp);
    }

    // ---- helpers ----

    /** Flatten AM's "old JSON format" to simple rows */
    private function flattenAffils(mixed $raw): array
    {
        $rows = [];

        $visit = function ($node) use (&$visit, &$rows) {
            if (is_array($node)) {
                foreach ($node as $v) {
                    if (is_array($v)
                        && array_key_exists('affiliation_id', $v)
                        && (array_key_exists('affiliation_fullname', $v) || array_key_exists('full_name', $v))) {
                        $rows[] = [
                            'affiliation_id' => $v['affiliation_id'],
                            'full_name'      => $v['affiliation_fullname'] ?? $v['full_name'],
                            'short_name'     => $v['short_name'] ?? null,
                            'type'           => $v['type'] ?? null,
                            'status'         => $v['status'] ?? null,
                        ];
                    } else {
                        $visit($v);
                    }
                }
            } elseif (is_object($node)) {
                $visit((array) $node);
            }
        };

        $visit($raw);

        // de-dup by id
        $uniq = [];
        foreach ($rows as $r) $uniq[$r['affiliation_id']] = $r;
        return array_values($uniq);
    }
}
