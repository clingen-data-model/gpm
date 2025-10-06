# `checkpoint:groups {groups?*}` + HTTP/UI trigger

Latest Update: 2025-10-06 (yyyy-mm-dd)

Emit a **snapshot (“checkpoint”)** of one or more `Group`s to the Data Exchange so downstream systems can resync from the current source of truth.

A checkpoint dispatches a **`GroupCheckpointEvent`** per group. The DX publisher (listener) then:
- persists a row to **`stream_messages`**,
- **publishes to Kafka** on **`gpm-checkpoint-events`**, and
- **logs** a checkpoint entry for audit/traceability.

---

## Locations in code

- **CLI command:** `App\Console\Commands\CheckpointGroups`
- **HTTP action (API endpoint):** `App\Modules\Group\Actions\EmitGroupCheckpoints` (uses `AsController`)
- **Route (module):** `app/Modules/Group/routes/api.php` → `POST /api/groups/checkpoints`
- **Policy:** `App\Modules\Group\Policies\GroupPolicy::checkpoint`
- **Job:** `App\Jobs\EmitGroupCheckpointJob`
- **Checkpoint event:** `App\Modules\Group\Events\GroupCheckpointEvent`
- **Base event:** `App\Modules\Group\Events\GroupEvent`
- **Payload mapper:** `App\Modules\Group\Http\Resources\GroupExternalResource`
- **DX interface:** `App\Events\PublishableEvent`
- **Module providers:**  
  - `App\Modules\Group\Providers\GroupModuleServiceProvider`  
  - `App\Modules\Foundation\ModuleServiceProvider`
- **DX configuration:** `config/dx.php`

---

## When to use

- Bulk re-emit the **authoritative** state of groups after resource/mapper changes.
- Backfill messages for a new downstream consumer.
- Recover from missed outbound events (replay “current truth”).

---

## CLI usage (unchanged, now delegates to the Action)

```bash
# Re-emit ALL groups
php artisan checkpoint:groups

# Re-emit specific groups by DB primary key(s)
php artisan checkpoint:groups 12 47 98

# Run inline (no queue); useful for local debugging
php artisan checkpoint:groups 22 --sync
```

**Notes**
- The CLI path does **not** enforce per-group policy checks; restrict command access to trusted admins.
- The command internally calls the same core logic as the HTTP endpoint.

---

## HTTP API

### Route
```
POST /api/groups/checkpoints
```
(Defined in `app/Modules/Group/routes/api.php` under the `groups` prefix.)

### Auth & Policy
- Route is behind `auth:sanctum`.
- **Allowed**:
  - **Super Admin** / **Super User**: may emit for any group and for “ALL groups” (no `group_ids` provided).
  - **Coordinator** (and optionally Chair, need to enable it in the policy): may emit **only for their group(s)**.
- The endpoint authorizes **per-group**; unauthorized IDs are returned in `denied_ids`.

### Request body
```json
{
  "group_ids": [12, 47, 98],   // optional: array or CSV string; omitted/empty => ALL groups (super roles only)
  "queue": true,               // optional; default true. false = run inline in the HTTP request
  "dry_run": false             // optional; if true, returns counts but does not emit
}
```

### Response
```json
{
  "status": "queued",          // or "emitted" when queue=false
  "accepted": 3,               // number of groups accepted for emission
  "ids": [12, 47, 98],         // the accepted group IDs
  "denied_ids": [ ],           // you lacked permission for these
  "not_found_ids": [ ]         // requested but not found
}
```

**Behavior**
- If `group_ids` omitted/empty → treat as **ALL groups** (requires super role).
- If `dry_run: true` → returns `would_emit`, `denied_ids`, `not_found_ids` and does not emit.
- **No batching**: when `queue: true`, we dispatch one job per group; with `QUEUE_CONNECTION=sync` they still run immediately. When `queue: false`, events fire inline.

---

## UI triggers (new)

### Bulk button (toolbar)
- Appears on the **Groups list** page, on the same row as “Filter”.
- Labeled **Sync Groups to Website (N)”** where `N` is the count of **visible** groups in the current tab that are **Active**.
- Sends `group_ids` of the **currently visible + Active** rows to the API:
  - “Active” means `group_status_id === 2` or `status.id === 2` or `status.name === 'active'` (case-insensitive).
- Calls Vuex action: `store.dispatch('groups/checkpoints', { group_ids, queue: true })`.

### Per-row button
- A rightmost **“Sync”** button appears for each row **only when the group is Active**.
- Clicking it posts a single ID: `group_ids: [<that id>]`.
- Both bulk and per-row route through the same `emitCheckpoints(ids)` function in `GroupList.vue`.

### Vuex & component notes
- **Vuex action** (`resources/js/store/groups.js`):
  ```js
  async emitCheckpoints(_, { group_ids = [], queue = true, dry_run = false }) {
    const { data } = await api.post('/api/groups/checkpoints', { group_ids, queue, dry_run })
    return data;
  }
  ```
- **Component** `emitCheckpoints(ids)` handles both single and many:
  - Shows per-row spinner when called with `{ rowId }`.
  - Displays a toast based on `{ accepted, denied_ids, not_found_ids }`.

---

## What the Action/Command do now (no batching)

