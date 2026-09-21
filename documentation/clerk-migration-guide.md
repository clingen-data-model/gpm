# Clerk migration guide

Branch: `clerk-idp-session-bridge` (September 2026). Reference for the
mechanics: `documentation/idp-clerk.md`.

## 1. What the branch does

GPM can now sign users in through Clerk **as well as** with local
email/password. Both paths end in the same Laravel session, so nothing
downstream (permissions, impersonation, downloads, tests) had to change.
Coordinators can also add members straight from an existing ClinGen account
(the member-add typeahead searches the Clerk directory), and invitees may
redeem an invitation with a ClinGen account instead of creating a password;
see "Adding members and redeeming invites" in `documentation/idp-clerk.md`.

| Area | Before | After |
|---|---|---|
| Sign-in | Fortify password login only | Clerk-first login page; password form behind a toggle |
| Request auth | Sanctum cookie session | Unchanged. Clerk token is verified **once** at `POST /api/idp/session-login`, then a normal session |
| Identity link | none | `users.idp_provider` + `users.idp_id` (unique pair) |
| Existing users | n/a | `php artisan idp:import-users` creates or links Clerk identities, keeping passwords |
| New users | invite → local account | invite → local account **and** a mirrored Clerk identity |
| Password changes | local only | pushed to Clerk for linked users |
| Name/email changes in Clerk | n/a | refreshed into GPM at each Clerk sign-in |
| Machine access | ad-hoc `make:user-token` | OAuth 2.0 client credentials (Passport): 10-minute scoped tokens from `POST /oauth/token`; `auth.session-or-client:<scope>` middleware on `/api/report/*` |
| Impersonation | lab404 session swap, unlogged | same mechanism, take/leave written to the activity log with the admin as causer |
| Local dev / CI | needs nothing | `IDP_DRIVER=fake`: offline stand-in with a login-page picker, dev endpoints, in-memory store for PHPUnit |
| Stack cleanup | Passport leftovers, unused 2FA, duplicate reset route, ~20 unguarded routes | removed / fixed / guarded |

Rollback at any time: `IDP_DRIVER=null`. Local passwords are never modified by
any of this, so the password form keeps working.

## 2. How Clerk accounts get created for existing users

Every existing GPM login is a row in `users` with an email and a bcrypt
password hash. Clerk's Backend API accepts bcrypt digests directly, so users
keep their current password. Three mechanisms cover everyone:

### 2a. Bulk import (the main path)

```
php artisan idp:import-users --all --dry-run     # report only
php artisan idp:import-users --all --throttle=200
php artisan idp:import-users 123 jane@example.com  # specific ids or emails
```

For each **unlinked** user, in order:

1. **Link if Clerk already has the person.** Look up by `external_id` equal to
   the user's `people.uuid`, then by email. Other ClinGen systems may share the
   Clerk instance, so an identity may already exist. When found, GPM stores its
   id and, if the Clerk record has no `external_id`, backfills it with the
   person uuid (`--no-backfill-external-id` disables that).
2. **Otherwise create** the Clerk user from `App\Services\Idp\IdpUserPayload`:

   | Clerk field | Source |
   |---|---|
   | `email_address` | `users.email` |
   | `first_name`, `last_name` | the Person's structured name, else `users.name` split on the first space |
   | `external_id` | `people.uuid` (null when the user has no Person) |
   | `password_digest` + `password_hasher: bcrypt` | `users.password` as stored (Laravel `$2y$` hashes) |
   | `skip_password_checks: true` | imported credentials are trusted as-is |
   | `created_at` | `users.created_at` |

3. Store `idp_provider='clerk'`, `idp_id=<Clerk user id>` on the row.

Properties of the command:

- **Idempotent.** Linked users are skipped, so it can be re-run for stragglers
  after a partial failure.
- **Rate-limit aware.** `--throttle` sleeps between creates and a 429 triggers
  exponential backoff.
- **Continues on error**, prints one line per user and a summary, and exits
  non-zero if anything failed.
- Never writes in `--dry-run`.

What users experience afterwards: the Clerk sign-in box accepts the same email
and password they already use. "Forgot password" can be done either in Clerk
or through GPM's local reset (which pushes the new password to Clerk).

Every GPM user has a password hash (`UserCreate` always hashes something, even
a random string for admin-created accounts), so the import never needs
`skip_password_requirement`. Users who never knew their password use a reset,
as today.

### 2b. Lazy linking at first Clerk sign-in

If someone signs in with Clerk before being imported (or the import missed
them), `UserFindByIdpIdentity` resolves them at the exchange: by stored link,
then by the Clerk record's `external_id` (person uuid), then by email
(case-insensitive). A match is persisted. A user already linked to a
*different* Clerk identity is refused rather than re-pointed, and an identity
that matches nobody gets a 403 with a "not linked" message.

### 2c. New users from now on

Invite redemption is unchanged for the invitee (email + password in the
onboarding wizard). `UserCreate` then mirrors the account to Clerk with the
plaintext password and `external_id` = person uuid, so they can use either
sign-in immediately. If Clerk is unreachable the local account is still
created and the user is linked later by 2a or 2b.

### Edge cases to expect during import

