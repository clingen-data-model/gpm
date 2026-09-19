<?php

namespace App\Http\Resources;

use App\Modules\Group\Models\GroupMember;
use Illuminate\Http\Resources\Json\JsonResource;

class ClingenPersonResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'institution' => $this->institution?->name,
            'credentials' => $this->credentials->pluck('name')->values(),
            'memberships' => $this->memberships->map(fn (GroupMember $membership) => [
                'status' => $membership->end_date === null ? 'active' : 'retired',
                'start_date' => $membership->start_date?->toDateString(),
                'end_date' => $membership->end_date?->toDateString(),
                'group' => [
                    'uuid' => $membership->group->uuid,
                    'name' => $membership->group->name,
                    'type' => $membership->group->type?->name,
                    'affiliation_id' => $membership->group->expertPanel?->affiliation_id,
                ],
                'roles' => $membership->roles->pluck('name')->values(),
            ])->values(),
        ];
    }
}
