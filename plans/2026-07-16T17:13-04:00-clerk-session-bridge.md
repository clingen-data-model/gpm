# Session-bridge Clerk auth: build out `feat/clerk-session-bridge`

## Context

We're comparing two architectural directions for Clerk auth in GPM: (1) the
stateless-bearer-token model already implemented on `feat/clerk-auth-integration`
/ `feat/clerk-auth`, vs. (2) a session-bridge model where Clerk verifies identity
once at login and GPM falls back to a normal Laravel session for everything
after. `feat/clerk-session-bridge` was just created off `main` with a single
cherry-picked spike commit (`350b0e366`, originally on the abandoned `sso-clerk`
branch) that proves the session-bridge mechanism works, but it's a rough POC:
no deactivation gate, Person-based identity linking, a bespoke ticket-based
invite flow, a webhook handler nobody asked for, and JWT payload decoding done
by hand instead of via the verifier that already ran.

The user chose **`feature/clerk-auth-migration`** (11 commits ahead of main) as
the feature-parity target — the "idiomatic Laravel guard" branch from
`plans/clerk-auth-branch-comparison.md`. Note: that comparison doc is now
**stale on two points** — current HEAD of `feature/clerk-auth-migration` already
fixes both the missing `iss` check and the `azp`-absent-bypass gap (commit
`960878f45`). Don't re-flag those as gaps; the mitigation to port over is
already correct.

Per the user's earlier answers: build to full feature parity, and keep the
existing Fortify/Sanctum session/CSRF infrastructure already on `main` rather
than stripping it — the whole point of a session-bridge is to ride that
existing machinery, not reinvent it.

**Goal of this branch**: same request-time guarantees as
`feature/clerk-auth-migration` (identity resolution incl. lazy-link,
stateless-JWT impersonation, invite redemption, no deactivation gate — it
doesn't have one either, so we don't invent one) — but the *primary* identity
check happens once at login (session established), not on every request.
Impersonation deliberately stays exactly as designed on the reference branch
(stateless JWT, `X-Impersonate-Token` header) since it's already decoupled
from how the primary identity was established — this is itself a useful
finding: impersonation's architecture doesn't have to change based on the
session-vs-bearer choice.

## What gets ported near-verbatim (transport-agnostic on the reference branch)

These don't care whether the caller is session- or bearer-authenticated —
copy them from `feature/clerk-auth-migration` with import-path adjustments only:

- `app/Services/Clerk/ClerkTokenVerifier.php` — JWKS fetch/cache, `firebase/php-jwt`
  decode, `iss`/`azp` checks (already-fixed version).
- `app/Services/Clerk/ImpersonationTokenService.php` — HS256 JWT derived from
  `APP_KEY`, 1800s TTL, `issue()`/`verify()`.
- `app/Http/Controllers/Api/ImpersonateController.php` (`take`/`leave`) — no
  changes needed; `leave()` stays a no-op 204.
- Whatever `ClerkApiClient` (hand-rolled `Http::withToken()` wrapper, used for
  the email-lookup fallback) exists on that branch — reuse rather than
  reintroducing the POC's `clerkinc/backend-php`-based `ClerkClientFactory`.
- `app/Modules/User/Actions/UserCreate.php`'s `?string $clerkId` param +
  eager-link-on-create behavior.
- `app/Modules/Person/Actions/InviteRedeem.php` and `InviteRedeemForExistingUser.php`
  — Clerk-session-token verification, find-or-create via `UserCreate`, person
  association. **One addition needed**: after a successful redeem, call
  `Auth::guard('web')->login($user)` + `$request->session()->regenerate()` so
  the brand-new user is immediately session-authenticated (on the reference
  branch this isn't needed because every subsequent request re-verifies the
  bearer token; here we need to establish the session at the same point).
- Frontend: `resources/js/components/ClerkSignUp.vue`,
  `resources/js/components/onboarding/AccountCreationForm.vue`, and
  `resources/js/domain/onboarding_service.js`'s `redeemInvite()` — retire the
  POC's parallel ticket-based `AcceptInvitation.vue` flow in favor of this
  already-working one rather than maintaining two invite UX patterns.
- `resources/js/domain/impersonate_service.js` (`take`/`leave`/`search`) and
  the `getImpersonationToken`/`setImpersonationToken`/`clearImpersonationToken`
  helpers from `resources/js/clerk.js` — port just those three sessionStorage
  helpers (as a small composable, e.g. `resources/js/composables/useImpersonationToken.js`),
  not the whole vanilla-`@clerk/clerk-js` wrapper (see deviations below).
  `ImpersonateControl.vue`, `ImpersonateSearchController`, `ImpersonatableUserResource`
  already exist identically on `main` — no change needed.

## What's new (the actual session-bridge-specific work)

1. **Migration**: `add_clerk_id_column_to_users_table` — `clerk_id` nullable
   unique string after `email`, matching the reference branch's column exactly
   (not `clerk_user_id`, not on `Person`). Remove the POC's
   `add_clerk_user_id_column_to_people_table` migration and drop
   `Person.clerk_user_id` entirely — it's dead (never wired into the model).
   No `active` column — the reference branch doesn't have a deactivation gate,
   so parity means not inventing one here either.

