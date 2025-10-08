# GPM-517 — Retire a Person (and from all Groups)

**Date:** 2025-10-08  

## Summary

Add an Admin action on **Person → Admin tab** labeled **“Retire user (and retire from all groups)”**.  
When executed, the system:

1. Iterates all **active memberships** for that person and retires each one (sets `end_date`).
2. Optionally **unlinks** the Person from their User record via the existing `PersonUnlinkUser`.
3. Adds **reason** why  `PersonUnlinkUser`.
4. Returns a concise summary JSON so the UI can show an accurate alert.

No schema change to `people` (we **do not** add `retired_at`, `retired_by_id`, or `retired_reason`).

---

## UI (Vue 3, Options API)

Button sits under **Delete Person** (Admin tab). The confirm modal calls the new endpoint and displays how many groups were affected.

### Success alert text

Backend returns:
```json
{"person_id": 3321, "memberships_retired": 2, "unlinked_user": false}
```

Show: **“Retired from 2 groups.”** (+ “User account unlinked.” if true)

### Example handler

> **Important:** send **`unlink_user`** (boolean) and `reason` (optional). Your earlier code sent `disable_login`; switch it to `unlink_user` to match the API.

```js
async commitRetireAll() {
  try {
    this.retireAllBusy = true
    const data = await this.saveEntry(`/api/people/${this.person.uuid}/retire`, {
      unlink_user: this.unlinkUserOnRetire ?? false, 
      reason: this.retireReason || null
    })

    this.showRetireAllConfirmation = false

    const n = Number(data?.memberships_retired ?? 0)
    const parts = [`Retired from ${n} ${n === 1 ? 'group' : 'groups'}.`]
    if (data?.unlinked_user) parts.push('User account unlinked.')

    await this.$store.dispatch('people/getPerson', { uuid: this.uuid })
    this.getLogEntries()
    this.$store.commit('pushSuccess', parts.join(' '))
  } catch (e) {
    this.$store.commit('pushError', 'Failed to retire user — see console/logs.')
  } finally {
    this.retireAllBusy = false
  }
}
```

---

## API

**Route**
```php
// app\Modules\Person\routes\api.php
Route::middleware(['auth:sanctum','can:people-manage'])
    ->post('/people/{person:uuid}/retire', \App\Modules\Person\Actions\PersonRetireAll::class);
```

**Request (JSON)**
```json
{"unlink_user": true, "reason": "Left ClinGen"}
```

**Response (JSON)**
```json
{"person_id": 3321, "memberships_retired": 2, "unlinked_user": false}
```

**Authorization**: `people-manage` (mirrors `PersonDelete`)

---

## Backend Implementation

### 1) Person Action (final)

`app/Modules/Person/Actions/PersonRetireAll.php`

```php
<?php

namespace App\Modules\Person\Actions;

use Carbon\Carbon;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsObject;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Actions\MemberRetire;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PersonRetireAll
{
    use AsController, AsObject;

    public function __construct(
        private MemberRetire $memberRetire,
        private ?PersonUnlinkUser $unlinkUser = null
    ) {}

    public function handle(Person $person, bool $unlinkUser = false, ?string $reason = null): array
    {
        $actor = Auth::user();
        $endAt = Carbon::now();
        $retired = 0;

        foreach ($person->memberships()->isActive()->get() as $gm) {
            try {
                $this->memberRetire->handle($gm, $endAt, $reason, $actor);
                $retired++;
            } catch (\Throwable $e) {
                Log::warning('PersonRetireAll: membership retire failed', [
                    'person_id' => $person->id,
                    'group_member_id' => $gm->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $didUnlink = false;
        if ($unlinkUser && $person->user) {
            if ($this->unlinkUser) {
                $this->unlinkUser->handle($person);
            } else {
                app(PersonUnlinkUser::class)->handle($person);
            }
            $didUnlink = true;
        }

        return [
            'person_id' => $person->id,
            'memberships_retired' => $retired,
            'unlinked_user' => $didUnlink,
        ];
    }

    public function asController(ActionRequest $request, Person $person)
    {
        $data = $request->validated();
        $unlinkUser = $request->boolean('unlink_user', true);
        $reason = $data['reason'] ?? null;

        return response()->json(
            $this->handle($person, $unlinkUser, $reason)
        );
    }

    public function rules(): array
    {
        return [
            'unlink_user' => ['boolean'],
            'reason'      => ['nullable','string','max:5000'],
        ];
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('people-manage');
    }
}
```

### 2) Reused MemberRetire (from Group Action)  with reason appended to notes

```php
public function handle(GroupMember $groupMember, Carbon $endDate, ?string $reason = null, $actor = null): GroupMember
{    
    $retireMember = ['end_date' => $endDate];

    if ($reason !== null) {
        $existing = trim((string) $groupMember->notes);
        $stamp = now()->toDateTimeString();
        $by = $actor?->name ? " by {$actor->name}" : '';
        $line = "[{$stamp}] Retired{$by}: {$reason}";
        $retireMember['notes'] = $existing ? ($existing . ". " . $line) : $line;
    }

    $groupMember->forceFill($retireMember)->save();

    Event::dispatch(new MemberRetired($groupMember));
    return $groupMember;
}
```

---

## Acceptance Criteria

- Admin tab shows a button **“Retire user (and retire from all groups)”** beneath **Delete Person**.
- Clicking the button opens a confirmation modal with:
  - Optional textarea “Reason”.
  - Checkbox “Also unlink user account”.
- On confirm, UI calls `POST /api/people/{uuid}/retire` and shows a success alert:  
  “**Retired from N group(s).**” (+ “User account unlinked.” when true).
- All active memberships for that person have `end_date` set.
- If “unlink” was chosen, the `PersonUnlinkUser` action runs.
- Endpoint requires `people-manage` permission.
- Action logs warnings for any membership failures but continues the loop (partial progress).

---

## Sample Response (real)

```json
{"person_id": 3321, "memberships_retired": 2, "unlinked_user": false}
```
