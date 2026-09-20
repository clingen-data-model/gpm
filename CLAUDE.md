# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

ClinGen Group & Personnel Management (GPM): a Laravel 13 / PHP 8.2+ API backend plus a Vue 3 single-page
app, used to manage ClinGen expert panels, their members, and the expert-panel application/approval workflow.
MySQL is the store, Redis the cache/session/queue. See `README.md` for the domain-level architecture write-up
and `documentation/` for design notes (`People vs Users.md`, `dx-implementation.md`,
`genetracker-integration.md`, `submission-notifications.md`, `future-tasks.md`, `running-tests.md`).

## Development environment

Everything runs in Docker Compose (`docker compose up -d`); the app is served at `http://localhost:8013`,
Mailpit at `http://localhost:8038`, MySQL exposed on host port 3319. The repo is bind-mounted at `/srv/app`
inside the containers. `scripts/entrypoint.sh` runs `composer install`, migrations, and clears config caches on
container start (dev mode when `PRODUCTION_MODE=false`).

Run PHP commands **inside the `app` container**, not on the host (host PHP is 8.4 and does not match the image):

```bash
docker compose exec -T app php artisan <command>
docker compose exec -T app composer <command>
```

`.env` holds secrets (DB, Redis, Clerk, DX credentials). Never print or commit it; `.env.example` shows the shape.

## Commands

```bash
# Backend tests (PHPUnit 12, sqlite :memory:, RefreshDatabase). Run config:clear first if tests are glacial.
docker compose exec -T app php artisan test
docker compose exec -T app php artisan test tests/Feature/End2End/Groups/JudgementDeleteTest.php
docker compose exec -T app php artisan test --filter=test_method_name
docker compose exec -T app vendor/bin/phpunit -c phpunit.xml --filter SomeTest

# Static analysis (PHP)
docker compose exec -T app vendor/bin/phpmd app text phpmd.xml

# Frontend (run on host; node/npm available)
npm run dev          # vite dev server (proxied through the 8013 backend)
npm run build        # regenerates public/build/ (which IS committed, see below)
npm run lint         # eslint (antfu config) over resources/js
npm run lintfix
npx vitest run       # unit tests; several spec files are known-broken since the vite migration
npx vitest run resources/js/tests/unit/domain/group.spec.js

# Sync backend config to the frontend after changing config/*.php values the SPA reads
docker compose exec -T app php artisan config:export   # writes resources/js/configs.json

# Scaffolding
docker compose exec -T app php artisan make:action Name --as-controller   # also --as-command, --as-listener=
docker compose exec -T app php artisan make:module ModuleName
docker compose exec -T app php artisan make:user-token user@example.com  # sanctum token for a human user (API testing)
docker compose exec -T app php artisan passport:keys                      # once per dev environment (OAuth signing keys)
docker compose exec -T app php artisan passport:client --client --name=X  # OAuth client for a machine caller
docker compose exec -T app php artisan idp:import-users --all --dry-run  # import local users into the IdP
```

Notes on tests: `tests/TestCase.php` seeds `GroupTypeSeeder` for every test and adds an
`assertValidationErrors` macro. A handful of pre-existing failures are environmental (Genetracker DB,
DX/Kafka); establish a baseline before attributing failures to your change. The PDO `MYSQL_ATTR_SSL_CA`
deprecation notices are noise.

## Backend architecture

**Modules.** Domain code lives in `app/Modules/{ExpertPanel,Group,Person,User,Funding}` and `app/Tasks`,
each with `Actions/`, `Events/`, `Models/`, `Http/`, `routes/`, `Providers/`, and often a module config
file (e.g. `app/Modules/Group/groups.php`, merged as `config('groups')`). Each module's provider extends
`App\Modules\Foundation\ModuleServiceProvider`, which on `register()`:

- scans the module's `Events/` directory (via `ClassGetter`, by file, so **every class in `Events/` is
  auto-discovered**) and wires listeners for `RecordableEvent`s (activity log), `PublishableEvent`s
  (Data Exchange), and `FollowAction`s;
- registers the explicit `$listeners` map (`Event::class => [Listener::class, ...]`) and `$policies`;
- loads every file in the module's `routes/` directory and registers actions as artisan commands.

Cross-module listeners go in `app/Providers/EventServiceProvider.php`; intra-module listeners go in the
module's provider. Providers are listed in `config/app.php` (no `bootstrap/providers.php`).

**Actions (lorisleiva/laravel-actions).** Nearly all write endpoints are action classes, not controllers.
Naming is **NounVerb** (`MemberRetire`, `GroupCreate`, `ApplicationSubmitStep`). A typical action has
`handle()` for the domain logic, `asController(ActionRequest $request, ...)` to adapt HTTP, `rules()` for
validation, and dispatches a domain event at the end. Actions are composed via constructor injection and
invoked from code with `Action::run(...)`. Routes bind them directly: `Route::post('/x', SomeAction::class)`.
Shared/cross-cutting actions live in `app/Actions` (comments, reports, reminders, mail).

