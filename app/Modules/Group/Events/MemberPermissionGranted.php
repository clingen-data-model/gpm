<?php

namespace App\Modules\Group\Events;

use Illuminate\Support\Collection;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class MemberPermissionGranted extends GroupMemberEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public GroupMember $groupMember, public Collection $permissions)
    {
        parent::__construct($groupMember);
    }

    public function getLogEntry(): string
    {
        return $this->groupMember->person->name
            . ' granted permissions '
            . $this->permissions->pluck('name')
                ->join(',', ', and ');
    }

    public function getProperties(): array
    {
        return [
            'group_member_id' => $this->groupMember->id,
            'person' => $this->groupMember->person->only('id', 'name', 'email'),
            'permissions' => $this->permissions->map(fn($p) => $p->only('id', 'name'))
        ];
    }

    public function getSubject(): Model
    {
        return $this->groupMember->group;
    }

}