```php
// App\Modules\Group\Actions\EmitGroupCheckpoints::handle(Collection $groups, bool $queue = true)
if ($groups->isEmpty()) {
    return ['accepted' => 0, 'ids' => []];
}

if ($queue) {
    foreach ($groups as $g) {
        EmitGroupCheckpointJob::dispatch($g->id);   // one job per group
    }
} else {
    foreach ($groups as $g) {
        event(new GroupCheckpointEvent($g));        // inline
    }
}

return ['accepted' => $groups->count(), 'ids' => $groups->pluck('id')->all()];
```
- The **Job** loads the group and fires `GroupCheckpointEvent($group)`.
- The **HTTP** path filters requested IDs by policy into `allowed_ids`; rejected IDs are returned in `denied_ids`.
- The **CLI** path delegates to the same `handle()` but does not perform per-group authorization (admin-only).

---

## Topics & schema versions (from `config/dx.php`)

**Outgoing topics:**
- `gpm-general-events`  (env: `DX_OUTGOING_GPM_GENERAL_EVENTS`, default `gpm-general-events`)
- **`gpm-checkpoint-events`**  (env: `DX_OUTGOING_GPM_CHECKPOINT_EVENTS`, default `gpm-checkpoint-events`)
- `gpm-person-events`   (env: `DX_OUTGOING_GPM_PERSON_EVENTS`, default `gpm-person-events`)

**Schema versions:**
- `gpm-general-events` → `1.9.9`
- `gpm-person-events`  → `1.9.9`
- **`gpm-checkpoint-events` → `2.0.0`**

> `GroupCheckpointEvent` returns schema `"2.0.0"` and overrides the topic to `gpm-checkpoint-events`.  
> `shouldPublish()` is **always** `true` for checkpoint events.

---

## Event details: `GroupCheckpointEvent`

Key overrides vs the base `GroupEvent`:

| Concern | Base `GroupEvent` | **`GroupCheckpointEvent`** |
|---|---|---|
| **Topic** | `gpm-general-events` | **`gpm-checkpoint-events`** |
| **Schema** | `1.9.9` | **`2.0.0`** |
| **Publish gate** | EP publish gated by approval | **Always publish** |
| **Payload** | Minimal `groupRepresentation` | **Full** via `GroupExternalResource` (`gpm_group`) |
| **Log entry** | derived | `"Checkpoint event for group: {name}"` |

Methods of interest (abridged):
```php
public function getSchemaVersion(): string { return '2.0.0'; }
public function getTopic(): string { return config('dx.topics.outgoing.gpm-checkpoint-events'); }
public function shouldPublish(): bool { return true; }
public function getPublishableMessage(): array {
    return (new GroupExternalResource($this->group))->toArray(null);
}
```
---

## Payload contract (`GroupExternalResource`) — abridged

```json
{
  "gpm_group": {
    "uuid": "string",
    "name": "string",
    "description": "string|null",
    "status": "string",
    "status_date": "ISO-8601",
    "type": "string",
    "coi": "https://.../coi/{uuid}",
    "members": [ { ... } ],
    "expert_panel": { ... },
    "parent": { ... }
  }
}
```

Members include `roles` and (only for Coordinator/Chair) `email`. EP blocks are included per type (VCEP/GCEP).

---

## End-to-end flow (HTTP + UI)

```
[UI] GroupList.vue
  → dispatch('groups/checkpoints', { group_ids, queue })
  → POST /api/groups/checkpoints

[Action: EmitGroupCheckpoints]
  → authorize per-group via GroupPolicy@checkpoint
  → allowed ids → handle(groups, queue)

[handle() no batching]
  → queue:true  → EmitGroupCheckpointJob::dispatch(id) per group
  → queue:false → event(new GroupCheckpointEvent($group)) inline

[Job]
  → loads Group
  → event(GroupCheckpointEvent)

[DX Publisher Listener]
  → shouldPublish() === true
  → topic = gpm-checkpoint-events
  → schema = 2.0.0
  → payload = GroupExternalResource(...)
  → persist to stream_messages
  → Kafka produce
  → log "Checkpoint event for group: ..."
```

---

## Data sinks & side effects

- **`stream_messages`**: one row per publish attempt.
- **Kafka**: one message per group to **`gpm-checkpoint-events`**.
- **Logs**: “Checkpoint event for group: {name}”.

---

## Operational notes

- **No batching**: we don’t use `Bus::batch`; no `job_batches` table required.
- **Queue driver**:
  - `QUEUE_CONNECTION=sync` → jobs run immediately in the same process (no `jobs` table required).
  - `database`/`redis` → start a worker; for `database` you must run `queue:table` + migrate (and optionally `failed_jobs`).
- **PII**: Member **emails** only included for **Coordinator/Chair**.
- **Idempotent**: Safe to emit repeatedly; downstream treats the latest snapshot as authoritative.
- **CSRF/Sanctum**: the UI posts with Sanctum; ensure XSRF cookie/header are set in the `api` service.

---

## Troubleshooting

1. **UI says success but no `stream_messages`**
   - Inspect API JSON: if `accepted === 0`, check `denied_ids` (policy) and `not_found_ids`.
   - Ensure the route is authenticated and your user has the required role(s).
   - Try `{ "queue": false }` to force inline and verify the publisher path.

2. **CLI works but UI doesn’t**
   - Policy likely denying the web user; the CLI bypasses per-group authorization.
   - Also confirm the web and CLI use the same `.env` / DB / DX flags (`php artisan optimize:clear`).

3. **Nothing publishes even with `queue:false`**
   - Listener registration: `GroupCheckpointEvent` must be discovered by the module provider and bound to the DX publisher.
   - Check logs for the “Checkpoint event for group: …” line.

---