**Events.** Domain events extend `App\Events\RecordableEvent` (abstract methods describe the activity-log
entry; `activity_type` defaults to the kebab-cased class name) and optionally implement `PublishableEvent`
to be pushed to the ClinGen Data Exchange (Kafka; see `app/DataExchange`, `config/dx.php`). Dispatching an
event is the mechanism that records history and triggers notifications, so new mutations should emit one.

**FollowActions** are persisted, deferred listeners (`App\Models\FollowAction`) run by `FollowActionRun` when
a matching event fires later; see README for the pattern.

**Auth.** Sanctum SPA cookie auth (`auth:sanctum` on API routes, `EnsureFrontendRequestsAreStateful` in the
`api` middleware group), Fortify for local login/password flows, `lab404/laravel-impersonate` for impersonation
(take/leave are activity-logged). An external identity provider (Clerk) is supported through a **session
bridge**: the SPA signs in with the IdP and posts the token once to `POST /api/idp/session-login`, which
verifies it and starts a normal session. Contracts and drivers (`clerk`, `fake`, `null`, chosen by
`IDP_DRIVER`) live in `app/Services/Idp`; the link is `users.idp_provider` + `users.idp_id`. Tests and
offline dev use the `fake` driver (`/dev/idp/*` endpoints, login-page picker). Machine callers use
OAuth client credentials (Passport, `POST /oauth/token`, 10-minute scoped tokens); routes open to them carry
`auth.session-or-client:<scope>`, which also passes a normal session. Never run `passport:install` here
(see `documentation/m2m-oauth-clients.md`). See `documentation/idp-clerk.md` for the IdP design.
Roles/permissions are spatie/laravel-permission v8 with a group-scoped extension in
`app/Models/Traits/HasRoles.php` (permissions can be scoped to a group). Permission checks in the SPA use
`resources/js/auth_utils.js`. `Person` (a ClinGen member, soft-deletable) and `User` (a login, hard-deleted)
are distinct models; people may exist without users.

**Routing.** `routes/api.php` is prefixed `/api`; module `routes/api.php` files are loaded by their providers.
`routes/web.php` serves the SPA catch-all plus report/download endpoints (under `/api/report` but in the
`web` group). `routes/dev.php` is prefixed `/dev`.

**Scheduler / queue.** `app/Console/Kernel.php` schedules reminder jobs, digest notifications, and DX
consumption. Production only runs the scheduler at :00 and :10 past the hour, so schedule new tasks at
those minutes. The `queue` and `scheduler` compose services run the same image with different entrypoints.

**External integrations.** GeneTracker is reached both by a second DB connection (`GT_DB_*`, `gt_sqlite`
in tests) and, increasingly, by an OAuth client-credentials API client in `app/Services/Api/GtApiService`.
Data Exchange (Kafka) publishing is driven by `PublishableEvent`s; `DX_DRIVER=log` in dev.

## Frontend architecture

Source is in `resources/js` (the README's references to `resources/app` and vue-cli are stale; the build is
Vite via `laravel-vite-plugin`, entry `resources/js/app.js`, alias `@` → `resources/js`). Vue 3 with a mix of
Options API and `<script setup>`, vue-router (`resources/js/router`), Vuex (`resources/js/store`, most
entity modules generated by `store/module_factory.js`), Tailwind 3 with lucide icons. Domain entity classes
live in `resources/js/domain`. HTTP goes through the axios instance in `resources/js/http/api.js`
(`withCredentials`, relative base URL). Many global components are registered app-wide; `eslint.config.js`
lists them under `vue/no-undef-components`.

Backend config (group types, EP types, next actions, feature flags, document types) is exported to
`resources/js/configs.json` by `php artisan config:export`; keep it in sync when those configs change.
IdP runtime settings (driver, Clerk publishable key) reach the SPA via `window.__GPM_IDP__`, injected by
`ViewController`; `resources/js/idp/` wraps `@clerk/vue` and the fake driver behind `useIdp()`.

**`public/build/` is committed.** Run `npm run build` when frontend changes need to ship, and expect large
generated diffs; keep them in their own commit.

## Conventions

- One concern per commit.
- Deferred follow-ups and deployment-time steps are tracked in `documentation/future-tasks.md` and
  `technical-debt-deployment-todos-2025-02.md`.
- Feature flags are `FEATURE_*` env vars surfaced via `config/system.php` / `appFeatures` in `configs.json`.
