# Clerk migration guide

Branch: `clerk-session-bridge` (September 2026). Reference for the
mechanics: `documentation/idp-clerk.md`.

## 0. Branch at a glance

`clerk-session-bridge` is 50 commits ahead of `main` (475 files, 12,618
insertions, 2,981 deletions) as of 2026-09-21. It bundles four pieces of
work, one concern per commit.

| Piece | What it adds |
|---|---|
| Laravel 13 upgrade | framework 11 → 13, activitylog v5 and permission v8, dompdf 3, json session serialization, cache hardening against unsafe deserialization, cache cleared on deploy |
| Clerk IdP session bridge | Clerk sign-in exchanged once for a Laravel session, IdP link on `users`, `idp:import-users`, mirroring and password sync, fake and null drivers, Clerk-first login page, impersonation logging, unguarded routes closed |
| Passport machine-to-machine access | OAuth client-credentials tokens from `/oauth/token`, `auth.session-or-client:<scope>` on `/api/report/*`, `oauth-client:list` / `oauth-client:revoke` |
| IdP-aware member invites | member-add typeahead merged with the Clerk directory, add a member from a ClinGen account, redeem an invite with a ClinGen account, refusal to invite an address that already has one |

Dependency changes against `main`:

| Package | main | branch |
|---|---|---|
| `laravel/framework` | ^11.0 | ^13.0 |
| `laravel/passport` | absent | ^13.8 |
| `firebase/php-jwt` | absent | ^7.0 |
| `spatie/laravel-activitylog` | ^4.0 | ^5.0 |
| `spatie/laravel-permission` | ^6.0 | ^8.0 |
| `dompdf/dompdf` | ^2.0 | ^3.0 |
| `laravel/tinker`, `rap2hpoutre/laravel-log-viewer` | ^2 | ^3 |
| `@clerk/vue` (npm) | absent | ^2.5.3 |

Schema changes, all applied by `php artisan migrate --force` in the container
entrypoint:

- `2026_09_18` alter `activity_log` for activitylog v5
- `2026_09_19` drop the stale `oauth_*` tables, drop the two-factor columns
  from `users`, add `users.idp_provider` and `users.idp_id` (unique pair)
- `2026_09_20` create the Passport tables

The member-invite piece adds no schema. `public/build/` is committed, so no
frontend build step runs at deploy time.

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
| Adding members | pick a GPM person, or invite a new name and email | the typeahead also lists ClinGen (Clerk) accounts with no GPM login; picking one links a GPM user to that account instead of sending an invite; inviting an address that already has a Clerk account is refused |
| Invite redemption | create an email and password | create a password, or sign in with an existing ClinGen account and link it with one click |

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

The onboarding wizard offers the invitee two paths.

- **Create a password.** `UserCreate` mirrors the account to Clerk with the
  plaintext password and `external_id` = person uuid, so both sign-ins work
  immediately. If Clerk is unreachable the local account is still created and
  the user is linked later by 2a or 2b.
- **I already have a ClinGen account.** The invitee signs in with Clerk inside
  the wizard and clicks Continue; `InviteRedeemWithIdp` links a new GPM user to
  that identity, redeems the invite and starts the session, so no second
  password ever exists.

### 2d. Adding members from ClinGen accounts

Because the Clerk instance is shared with other ClinGen applications, the
person a coordinator wants to add may already hold a ClinGen account without
any GPM record. The Add Member form handles three cases.

| Coordinator picks | What happens | Email sent |
|---|---|---|
| A GPM person who can already log in | `MemberAdd`, unchanged | added to group |
| A ClinGen account not in the GPM, or a GPM person without a login whose address matches one | `MemberAddFromIdp` re-reads the identity from Clerk, finds or creates the Person, `UserCreateFromIdpIdentity` links a User to it (redeeming any pending invite), then adds the membership and roles | added to group, with a note that their existing ClinGen account signs them in |
| A new name and email | `MemberInvite`, unchanged, creates the person and an invite code | invitation |

The typeahead behind the form is `GET /api/groups/{uuid}/members/candidates`.
It matches GPM people by substring on each typed field and, once the most
specific field has 3 or more characters, searches the Clerk directory with the
`query` filter (10 rows, cached 30 s server-side). Results merge in this order:

1. identities already linked to a `users.idp_id` are dropped;
2. identities where any verified address is a `users.email` are dropped;
3. identities whose address matches a person **without** a login are folded
   into that person's row and badged "Has ClinGen account";
4. the rest appear once per verified address, badged "ClinGen account, not yet
   in GPM".

If Clerk is unreachable the GPM rows still come back with
`idp_available: false` and the form shows a small note. `MemberInvite` refuses
(422) an address that already has a Clerk identity, pointing the coordinator at
the suggestion instead. With `IDP_DRIVER=null` the two new endpoints return 404
and the form behaves as before. Full mechanics: "Adding members and redeeming
invites" in `documentation/idp-clerk.md`.

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

## 4. Deployment guide

