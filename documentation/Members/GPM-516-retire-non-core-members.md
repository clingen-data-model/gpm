# GPM-516 — Retire Non-Core Members when Group Becomes Inactive/Retired

**Last Updated:** 2025-10-06  

---

## Summary

When a **Group** moves to **Inactive** or **Retired**, most active **GroupMember** records should be retired automatically. Only the following roles remain active by default:

- **Coordinator**
- **Chair**
- **Grant Liaison**

A coordinator can subsequently re-add any other designee from the list of retired members if needed.

This document covers the design, configuration, code changes, and rollout steps.

---

## Status & Decision

- **Implementation style:** Event-driven via `GroupStatusUpdated` → listener **RetireMembersOnGroupInactivation** → action **RetireNonCoreMembers**.
- **Execution mode:** **Synchronous** for immediate UI consistency.

> If you prefer a fully inline approach without any event coupling, see **Alternative: Inline in `GroupStatusUpdate`** below.

---

## Data Model Assumptions

- `group_members` has **`end_date`** (nullable). When set ⇒ membership is retired.
- `group_members` has a **`note`** column we can use for audit context (optional).
- Roles are attached to `GroupMember` via Spatie’s `model_has_roles` with `model_type = App\Modules\Group\Models\GroupMember`.
- Canonical role name is stored as `roles.name`.

---

## Configuration

**File:** `app/Modules/Group/groups.php`

```php
return [
    // this one is already there
    'statuses' => [
        'applying' => ['id' => 1, 'name' => 'applying'],
        'active'   => ['id' => 2, 'name' => 'active'],
        'retired'  => ['id' => 3, 'name' => 'retired'],
        'removed'  => ['id' => 4, 'name' => 'removed'],
        'inactive' => ['id' => 5, 'name' => 'inactive'],
    ],

    // Added this one. Members with ANY of these roles remain active when a group is inactivated/retired. Refers to `roles`.`name`, not id.
    'keep_roles_on_inactivation' => [
        'coordinator',
        'chair',
        'grant-liaison',
    ],
];
```

> **Important:** Strings in `keep_roles_on_inactivation` must **exactly** match `roles.name` values, e.g., `"grant-liaison"` vs `"grant liaison"`. Adjust accordingly before deploy.

---

## Event Flow

```
GroupStatusUpdate::handle($group, $newStatus)
  └─ updates group_status_id
  └─ event(new GroupStatusUpdated($group, $newStatus, $oldStatus))

GroupStatusUpdated (Event)
  └─ RetireMembersOnGroupInactivation (Listener)  [sync]
      └─ RetireNonCoreMembers (Action)
          └─ Bulk retire all active GroupMember rows for this group
             that DO NOT hold any keep-role (set end_date, add note*)
```

\* `note` is only set if currently empty/null; existing notes remain unchanged.

---

## Implementation

### 1) Action: `RetireNonCoreMembers`

**File:** `app/Modules/Group/Actions/RetireNonCoreMembers.php`

```php
<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\GroupMember;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RetireNonCoreMembers
{
    /**
     * Retire all active memberships in the group that do NOT hold any keep-role.
     *
     * @return int number of memberships retired
     */
    public function handle(Group $group, ?Carbon $asOf = null): int
    {
        $asOf = $asOf ?: now();

        // Pull exact role names from config.
        $keep = collect(config('groups.keep_roles_on_inactivation', []))
            ->filter()
            ->values();

        // Resolve keep role IDs by name.
        $roleIds = Role::query()
            ->whereIn('name', $keep)
            ->pluck('id');

        // Active memberships for this group that lack any keep-role
        $q = GroupMember::query()
            ->where('group_id', $group->id)
            ->whereNull('end_date')
            ->whereDoesntHave('roles', fn ($r) => $r->whereIn('id', $roleIds));

        // Add a note only if empty/null to avoid clobbering existing notes
        $message = sprintf(
            'Retired via group status change (%s) on %s',
            optional($group->status)->name ?? 'inactive/retired',
            $asOf->toDateTimeString()
        );

        return $q->update([
            'end_date' => $asOf,
            'note' => DB::raw(
                "CASE WHEN note IS NULL OR note = '' THEN " . DB::getPdo()->quote($message) . " ELSE note END"
            ),
        ]);
    }
}
```

---

### 2) Listener: `RetireMembersOnGroupInactivation` (sync)

**File:** `app/Modules/Group/Listeners/RetireMembersOnGroupInactivation.php`

```php
<?php

namespace App\Modules\Group\Listeners;

use App\Modules\Group\Events\GroupStatusUpdated;
use App\Modules\Group\Actions\RetireNonCoreMembers;
use Illuminate\Contracts\Queue\ShouldQueue;

class RetireMembersOnGroupInactivation implements ShouldQueue
{
    public function __construct(private RetireNonCoreMembers $action) {}

    public function handle(GroupStatusUpdated $event): void
    {
        $retiredId  = data_get(config('groups.statuses.retired'),  'id', 3);
        $inactiveId = data_get(config('groups.statuses.inactive'), 'id', 5);

        if (in_array($event->newStatus->id, [$retiredId, $inactiveId], true)) {
            $this->action->handle($event->group);
        }
    }
}
```

