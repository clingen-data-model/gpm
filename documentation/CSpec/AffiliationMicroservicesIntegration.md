
# Affiliation ID Creation & Status Sync — Implementation Notes

**Owner:** (you)  
**Module(s):** ExpertPanel, Groups, Frontend (Vue)  
**Date:** 2025‑10‑03  
**Ticket:** Create/Sync Affiliation IDs in AM; surface “Create” button in UI; sync status on group status change

---

## 1) Summary

We added end‑to‑end support to:
- **Create an Affiliation ID** for Expert Panels (GCEP/VCEP/SCVCEP) on demand from the UI and automatically on group creation.
- **Create/Update CDWG** records in AM when we create CDWGs in GPM.
- **Sync Expert Panel status** to AM whenever a group’s status changes (best‑effort, non‑blocking).
- Improve **error handling** so users see human‑readable messages (e.g., “This UUID already exists.”).

AM (Affiliations Manager) returns both `affiliation_id` and `expert_panel_id`. We standardize on **AM’s `expert_panel_id`** as the Affiliation ID we persist to `expert_panels.affiliation_id` (integer, 5 digits).

---

## 2) Architecture Overview

```
UI (Vue) ──▶ API (Laravel)
  Button       └─▶ AffiliationController@store (EP UUID)
                   └─▶ Action: AffiliationCreate@handle(ExpertPanel)
                           └─▶ AffilsClient (AM API; fake mode in local/testing)
                           └─▶ AmAffiliationRequest audit log
                   └─▶ returns { affiliation_id, message }

Group Status change ──▶ Action: GroupStatusUpdate@handle
                         └─▶ AffiliationUpdate@handle(ExpertPanel)  // best-effort sync
```

**Key models/services**
- `ExpertPanel` (stores `affiliation_id` as integer; holds names, members, coordinators)
- `AmAffiliationRequest` (audit log of requests to AM: payload, status, response, error)
- `AffilsClient` (HTTP client to AM; supports fake responses for local/testing)
- Actions: `AffiliationCreate`, `AffiliationUpdate`, `GroupCreate`, `GroupStatusUpdate`

---

## 3) Backend Changes

### 3.1 Routes
```php
// app/Modules/ExpertPanel/routes/api.php
Route::group(['prefix' => 'api/applications','middleware'=>['api']], function () {
    // …other application routes…
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/{expertPanel:uuid}/affiliation', [AffiliationController::class, 'show']);
        Route::post('/{expertPanel:uuid}/affiliation', [AffiliationController::class, 'store']);
    });
});
```

### 3.2 AffiliationCreate (server → AM “create”)
- If EP already has an `affiliation_id`, returns **200** with existing id.
- Otherwise tries **AM detail by UUID** first. If found with an `expert_panel_id`, we **sync** it locally and return 200.
- Else builds payload: names, type, status, members (flattened), coordinators (array of name/email), CDWG id.
- Calls `AffilsClient->create($payload)`.
- On success: stores `expert_panel_id` into `expert_panels.affiliation_id`, logs success via `AmAffiliationRequest`, returns **201**.
- On AM error: logs failure in `AmAffiliationRequest` and returns a friendly message and proper HTTP code.

**Normalization rule**
> Treat **AM’s `expert_panel_id`** as the Affiliation ID we persist to `expert_panels.affiliation_id`.

### 3.3 AffiliationUpdate (server → AM “update”)
- Requires local `affiliation_id` to exist (>0).
- Builds payload (names, status, members, coordinators, CDWG).
- Calls `AffilsClient->updateByEpID($affiliation_id, $payload)`.
- Logs via `AmAffiliationRequest` as success/failure, returns JSON with message and (200/4xx).

### 3.4 GroupStatusUpdate (status sync)
- After changing the group status and firing `GroupStatusUpdated`, we best‑effort sync to AM:
  ```php
  $ep = $group->expertPanel ?? null;
  if ($ep && (int)$ep->affiliation_id > 0) {
      try { $this->affiliationUpdate->handle($ep); }
      catch (\Throwable $e) {
          Log::warning('AM sync on status change failed', [/* context */]);
      }
  }
  ```