2. **`ClerkAuthenticate` middleware** (rewrite the POC's version): call the
   ported `ClerkTokenVerifier::verify()` instead of the official SDK +
   hand-rolled base64 payload decode. Used only on: the login-exchange route,
   `InviteRedeemForExistingUser`'s route, and nowhere else — this is the
   opposite of the reference branch, where it guards nearly every route.

3. **`ClerkSessionLogin` action** (rewrite the POC's version): extract the
   resolution logic from the reference branch's `ClerkGuard::resolveFromClerkClaims()`
   (match by `clerk_id`, fall back to verified-email match with
   `forceFill(['clerk_id' => $clerkId])->save()` lazy-linking) into this action
   instead of a custom guard. On no match at all → 403 (send the user through
   invite/onboarding). On match → `Auth::guard('web')->login($user)` +
   `$request->session()->regenerate()`.

4. **`ApplyImpersonationToken` middleware** (new — no direct equivalent
   needed on the reference branch, since there impersonation-check is just a
   branch inside the single `ClerkGuard` closure). Runs immediately after the
   standard `auth:web` middleware on protected API routes: if
   `X-Impersonate-Token` is present, verify via `ImpersonationTokenService`,
   look up the target `User`, and swap `Auth::setUser($target)`, stashing
   `impersonator_id` on the request (same attribute name the `User` model's
   `getImpersonatedByAttribute()`/`getIsImpersonatingAttribute()` already read
   on the reference branch — port those two accessors onto the `User` model
   here too).

5. **Route wiring**: replace `auth.clerk`/`clerk.gpm` (POC) middleware on all
   protected routes with `['auth', ApplyImpersonationToken::class]` (the `auth`
   alias already resolves via the session `web` guard on `main` — no new guard
   registration needed, unlike the reference branch's `Auth::viaRequest('clerk', ...)`).

6. **Logout**: extend the existing Fortify-era logout to also call
   `Auth::guard('web')->logout()` + session invalidate/regenerate; frontend
   `logout()` action additionally calls the Clerk SDK's client-side sign-out.

7. **Config**: replace the POC's `config/clerk.php` keys with the reference
   branch's (`frontend_api_url`, `authorized_parties`, `jwks_cache_ttl`,
   `clock_skew_leeway`, `impersonation_ttl`, `publishable_key`). Drop
   `webhook_signing_secret`/`invitation_redirect_url`/`jwt_key`/`base_url` —
   no webhook on the parity target.

## Explicitly dropped from the POC (no equivalent on the parity target)

- `ClerkWebhookReceive.php` / `ClerkWebhookService.php` — reference branch has
  zero webhook handling (confirmed via `git grep -i webhook/svix` returning
  nothing on that branch). Remove rather than keep as unused extra scope.
- `ClerkClientFactory.php` / `ClerkInvitationService.php` / `ClerkUserLinkService.php`
  — superseded by the ported `ClerkApiClient` + `ClerkGuard`-derived resolution
  logic described above.
- `invites.clerk_invitation_id` / `invites.expires_at` columns — already
  wired into `Invite::$fillable` from the cherry-pick, but nothing in the
  ported invite-redeem flow uses them (reference branch's `InviteRedeem`
  matches by plain `code`). Drop the migration and the two `$fillable` entries
  to avoid dead schema.
- `ClerkMe.php` debug endpoint — drop, no longer needed once `ClerkSessionLogin`
  has real error responses.

## Intentional deviations from strict parity (flagged, not silent)

- **Keep Fortify + Sanctum** (per earlier decision) — the reference branch
  dropped Fortify but kept Sanctum "likely as an oversight" per the comparison
  doc; here we keep both on purpose so the legacy password login keeps working
  side-by-side with Clerk during the comparison.
- **Keep `@clerk/vue`** (already used by the cherry-picked POC's
  `ClerkLogin.vue`) instead of porting the reference branch's vanilla
  `@clerk/clerk-js` wrapper (`resources/js/clerk.js`) — the choice of Vue SDK
  binding is orthogonal to the session-vs-bearer question. Only the three
  sessionStorage impersonation-token helpers get ported, not the whole file.
- **`lab404/laravel-impersonate`** is still in `composer.json`/`User` model on
  `main`. The reference branch removed it. Before removing it here, grep for
  other call sites beyond the impersonate start/stop routes (e.g. any
  `$user->impersonate()` calls elsewhere) — if none, drop the package the same
  way the reference branch did; if something else depends on it, leave it
  installed and just stop using it on the auth-relevant path.

## Verification

- Adapt the reference branch's Mockery-based test pattern
  (`Mockery::mock(ClerkTokenVerifier::class)->shouldReceive('verify')->andReturn(...)`,
  bound via `$this->app->instance(...)`) for the new `ClerkSessionLogin`
  action's tests instead of hitting real Clerk/JWKS.
- Port `tests/Unit/Clerk/ImpersonationTokenServiceTest.php` and
  `tests/Feature/Integration/ImpersonateSearchTest.php` unchanged (both
  transport-agnostic).
- New Feature tests to write (no reference-branch equivalent, since the
  mechanism itself is new): session established after
  `ClerkSessionLogin`/`InviteRedeem` (assert `Auth::check()` + a subsequent
  request succeeds using only the session cookie, no Authorization header),
  lazy-link-by-email happens exactly once, 403 on no match, CSRF rejection on
  a mutating request missing the `X-XSRF-TOKEN` header (this is the actual
  point of comparison — assert it's enforced), and impersonation
  start/leave/isolation behave identically to the bearer-token branch's tests.
- Manual: `docker compose exec -it app php artisan test`, then drive the
  actual login → protected-route → impersonate-take → impersonate-leave →
  logout flow in a browser to confirm cookies/CSRF work end-to-end (the thing
  that can't be fully verified by unit tests alone).