> To switch to async later, change `$connection` to your queue connection (e.g., `'database'`/`'redis'`) and ensure workers are running.

---

### 3) Register the Listener in the **Group module** provider

**File:** `app/Modules/Group/GroupServiceProvider.php` (extends your `ModuleServiceProvider`)

```php
<?php

namespace App\Modules\Group;

use App\Modules\Group\Events\GroupStatusUpdated;
use App\Modules\Group\Listeners\RetireMembersOnGroupInactivation;
use App\Modules\Support\Providers\ModuleServiceProvider; // Your abstract base

class GroupServiceProvider extends ModuleServiceProvider
{
    protected $listeners = [
        GroupStatusUpdated::class => [
            RetireMembersOnGroupInactivation::class,
        ],
    ];

    protected function getModulePath()
    {
        return app_path('Modules/Group');
    }
}
```

Your `ModuleServiceProvider` plus the `RegistersExplicitEventListeners` trait takes care of wiring `Event::listen(...)` behind the scenes.

---

### 4) **Front-end Impact (GPM-516)**

- **Why:** Member retirements happen server-side on status change; the **Members** tab won’t reflect this until the UI refetches.
- **When to refetch:** Only when `group_status_id` becomes **inactive** or **retired**.

**`GroupDetail.vue`** — After the “Edit Group Info” modal saves, conditionally reload members:

```diff
<script>
+import configs from '@/configs'
// ...existing imports...
</script>
```

```diff
data() {
  return {
+   inactivationStatusIds: [
+     configs.groups.statuses.retired.id,
+     configs.groups.statuses.inactive.id
+   ],
    showInfoEdit: false,
    // ...
  }
},
methods: {
+ async onGroupInfoSaved () {
+   this.showInfoEdit = false
+   const newId = Number(this.group.group_status_id)
+   if (this.inactivationStatusIds.includes(newId)) {
+     // Tailor to your store signature:
+     await this.$store.dispatch('groups/getMembers', this.group)
+     // or: await this.$store.dispatch('groups/getMembers', { uuid: this.group.uuid, active: 1 })
+   }
+ },
  // ...
}
```

Wire it to the form:

```diff
<GroupForm
  ref="infoForm"
  @canceled="showInfoEdit = false"
- @saved="showInfoEdit = false"
+ @saved="onGroupInfoSaved"
/>
```

**No backend resource shape changes** were required for the UI.

#### Listener execution mode
- We are running `RetireMembersOnGroupInactivation` **synchronously** (`public $connection = 'sync'`) so retirements complete before the response.  
- If later switched to async, keep the UI refetch and add a short retry/backoff on the members call.

#### Test notes
- Change status to **inactive**/**retired** → verify only coordinator/chair/grant-liaison remain visible without page reload.
- Change status to **active** → no members refetch is triggered.

---

## Alternative: Inline in `GroupStatusUpdate` (no listener)

If you need strict atomicity without events, run the action directly when transitioning to `inactive`/`retired`:

```php
public function handle(Group $group, GroupStatus $groupStatus): Group
{
    if ($group->group_status_id == $groupStatus->id) {
        return $group;
    }

    $oldStatus = $group->status;
    $group->update(['group_status_id' => $groupStatus->id]);

    $targets = [
        data_get(config('groups.statuses.retired'), 'id', 3),
        data_get(config('groups.statuses.inactive'), 'id', 5),
    ];

    if (in_array($groupStatus->id, $targets, true)) {
        app(\App\Modules\Group\Actions\RetireNonCoreMembers::class)->handle($group);
    }

    event(new GroupStatusUpdated($group, $groupStatus, $oldStatus));
    return $group;
}
```

You can keep the same UI refetch behavior above.

---

## Backfill (optional)

For existing groups already set to `inactive`/`retired`, use a one-shot command:

**File:** `app/Console/Commands/GpmRetireNonCoreMembers.php`

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Actions\RetireNonCoreMembers;

class GpmRetireNonCoreMembers extends Command
{
    protected $signature = 'gpm:retire-non-core {groupId?}';
    protected $description = 'Retire non-core members for groups that are inactive/retired';

    public function handle(RetireNonCoreMembers $action): int
    {
        $retiredId  = data_get(config('groups.statuses.retired'), 'id', 3);
        $inactiveId = data_get(config('groups.statuses.inactive'), 'id', 5);

        $groups = Group::query()
            ->when($this->argument('groupId'), fn ($q, $id) => $q->where('id', $id))
            ->whereIn('group_status_id', [$retiredId, $inactiveId])
            ->get();

        $total = 0;
        foreach ($groups as $group) {
            $count = $action->handle($group);
            $this->info("Group {$group->id}: retired {$count} members");
            $total += $count;
        }
        $this->info("Done. Total memberships retired: {$total}");
        return self::SUCCESS;
    }
}
```
