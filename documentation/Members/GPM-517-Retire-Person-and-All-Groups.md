# GPM-517 — Retire a Person (and from all Groups)

**Date:** 2025-10-21  

## Summary

Add an Admin action on **Person → Admin tab** labeled **"Retire Person"**.  
When executed, the system:

1. Iterates all **active memberships** for that person and retires each one (sets `end_date`).
2. Optionally **Disable Login** the Person from their User record.
3. Adds **reason** why retire the individual from all groups.
4. Returns a concise summary JSON so the UI can show an accurate alert.

No schema change to `people` (we **do not** add `retired_at`, `retired_by_id`, or `retired_reason`).

---

## User ↔ Group Related Models and Actions

- **User**: authentication identity (login).  
- **Person**: Linked to User via `people.user_id`.
- **Member (GroupMember)**: Person’s membership in a Group.

**Available actions**
- **Remove Member**: removes one membership from one group.  
- **Retire/Unretire Member**: set/clear `end_date`.  
- **Retire Person (this ticket)**: retire **all** active memberships; optionally **disable login (soft-delete User)**.
- **Delete Person** (existing): remove all memberships, unlink Person↔User, delete `Person`, soft-delete User (via `UserDelete`).

---

## UI (Vue 3, Options API)

Button sits under **Delete Person** (Admin tab). The confirm modal calls the new endpoint and displays how many groups were affected.

### Success alert text

Backend returns:
```json
{"person_id": 3321, "memberships_retired": 2, "disable_login": false}
```

Show: **"Retired from 2 groups."**

### Example handler

> **Important:** send **`disable_login`** (boolean) and `reason` (optional).


---

## API

**Route**
```php
// app\Modules\Person\routes\api.php
Route::middleware(['auth:sanctum'])->post('/people/{person:uuid}/retire', \App\Modules\Person\Actions\PersonRetireAll::class);
```

**Request (JSON)**
```json
{"disable_login": true, "reason": "Left ClinGen"}
```

**Response (JSON)**
```json
{"person_id": 3321, "memberships_retired": 2, "disable_login": false}
```

**Authorization**: `people-manage` (mirrors `PersonDelete`)

---

## Backend Implementation

### 1) Person Action (final)

`app/Modules/Person/Actions/PersonRetireAll.php`


### 2) Reused MemberRetire (from Group Action)  with reason appended to notes

### 3) MemberUnretire (restores the soft-deleted User)

When unretiring, if we need the account active again:

```php
$groupMember->person?->user()->withTrashed()->first()?->restore();
```


---

## Acceptance Criteria

- Admin tab shows a button **"Retire Person"** beneath **Delete Person**.
- Clicking the button opens a confirmation modal with:
  - Optional textarea "Reason".
  - Checkbox "Also disable login for this user.".
- On confirm, UI calls `POST /api/people/{uuid}/retire` and shows a success alert:  
  "**Retired from N group(s).**".
- All active memberships for that person have `end_date` set.
  - **Meeting Notes - 10/24/2025:**
We decided to continue using hard delete for the `User` data, but keep the `Person` data. In the future, if the same `User` return, we can create a new user and link it to the existing `Person` record via the `user_id` field in the `people` table.
- If "Disable Login" was chosen, the system hard delete the `user` of that `Person`.

- Action logs warnings for any membership failures but continues the loop (partial progress).

---

## Sample Response (real)

```json
{"person_id": 3321, "memberships_retired": 2, "disable_login": false}
```