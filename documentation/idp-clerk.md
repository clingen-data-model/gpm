# External identity provider (Clerk) and the session bridge

GPM accepts sign-ins from an external identity provider (IdP) **in addition to**
local email/password credentials. Clerk (clerk.com) is the first provider; the
integration is behind two small contracts so another provider could be added.

## How a sign-in works

1. The SPA renders the IdP's sign-in UI on `/login` (Clerk's `<SignIn>` component,
   or the fake picker in development). The local password form stays available
   behind a toggle.
2. When the IdP reports a signed-in user, the SPA asks it for a short-lived session
   token and POSTs it once to `POST /api/idp/session-login` as a bearer token
   (`resources/js/store/index.js`, action `idpSessionLogin`).
3. `App\Actions\Auth\IdpSessionLogin` verifies the token through the
   `TokenVerifier` contract (Clerk: RS256 against the instance JWKS, checking
   `iss`, `azp`, `exp`, `nbf`), resolves the local `User`, syncs `name`/`email`
   from the IdP record, and establishes a normal Laravel session.
4. Every later request uses the Sanctum cookie session exactly as a local login
   does. The IdP is not contacted again until the next sign-in.

Logout ends the Laravel session (Fortify `POST /api/logout`) and then the IdP
session (`useIdp().signOut()`), otherwise `/login` would sign the user straight
back in.

### Resolving the local user

`App\Modules\User\Actions\UserFindByIdpIdentity`, in order:

1. `users.idp_provider` + `users.idp_id` (already linked)
2. the IdP record's `external_id`, which GPM sets to `people.uuid`
3. the IdP record's primary email (or an `email` claim in the token)

Matches on 2 and 3 are persisted so later logins take path 1. A user already
linked to a *different* identity is never re-pointed. Unknown identities get
a 403 with a "not linked" message.

### Managing sign-in methods

`/account/sign-in-methods` ("Sign-in methods" in the user menu, shown only when
an IdP is configured) renders Clerk's `<UserProfile>`, where a user manages the
connected Google/GitHub accounts, extra email addresses and password on their
*identity*. GPM stores none of it and `users.idp_id` does not change when a
social account is linked, so nothing here needs syncing back.

Most users never need the page: Clerk links an OAuth sign-in to an existing
identity automatically when the provider returns a **verified** email matching
it, and imported addresses are admin-verified. The page covers what cannot be
automatic — linking an account whose email differs from the GPM one (add the
address here first), or removing a connection. Social sign-in also sidesteps
Device Trust, which only challenges password sign-ins.

## Schema

- `users.idp_provider` (e.g. `clerk`) + `users.idp_id`, unique as a pair.

## Keeping the two directories in step

| Change | Direction | Where |
|---|---|---|
| New local user (invite redemption, `user:create`) | GPM → IdP | `UserIdpMirror`, called from `UserCreate` |
| Local password change/reset | GPM → IdP | `UserIdpPasswordSync`, called from the Fortify actions and `user:change-password` |
| Name/email changed in the IdP | IdP → GPM | `UserIdpProfileSync` at each session login |
| Existing users | GPM → IdP | `php artisan idp:import-users` |

IdP failures on the GPM → IdP paths are logged and never block the local
change; unlinked users are picked up by lazy linking or the import command.

A password changed **inside Clerk** is not pushed back to GPM, so the local
password form would keep accepting the old password. See future-tasks.

## Configuration

`config/idp.php`, driven by env:

```
IDP_DRIVER=null            # clerk | fake | null (IdP sign-in disabled)
IDP_PROVIDER_NAME=clerk    # value stored in users.idp_provider
IDP_SYNC_PROFILE=true
IDP_MIRROR_USERS=true

CLERK_PUBLISHABLE_KEY=pk_...
CLERK_SECRET_KEY=sk_...
CLERK_FRONTEND_API_URL=https://<slug>.clerk.accounts.dev   # prod: https://clerk.<domain>
CLERK_AUTHORIZED_PARTIES=http://localhost:8013             # comma separated; defaults to APP_URL

IDP_FAKE_STORE=storage/app/fake-idp/users.json             # empty => in memory
```

The SPA learns the driver and publishable key from `window.__GPM_IDP__`,
injected by `ViewController` into `resources/views/app.blade.php` (the built
assets are shared across environments, so nothing IdP-specific is baked in at
build time). Laravel reads `.env` from the bind mount, so no docker-compose
changes are needed for these variables.

### Clerk dashboard settings

- Email address + password enabled.
- Sign-up is **public**, and that is deliberate: the Clerk instance is shared
  with other ClinGen software, so people may hold an identity without being GPM
  users. GPM itself creates identities through the Backend API (mirror on
  invite redemption, import command). A self-registered identity has no
  `external_id` GPM recognises and no matching `users.email`, so the session
  bridge rejects it with "not linked" — signing up grants nothing here.
- Optional: customise the session token to add `"email": "{{user.primary_email_address}}"`
  so the exchange can link by email even when the Backend API is unreachable.
- Production needs the custom-domain CNAMEs before `CLERK_FRONTEND_API_URL`
  resolves; `iss` differs between dev (`*.clerk.accounts.dev`) and prod.
- **Device Trust** (Protect → Rules) is auto-enabled for applications created
  after 2025-11-14. Decide it deliberately before the cutover — see
  "First sign-in after an import" below.

The instance's live settings are readable without auth, which is quicker than
clicking through the dashboard:

```
curl -s "$CLERK_FRONTEND_API_URL/v1/environment?_clerk_js_version=5.0.0" | jq .user_settings
```

