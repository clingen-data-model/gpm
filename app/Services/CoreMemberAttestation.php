<?php

namespace App\Services;

use App\Modules\Group\Models\GroupMember;
use App\Modules\Person\Models\Attestation;
use Illuminate\Support\Facades\DB;
use App\Modules\Person\Events\AttestationPendingCreated;
use Illuminate\Support\Facades\Event;

class CoreMemberAttestation
{
    public function handle(GroupMember $member, iterable $roles): void
    {
        $targetID   = config('groups.roles.core-approval-member.id', 105);
        $targetName = config('groups.roles.core-approval-member.name', 'core-approval-member');

        $assignedCore = collect($roles)->contains( fn($role) => (int)($role->id ?? 0) === (int) $targetID || (($role->name ?? null) === $targetName) );

        if (! $assignedCore || ! $member->person) { return; }
        if (! ($member->group?->is_vcep)) { return; }

        DB::transaction(function () use ($member) {
            $person = $member->person;

            $existing = Attestation::query()
                ->where('person_id', $person->id)
                ->whereNull('revoked_at')
                ->whereNull('deleted_at')
                ->first();

            if ($existing) return;

            $attestation = Attestation::create(['person_id' => $person->id]);
            Event::dispatch(new AttestationPendingCreated($person, $attestation));

        });
    }
}