- Uses `AffiliationUpdate@handle` (no special “activate” path needed—status derives from EP/group).

### 3.5 GroupCreate (CDWG + EP flows)
- **CDWG (group_type_id = 2):** Calls `AffilsClient->createCDWG(['name' => $group->name])`. If success, we may store the returned AM `id` if needed. On failure, throw `ValidationException` with AM’s message.
- **EP (3/4/5):** Creates `ExpertPanel` record, then best‑effort calls `AffiliationCreate->handle($expertPanel)`.
  - Errors are logged but do not block group creation.

### 3.6 AffilsClient (AM API client)
- Reads base URL, API key, paths from `config/services.php`.
- **Fake Mode** for local/testing: configurable via `services.affils.fake` or environment detection.
  - `create`, `detail`, `createCDWG`, `updateCDWG`, `updateByEpID`, `updateByUUID` return consistent fake responses using `Http::response(...)`.
- **Error shaping:** for real calls we surface human‑readable messages (e.g., flatten AM `details` arrays into `"This UUID already exists."`).

### 3.7 AmAffiliationRequest (audit)
- Migrated table: `am_affiliation_requests`
  - `request_uuid`, `expert_panel_id`, `payload` (json), `http_status` (smallint), `response` (json), `status` (`pending|success|failed`), `error` (text).
  - Indexes: `unique(expert_panel_id, request_uuid)`; `index(expert_panel_id, status)`.
- Model helpers: `markSuccess`, `markFailed` for consistent logging.

---

## 4) Frontend Changes (Vue)

### 4.1 “Create Affiliation ID” button
- **Where:** 
  - `components/applications/BasicInfoData.vue` (visible in Application Admin view)
  - `components/groups/GroupForm.vue` (admin edit form)
- Shows the button if:
  - User has appropriate permission (e.g. `groups-manage`), and
  - Group is an EP (`group.group_type_id > 2`), and
  - `group.expert_panel.affiliation_id` is empty.
- On click, calls **store action**: `groups/createAffiliationId({ uuid })` which POSTs to `/api/applications/:epUuid/affiliation`.
- Success → updates the in‑store group model (`expert_panel.affiliation_id`) and shows a green toast.  
  Failure → shows the server-provided message (e.g., “This UUID already exists.”).

### 4.2 Store
- Added action `createAffiliationId` in `resources/js/store/groups.js` to centralize the API call and update state.  
  _Name casing can be camelCase—Vuex doesn’t require SCREAMING_SNAKE_CASE._

---

## 5) Error Handling Strategy

- **Backend**
  - When AM returns errors like:
    ```json
    {"error":"Validation Failed","details":{"uuid":["This UUID already exists."]}}
    ```
  - We flatten `details` to a short message (“This UUID already exists.”) and return it as `message` with an appropriate HTTP status (400/422/502 depending on context).
  - We **always** write an `AmAffiliationRequest` row: `pending` → `success` or `failed` with `http_status`, `response`, and `error` for auditability.
  - API callers (frontend) can rely on `message` being the primary string to show.

- **Frontend**
  - In catch blocks, prefer `err.response?.data?.message` before fallback strings.
  - Display numeric Affiliation ID plainly once returned.

---

## 6) Configuration

`config/services.php`:
```php
'affils' => [
  'base_url' => env('AFFILS_BASE_URL'),
  'api_key'  => env('AFFILS_API_KEY'),
  'timeout'  => 15,
  'fake'     => env('AFFILS_FAKE', false),
  'paths'    => [
      'list'           => '/api/affiliations_list/',
      'create'         => '/api/affiliation/create/',
      'detail'         => '/api/affiliation_detail/',
      'update_by_epid' => '/api/affiliation/update/expert_panel_id',
      'update_by_uuid' => '/api/affiliation/update/uuid',
      'cdwg_list'      => '/api/cdwg_list/',
      'cdwg_create'    => '/api/cdwg/create/',
      'cdwg_update'    => '/api/cdwg/id/%d/update/',
  ],
  'list_ttl' => 900,
],
```