Deploy first with the IdP switched off, prove the stack on staging with a
Clerk development instance, then flip production and import. Every step is
reversible with `IDP_DRIVER=null`.

1. **Before the first deploy.** Add the secrets the branch needs even with the
   IdP off: `PASSPORT_PRIVATE_KEY` and `PASSPORT_PUBLIC_KEY` (PEM contents;
   generate a pair locally with `php artisan passport:keys` and copy the files
   from `storage/`). Set `IDP_DRIVER=null`. Leave the `CLERK_*` and
   `IDP_SEARCH_*` values unset for now. Confirm the image is PHP 8.2 or newer
   and that Redis and MySQL are reachable, as before. Never run
   `passport:install` against this database.
2. **Deploy the branch.** The entrypoint runs `composer install`,
   `php artisan migrate --force` (the migrations listed in §0) and, in
   production mode, clears the application cache and rebuilds the config,
   route, view and event caches. No frontend build is needed; the assets are
   committed. The migration that drops the old `oauth_*` tables only acts if
   they still exist.
3. **Check the deploy with the IdP off.** The login page is unchanged. Sign in
   with a password, open a group, add a member by name and by fresh invite, and
   download one report. Reports and reference endpoints now require a session,
   so announce that any external script must move to an OAuth client (§5).
4. **Staging against a Clerk development instance.** Configure the instance as
   in §3, then set `IDP_DRIVER=clerk`, `CLERK_PUBLISHABLE_KEY`,
   `CLERK_SECRET_KEY`, `CLERK_FRONTEND_API_URL` and `CLERK_AUTHORIZED_PARTIES`
   (the staging origin) and restart the app container. Then verify, in order:
   - `php artisan idp:import-users --all --dry-run`, review the plan, then
     `--all --throttle=200`; re-run once for stragglers and confirm the summary
     reports zero failures.
   - Sign in through Clerk with an imported account using its old password.
   - Change that password in GPM and confirm Clerk accepts the new one.
   - Redeem a fresh invite with **Create a password**; the Clerk identity
     appears with the person uuid as `external_id`.
   - Redeem a second fresh invite with **I already have a ClinGen account** in
     a private window: the dashboard opens, `invites.redeemed_at` is set,
     `users.idp_id` matches the Clerk user. Repeat with an identity already
     linked to another GPM user and confirm the guidance message and that you
     remain signed out.
   - In Add Member, type three letters of a Clerk user who is not in the GPM:
     rows badged "ClinGen account, not yet in GPM" appear, one per verified
     address. Add one: the membership exists, no invite email is sent, the
     added-to-group email carries the sign-in note, and that user can sign in
     through Clerk straight away.
   - Type the email of that same Clerk user into the invite fields without
     picking the suggestion: a 422 points you back to the suggestion.
   - Impersonate a user and leave; two activity-log entries appear with the
     admin as causer.
   - Create an OAuth client with `php artisan passport:client --client
     --name="Test"`, fetch a token from `/oauth/token` with
     `scope=reports:read`, call `/api/report/basic-summary`, then
     `oauth-client:revoke` it.
   - Do the visual pass of Clerk's sign-in component on `/login` and inside
     the wizard step; both have only been exercised with the fake driver so far.
5. **Production cutover.** Point the production Clerk instance's custom-domain
   CNAMEs, set the `CLERK_*` values for the production origin, flip
   `IDP_DRIVER=clerk` and restart. Run the import dry-run, then the import
   off-hours with `--throttle=200`, watch the summary, and re-run once for
   stragglers. Announce the Clerk-first login page; existing passwords keep
   working in both places.
6. **Rollback.** Set `IDP_DRIVER=null` and restart. Established sessions keep
   working, the session exchange and the two member-invite endpoints return
   404, the login page shows only the password form, the wizard offers only
   the password path, the member-add search returns GPM people only, and
   mirroring and profile sync become no-ops. Nothing this branch writes to
   Clerk has to be undone for GPM to work.
7. **What to watch afterwards.** In the Laravel log: `Could not mirror user to
   the identity provider` (invite redeemed while Clerk was down; the import or
   lazy linking picks the user up), `IdP directory search failed` (typeahead
   degraded; a run of these means Clerk rate limits or an outage), `Could not
   back-fill external_id` and `IdP identity carries a different external_id`
   (the shared-instance ownership question), and `IdP identity matched a user
   already linked to a different identity` (a merge candidate for a
   coordinator). Rate-limited import calls retry on their own and honour
   `Retry-After`.

Still open before production: the six fake-driver member-invite scenarios
listed in `documentation/idp-clerk.md` and the visual pass above were not run in a browser during
development; the automated suites (PHPUnit, Vitest, PHPMD, ESLint) pass with
only the pre-existing environmental failures.

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
- The Clerk `<SignIn>` component with hash routing inside `/login`, and now
  inside the invite wizard's "I already have a ClinGen account" step, has been
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
- An email address changed inside Clerk reaches `users.email` only at that
  user's next sign-in; the same webhook would keep it current between logins.
