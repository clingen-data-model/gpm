# Clerk Auth Integration Plan
Date: 2026-05-08

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

## Commit Sequence

### Phase 1 — Database & Config (no behavior change)
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
9. `feat: update user provisioning action to look up or create Clerk account`

### Phase 6 — Frontend
10. `chore: install @clerk/vue and register clerkPlugin in Vue app`
11. `feat: wire Clerk session token into Axios request interceptor`
12. `feat: replace LoginForm with Clerk SignIn component`

### Phase 7 — Migration & Cutover
13. `feat: artisan command to import existing users into Clerk`
14. `refactor: make VerifyClerkToken sole API auth; remove Sanctum stateful middleware`
15. `chore: remove laravel/fortify and related config, routes, and actions`

### Phase 8 — Documentation
16. `docs: update CLAUDE.md and copilot-instructions.md for Clerk auth architecture`

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
- Removed (PHP): `laravel/fortify`, `lab404/laravel-impersonate`
- Kept: `laravel/sanctum` (removed from API middleware in Phase 7; may be kept
  for future use or fully removed)