**.env**
```
AFFILS_BASE_URL=https://am.example.org
AFFILS_API_KEY=********
AFFILS_FAKE=true            # enable for local/testing
```

---

## 7) Testing Notes

- **Feature tests** cover:
  - Creating affiliation id (201) and double‑request short‑circuit (409).
  - UUID mismatch (422) results in no outbound requests.
  - CDWG create success (201) and validation error (400) propagation.
  - Update path returns 200 on success, proper error mapping on failure.
  - Audit table rows are persisted with correct `status`.
- **Local/CI** should set `AFFILS_FAKE=true` to avoid polluting AM with test data.

---

## 8) API Examples

### Create Affiliation
```
POST /api/applications/{ep_uuid}/affiliation
→ 201 { "affiliation_id": 40123, "message": "Affiliation created successfully." }

If already set:
→ 200 { "affiliation_id": 40123, "message": "Affiliation already assigned." }

On error (AM):
→ 400/422 { "message": "This UUID already exists." }
```

### Get Affiliation Detail
```
GET /api/applications/{ep_uuid}/affiliation
→ 200 { "affiliation_id": 40123, ... } // proxied/normalized detail (depending on controller)
```

### Update Affiliation (status/names)
Triggered from GPM when group status changes:
```
→ 200 { "affiliation_id": 40123, "message": "Affiliation updated." }
→ 409 { "message": "Cannot update AM: expert panel has no affiliation_id yet." }
```

### CDWG Create
```
POST (internal) on GroupCreate when group_type_id == 2
Payload to AM: { "name": "<CDWG name>" }
Success → AM returns { "id": <int>, "name": "<CDWG name>" }
Failure → 400 with readable message bubbled back to UI
```

---

## 9) Known Limitations / Follow‑ups

- We assume AM’s `expert_panel_id` is the canonical **Affiliation ID**; we ignore AM’s numeric `affiliation_id` field to avoid confusion.
- AM sync is **best-effort** on status changes; failures are logged but do not block GPM workflows.
- Consider a background job/queue for AM calls if latency becomes noticeable or to better handle retries.
- If AM introduces new required fields, update the payload builders and validation accordingly.

---

## 10) Quick Reference (Key Files)

- **Actions**
  - `App/Modules/ExpertPanel/Actions/AffiliationCreate.php`
  - `App/Modules/ExpertPanel/Actions/AffiliationUpdate.php`
  - `App/Modules/Group/Actions/GroupCreate.php`
  - `App/Modules/Group/Actions/GroupStatusUpdate.php`

- **Controllers**
  - `App/Modules/ExpertPanel/Http/Controllers/Api/AffiliationController.php`

- **Services**
  - `App/Modules/ExpertPanel/Service/AffilsClient.php`

- **Models**
  - `App/Modules/ExpertPanel/Models/AmAffiliationRequest.php`
  - `App/Modules/ExpertPanel/Models/ExpertPanel.php`

- **Frontend**
  - `resources/js/components/applications/BasicInfoData.vue` (Create button)
  - `resources/js/components/groups/GroupForm.vue` (Create button in admin form)
  - `resources/js/store/groups.js` (action: `createAffiliationId`)

---

## 11) Glossary

- **EP:** Expert Panel (GCEP, VCEP, SCVCEP)
- **AM:** Affiliations Manager service (external)
- **Affiliation ID:** The **AM `expert_panel_id`** we store in `expert_panels.affiliation_id`
- **CDWG:** Clinical Domain Working Group
- **UUID:** GPM’s globally unique ID for an EP, also sent to AM and used to deduplicate/lookup

---

## 12) Changelog (high level)

- Added endpoints to request affiliation creation from UI.
- Implemented AffiliationCreate and AffiliationUpdate actions with audit logging.
- Hooked GroupStatusUpdate to sync EP status to AM.
- Added CDWG create/update calls during GroupCreate.
- Introduced fake mode in AffilsClient for local/testing.
- Frontend: buttons to trigger creation; store action; improved error surfacing.
