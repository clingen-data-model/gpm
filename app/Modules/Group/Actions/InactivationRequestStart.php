<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\{Group, ExpertPanelInactivationRequest};
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;

class InactivationRequestStart
{
    public function handle(Group $group, User $user): ExpertPanelInactivationRequest
    {
        return DB::transaction(function () use ($group, $user) {
            Group::whereKey($group->id)->lockForUpdate()->firstOrFail();
            $existing = ExpertPanelInactivationRequest::where('group_id', $group->id)->first();
            if ($existing) return $existing;
            return ExpertPanelInactivationRequest::create([
                'group_id' => $group->id,
                'schema_version' => 3,
                'created_by_id' => $user->id,
                'answers' => [
                    'group_name' => $group->display_name,
                    'affiliation_id' => (string) $group->affiliation_id,
                    'parent_name' => $group->parent?->display_name,
                    'request_date' => now()->toDateString(),
                    'liaisons' => app(InactivationContacts::class)->defaults($group),
                    'transfer_planned' => $group->is_gcep ? 'yes' : null,
                    'receiving_group_id' => null, 'receiving_name' => '',
                    'receiving_affiliation_id' => '', 'receiving_parent_name' => '',
                    'receiving_liaisons' => [], 'transfer_discussion_date' => null,
                    'transfer_attestation' => false, 'no_transfer_attestation' => false,
                    'public_text' => '', 'materials_access' => '',
                    'publications' => app(InactivationPublications::class)->snapshot($group),
                    'publications_acknowledged' => false,
                    'continuing_members' => [],
                ],
            ])->fresh(); // Hydrate database defaults before the resource calculates can_edit.
        });
    }
}