Note that Device Trust does **not** appear in that payload; it only surfaces as
an extra step during a sign-in attempt.

## Local development and tests: the fake driver

`IDP_DRIVER=fake` needs no network and no Clerk account:

- `/login` shows a "Fake identity provider" picker listing fake identities and
  local users. Choosing one calls `POST /dev/idp/token`, which mints an HS256
  token signed with `APP_KEY` (creating the fake identity from the local user
  on first use), and the SPA performs the normal exchange.
- Identities live in `storage/app/fake-idp/users.json`; delete it to start over.
- `GET /dev/idp/users` and `POST /dev/idp/token` 404 outside the fake driver
  and in production.
- PHPUnit runs on the fake driver with an in-memory store (`phpunit.xml`).
  `tests/TestCase.php::loginViaIdp()` establishes a real session through the
  exchange; the usual `login()` helpers still use `Sanctum::actingAs`.
  `FakeIdpClient::failNext()` simulates an outage.

Code: `app/Services/Idp/` (contracts, `Clerk/`, `Fake/`, `NullDriver/`),
bound by `app/Providers/IdpServiceProvider.php`.

## Importing existing users

```
php artisan idp:import-users --all --dry-run
php artisan idp:import-users --all --throttle=200
php artisan idp:import-users 123 jane@example.com
```

For each unlinked human user: link an identity the IdP already has (by
`external_id` = person uuid, then email; `external_id` is backfilled), else
create one with `password_digest` = the bcrypt hash from `users.password`, so
the password keeps working. Idempotent; re-run for stragglers. Exit code is
non-zero when any user failed.

Cutover order: deploy with `IDP_DRIVER=null` → on the staging/dev instance set
`clerk`, dry-run, import, verify → on prod flip to `clerk`, dry-run, import
off-hours. Rollback is `IDP_DRIVER=null`; local passwords are never modified.

Clerk's list filters take a **repeated** query key (`?email_address=a&email_address=b`).
The bracketed arrays `http_build_query()` produces are ignored, and the request
degrades into an unfiltered `GET /users?limit=1` that returns the same account
for every lookup — so the import silently links every user to one identity.
`ClerkClient::queryString()` exists for this; do not hand the filter to
`Http::get($url, $query)` as an array. Covered by `tests/Unit/Idp/ClerkClientTest.php`.

Always read a `--dry-run` before importing for real: repeated "linking to
existing identity <same id>" lines are the signature of a broken lookup.

### Call budget

Backend API limits are 1000 requests per 10s on production instances and 100
per 10s on development ones; a 429 carries `Retry-After`, which the command
waits out (every call is retried, not just creates). There is no bulk
user-create endpoint — `POST /v1/users` takes one user — so the only batching
available is on reads. `--all` therefore indexes the directory first, paging
`GET /v1/users` 500 at a time, and resolves every link from that index:
one call per 500 identities instead of two lookups per user. Pass
`--no-prefetch` to go back to per-user lookups. Explicit user arguments always
look up individually, where paging the directory would not repay itself.

For ~3,800 users that is one list call plus one create per user, well inside
even a development instance's budget. Raise `--throttle` if creates alone
start drawing 429s.

### First sign-in after an import

Imported identities are sound: addresses created through the Backend API come
back `{"status": "verified", "strategy": "admin"}`, and the bcrypt digest makes
`password_enabled` true. Two things still stand between a user and a session.

**Device Trust.** Clerk requires a supplementary email code when all three hold:
a valid password was entered, the user has no MFA, and the device is new. Every
imported user meets all three on first sign-in (`last_sign_in_at` is null, so
every device is new), so the whole user base gets a code on day one and any
delivery failure is a lockout. This is independent of which first factors are
enabled — it follows a *successful* password, so disabling the `email_code`
first factor does not avoid it. Decide before the cutover; to turn it off:

```
npx clerk@latest config patch --json '{"auth_password":{"device_trust":{"enabled":false}}}'
```

**Development-instance email.** Dev instances (`sk_test`, `*.clerk.accounts.dev`)
send through Clerk's shared sender with no domain authentication, and
institutional mail systems drop it routinely — the code never arrives. Two ways
through:

- Mint a sign-in ticket (`ticket` is an enabled first factor) and open
  `/login#/?__clerk_ticket=<token>`:
  `POST https://api.clerk.com/v1/sign_in_tokens {"user_id": "user_..."}`.
  The dashboard's "Impersonate user" action does the same thing.
- On instances where `auth_config.test_mode` is true, addresses containing
  `+clerk_test` accept the fixed code `424242`. Avoid this on real records:
  with `IDP_SYNC_PROFILE` on, the altered address is written back to
  `users.email` at the next session login.

Production sends from your own verified domain, so delivery should not be the
problem there; sign-in tickets still work if it is.

## Machine-to-machine access (OAuth clients)

Other systems authenticate with OAuth 2.0 client credentials (Laravel
Passport): a client id and secret are exchanged at `POST /oauth/token` for a
10-minute bearer token carrying scopes. Routes open to machines carry
`auth.session-or-client:<scope>`, which passes a signed-in session and
otherwise requires a client token with the scope. Full details, caller guide
and key handling: `documentation/m2m-oauth-clients.md`.

## Impersonation

Unchanged mechanism (lab404/laravel-impersonate session swap via
`/impersonate/take/{id}` and `/impersonate/leave`); it works regardless of
how the admin signed in. Take and leave are now recorded in the activity log
as `user-impersonation-started` / `-ended` on the impersonated user, with the
admin as causer.
