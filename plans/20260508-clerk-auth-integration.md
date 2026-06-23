# Clerk Auth Integration Plan
Date: 2026-05-08
Status: **COMPLETE** (as of 2026-06-23)

## Goal

Replace the current Fortify/Sanctum authentication stack with Clerk.com as the
external Identity Provider (IdP). GPM retains all authorization logic (Spatie
permissions, group/panel membership). Shared Clerk identity enables users to
authenticate once across multiple ClinGen applications; each app manages its own
access control independently.

## Architecture

- **Identity**: Clerk manages who users are (login, password reset, 2FA, email
  verification, profile).
- **Authorization**: GPM manages what users can do (Spatie roles/permissions,
  group membership, `active` flag).
- **Link**: `users.clerk_user_id` (Clerk's stable UUID) is the foreign key
  between Clerk identity and GPM user records.
- **API auth**: Stateless JWT via `Authorization: Bearer <session_token>` on
  every API request. No Sanctum stateful cookies. Works across subdomains and
  separate domains.
- **Provisioning**: Admin-led only. No auto-creation from Clerk webhooks. When
  an admin provisions a user in GPM, the provisioning action looks up or creates
  the Clerk account by email and stores the `clerk_user_id`.
- **Removal**: Setting `active = false` on the local user record revokes GPM
  access. The Clerk identity is preserved for other applications.
- **Impersonation**: DB-tracked via `active_impersonations` table, replacing
  `lab404/laravel-impersonate`. All `canImpersonate`/`canBeImpersonated`
  business logic remains on the User model.

## Key Decisions

- Option A provisioning (admin-led): many Clerk users may exist that need no
  GPM record.
- `user.created` webhook is not subscribed to. Webhooks only handle
  `user.updated` (sync name/email) and `user.deleted` (deactivate local record).
- Bearer tokens always (not cookies) for API calls to ensure cross-subdomain
  and cross-domain compatibility.
- `azp` (authorized parties) claim verified against a configured allowlist to
  prevent token replay across apps.
- 401 = no valid Clerk JWT; 403 = valid JWT but user inactive or not provisioned
  in GPM.
- Impersonation is handled within GPM at the application layer (not via Clerk's
  paid impersonation feature).
- `laravel/sanctum` was fully removed (not kept for future use); its session
  middleware, CSRF-cookie route, and `HasApiTokens` trait were all deleted.
- `laravel/fortify` and all associated Actions, config, and `FortifyServiceProvider`
  were fully removed; `AuthController` (password-reset/isAuthenticated) and the
  `MakeTokenForUser` artisan command also deleted in the same pass.

## Commit Sequence (as implemented)

### Phase 1 — Database & Config
1. `migration: add clerk_user_id and active columns to users table`
2. `chore: install clerkinc/backend-php and add Clerk config`

### Phase 2 — Backend JWT Layer
3. `feat: VerifyClerkToken middleware and ClerkUserResolver`
4. `feat: apply VerifyClerkToken to API routes alongside Sanctum`

### Phase 3 — Impersonation
5. `migration: create active_impersonations table`
6. `feat: ImpersonateStart and ImpersonateStop actions`
7. `refactor: integrate DB impersonation into VerifyClerkToken; remove lab404`

### Phase 4 — Webhooks
8. `feat: Clerk webhook endpoint for user.updated and user.deleted`

### Phase 5 — Admin Provisioning
9. `feat: update user provisioning to look up or create Clerk account`

### Phase 6 — Frontend
10. `chore: install @clerk/vue and register clerkPlugin in Vue app`
11. `feat: wire Clerk session token into Axios request interceptor`
12. `feat: replace LoginForm with Clerk SignIn component`

### Phase 7 — Migration & Cutover
13. `feat: artisan command to import existing users into Clerk`
    - Enhanced with `allow specification of specific user(s) to import` (optional
      `--user` argument to target individual users rather than all pending users)
14. `refactor: make VerifyClerkToken sole API auth; remove Sanctum stateful middleware`
    - Removes `EnsureFrontendRequestsAreStateful` from api middleware group
    - Removes `withCredentials` from Axios (no more session cookies)
    - Updates document download route (web.php) to use `auth.clerk`
15. `chore: remove laravel/fortify and laravel/sanctum; migrate all routes to auth.clerk`
    - Removes both packages via composer
    - Deletes `FortifyServiceProvider`, all `app/Actions/Fortify/` classes,
      `config/fortify.php`, `config/sanctum.php`, `config/laravel-impersonate.php`
    - Deletes `AuthController` and `MakeTokenForUser` artisan command
    - Replaces `auth:sanctum` with `auth.clerk` in all module route files
    - `VerifyClerkToken` updated to return 401 immediately (transition-period
      passthrough to Sanctum removed)
    - Removes `HasApiTokens` trait and Sanctum import from User model
    - Updates Vuex store: removes login/checkAuth Sanctum calls; logout is
      Clerk-only; `checkAuth` delegates to `forceGetCurrentUser`

### Phase 8 — Documentation
16. `docs: document Clerk auth architecture in CLAUDE.md and copilot-instructions.md`

### Post-Integration Fixes
17. `fix: add auth.clerk to routes missing authentication guards`
    - `/api/people/coc` and `/coc/attest` were outside the `auth.clerk` group,
      causing `$request->user()` to be null and a 500 on login
    - `PUT /api/users/{user}/roles-and-permissions` had no auth guard, allowing
      unauthenticated callers to trigger a 500
    - `GET /next-actions/assignees` was orphaned with no prefix or middleware;
      moved into api/applications under `auth.clerk`
18. `fix: repair impersonation start/stop flow`
    - `impersonateSelected()` now reloads the page after the API confirms
      success so the UI actually switches to the impersonated user
    - Stop-impersonation replaced dead `href="/impersonate/leave"` (old Fortify
      route) with a button calling `DELETE /api/impersonate` then reloading
    - Axios error interceptor guards against undefined `error.response` (network
      errors) to prevent a TypeError crash that was masking real failures

## Environment Variables Added

```
CLERK_PUBLISHABLE_KEY=pk_...
CLERK_SECRET_KEY=sk_...
CLERK_AUTHORIZED_PARTIES=https://gpm.clingen.org,http://localhost:8013
CLERK_WEBHOOK_SECRET=whsec_...
VITE_CLERK_PUBLISHABLE_KEY=pk_...
```

## Packages

- Added (PHP): `clerkinc/backend-php`
- Added (JS): `@clerk/vue`
- Removed (PHP): `laravel/fortify`, `lab404/laravel-impersonate`, `laravel/sanctum`