- **Email already in Clerk under a different `external_id`** (created by
  another ClinGen app with its own id scheme): the user is linked by email;
  the backfill of `external_id` will fail with a conflict and be logged, not
  fatal. Decide who owns `external_id` on the shared instance before prod.
- **Users without a Person** (service-style admin logins): imported with
  `external_id` null; linking later relies on email.
- **Case differences in email**: matching is case-insensitive on both sides.
- **Duplicate identities across apps**: the command links rather than
  creates, so no duplicates are made by GPM.
- **Email verification**: Backend-API-created addresses should be marked
  verified so imported users are not challenged on first sign-in. Confirm on
  the dev instance before the prod run.

## 3. Clerk instance setup

Use a development instance for local/staging and a production instance (with
the custom-domain CNAMEs) for prod. In the dashboard:

- **User & Authentication:** enable email address and password. Leave
  username/phone/social off unless wanted.
- **Sign-up: restricted.** GPM creates identities through the Backend API;
  nobody should self-register. (Clerk's `<SignIn>` still shows a sign-up link
  unless sign-up is restricted.)
- **Sessions → customize session token (optional):** add
  `"email": "{{user.primary_email_address}}"`. GPM then can link by email even
  if the Backend API call fails during the exchange.
- Note the **Frontend API URL** (the JWT issuer) and the publishable and
  secret keys.

Environment variables (`config/idp.php`):

```
IDP_DRIVER=clerk
CLERK_PUBLISHABLE_KEY=pk_...
CLERK_SECRET_KEY=sk_...
CLERK_FRONTEND_API_URL=https://<slug>.clerk.accounts.dev   # prod: https://clerk.<domain>
CLERK_AUTHORIZED_PARTIES=https://<gpm origin>              # comma separated; must match the browser origin
```

The SPA gets the driver and publishable key from `window.__GPM_IDP__`
(injected by `ViewController`), so the committed build assets need no
per-environment rebuild. Laravel reads `.env` from the bind mount; no
docker-compose changes are needed.

## 4. Cutover plan

1. **Deploy the branch with `IDP_DRIVER=null`.** Migrations run from the
   entrypoint (drop old oauth tables, drop 2FA columns, add IdP columns, create
   Passport tables). The login page is unchanged in this mode. Announce that
   reports and reference endpoints now require a session (external scripts
   should move to an OAuth client, §5).
2. **Staging / dev instance:** set `IDP_DRIVER=clerk` and the Clerk values.
   - `idp:import-users --all --dry-run`, review the plan, then `--all`.
   - Sign in through Clerk with an imported account using its old password.
   - Change that password locally, confirm Clerk accepts the new one.
   - Redeem a fresh invite, confirm the Clerk identity appears with the person
     uuid as `external_id`.
   - Impersonate and leave, check the two activity-log entries.
   - Create an OAuth client, fetch a token from `/oauth/token`, call `/api/report/basic-summary`.
3. **Production:** flip `IDP_DRIVER=clerk`, run the dry-run, then the import
   off-hours with `--throttle=200`, watch the summary, re-run once for
   stragglers.
4. **Rollback:** `IDP_DRIVER=null`. Sessions already established keep working;
   the exchange endpoint returns 404; mirroring and sync become no-ops.

## 5. OAuth clients for machine callers

```
php artisan passport:client --client --name="GeneTracker"   # prints id + secret once
php artisan oauth-client:list
php artisan oauth-client:revoke <client-id>
```

Callers POST `grant_type=client_credentials`, `client_id`, `client_secret`
and `scope` to `/oauth/token` and send the returned 10-minute token as
`Authorization: Bearer`. Only routes carrying `auth.session-or-client:<scope>`
accept client tokens (today `/api/report/*` with `reports:read`); scopes are
declared in `App\Providers\OAuthServiceProvider`. Deployed environments need
the `PASSPORT_PRIVATE_KEY`/`PASSPORT_PUBLIC_KEY` secrets; local dev runs
`php artisan passport:keys`. See `documentation/m2m-oauth-clients.md`.

## 6. Known gaps

- A password changed **inside Clerk** is not written back to GPM, so the
  local password form keeps accepting the old one for that user. Options
  (tracked in `documentation/future-tasks.md`): hide the local form for linked
  users after cutover, or add a svix-signed webhook endpoint.
- The Clerk `<SignIn>` component with hash routing inside `/login` has been
  exercised only through the fake driver and unit tests; do a visual pass on
  the dev instance.
- `external_id` ownership across ClinGen apps sharing the Clerk instance needs
  an agreement (see §2 edge cases). Adding a member from a Clerk account and
  redeeming an invite with one now also back-fill `external_id` when it is
  empty (never overwriting a different value, which is logged instead).
- The member-add typeahead calls Clerk's `GET /users?query=` once per request
  (3-character minimum, 500 ms debounce in the SPA, `limit=10`, 30 s server
  cache). If the instance's rate limit is hit, searches degrade to GPM-only
  results with a note; a feature flag to switch the directory search off is
  tracked in `future-tasks.md`.
- Only verified Clerk addresses (and the primary) are offered when adding a
  member from a Clerk account; an unverified secondary address cannot be picked.
