# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

ClinGen Group & Personnel Management (GPM) — a Laravel 11 + Vue 3 SPA for managing ClinGen Expert Panel applications, people, groups, and approval workflows. Backend runs in Docker with PHP 8.2+, MySQL 8.0, Redis, and Nginx. Frontend is built with Vite.

## Common Commands

All PHP/artisan commands run inside the Docker container. On systems where docker isn't installed, try podman.

```bash
# Start services
docker compose up -d

# Run tests (SQLite :memory: — see phpunit.xml)
docker compose exec -it app php artisan test
./vendor/bin/phpunit --testsuite=Unit
./vendor/bin/phpunit --testsuite=Feature
./vendor/bin/phpunit --testsuite=Integration

# Migrations
docker compose exec -it app php artisan migrate

# Generate an Action class
docker compose exec -it app php artisan make:action Namespace/ActionName

# Export Laravel config → JSON for the Vue frontend
docker compose exec -it app php artisan config:export

# Frontend builds (Vite runs automatically in container, but manually:)
docker compose exec -it app npm run dev
docker compose exec -it app npm run build
docker compose exec -it app npm run lint
docker compose exec -it app npm run lintfix

# Composer (when dependencies change)
docker compose run --no-deps --rm -it --entrypoint composer app install --no-interaction --no-plugins --no-scripts --prefer-dist
docker compose run --no-deps --rm -it --entrypoint composer app dump-autoload

# Reset database from a dump (place .sql/.sql.gz in .docker/mysql/db-init/)
docker volume rm gpm_db && docker compose up -d db
```

App is accessible at `http://localhost:8013` (or `APP_PORT` from `.env`).

## Architecture

### Modular Backend (`app/Modules/`)

Code is divided into domain modules, each self-contained:
- `ExpertPanel/` — panel applications, COI, approval workflows, next actions
- `Group/` — working groups, members, submissions, types/statuses
- `Person/` — people, institutions, demographics
- `User/` — accounts, authentication
- `Foundation/` — shared module utilities

Each module has: `Actions/`, `Models/`, `Http/`, `Events/`, `Providers/`, `routes/`

Module API routes are registered by each module's `ServiceProvider`, not automatically discovered.

### Actions Pattern (`lorisleiva/laravel-actions`)

**Prefer Actions over Controllers.** Business logic lives in Action classes (see `app/Actions/` and `app/Modules/*/Actions/`). Actions can serve as HTTP controllers, queued jobs, or event listeners:

```php
class CommentCreate {
    use AsController;

    public function handle(array $data) { /* core logic */ }
    public function asController(ActionRequest $request) { /* HTTP layer */ }
    public function rules(ActionRequest $request): array { /* validation */ }
    public function authorize(ActionRequest $request): bool { /* permissions */ }
}
```

### Authentication (Clerk)

GPM uses **Clerk.com** as its external IdP. All API authentication is stateless Bearer token — no session cookies.

**Flow**: The `@clerk/vue` plugin initialises on the frontend. An Axios request interceptor (`resources/js/http/api.js`) attaches `Authorization: Bearer <token>` to every API call. The backend validates tokens in `App\Http\Middleware\VerifyClerkToken` (`auth.clerk` middleware alias), which:
1. Returns 401 if no Bearer token is present.
2. Verifies the JWT using the Clerk JWKS endpoint (via `clerkinc/backend-php`).
3. Looks up the local `User` by `clerk_user_id` where `active = true`; returns 403 if not found or inactive.
4. Swaps `Auth::user()` to an impersonation target if an `active_impersonations` row exists.

**Local User model fields** (`users` table):
- `clerk_user_id` — stable Clerk identity; set once on provisioning, never cleared on removal.
- `active` — per-app access gate (401 = no valid JWT, 403 = valid JWT but not active here).

**Provisioning** (admin-led only, no auto-creation from webhooks):
- `UserCreate` action calls `ClerkApiService::findOrCreateByEmail()` to look up or create the Clerk account, then stores `clerk_user_id`.
- `clerk:import-users` artisan command migrates existing users (with bcrypt hash transfer).

**Impersonation** tracked via `active_impersonations` table (not session-based). Start/stop via `ImpersonateStart` / `ImpersonateStop` actions at `POST/DELETE /api/impersonate`. During impersonation `request->attributes->get('impersonating_user')` holds the real admin.

**Webhooks**: `POST /api/webhooks/clerk` (Svix-verified) syncs `user.updated` and sets `active = false` on `user.deleted`. `user.created` is intentionally ignored.

**Key env vars**: `CLERK_SECRET_KEY`, `CLERK_PUBLISHABLE_KEY`, `CLERK_AUTHORIZED_PARTIES`, `CLERK_WEBHOOK_SECRET`, `VITE_CLERK_PUBLISHABLE_KEY`.

### Authorization

Uses `spatie/laravel-permission`. Check permissions in the Action's `authorize()` method:
```php
return $request->user()->hasPermissionTo('ep-applications-comment');
```

### Event Recording & Activity Log

Models with `RecordsEvents` trait publish domain events → logged to `activity_log` table (Spatie Activitylog). Events implementing `PublishableEvent` are also forwarded to Kafka (Data Exchange).

### Data Exchange (DX) / Kafka

GPM publishes to topics `gpm-general-events` and `gpm-person-events`, and consumes from ClinGen registry. Config: `config/dx.php`. See `documentation/dx-implementation.md` for full details.

Consuming pipeline: `KafkaMessageStream` → `IncomingMessageProcessor` → `MessageHandlerFactory` (runs hourly via scheduler).

### UUIDs

Most models use the `HasUuid` trait with an indexed `uuid` column for external references.

### Polymorphic Relationships

Extensively used for comments, documents, notes, and log entries (e.g., `subject_type` / `subject_id` on `Comment`).

### Frontend (`resources/js/`)

- **Router**: `resources/js/router/` — split by domain (applications, people, groups, etc.)
- **Views**: `resources/js/views/` — one component per route
- **Components**: `resources/js/components/` — reusable UI by feature
- **Store (Vuex 4)**: `resources/js/store/` — modules using factory pattern
- **HTTP**: `resources/js/http/api.js` — configured Axios instance with Clerk Bearer token interceptor
- **Composables**: `resources/js/composables/`

### Routes

- `routes/web.php` — SPA catch-all + report routes
- `routes/api.php` — top-level API; module routes loaded by each module's ServiceProvider

## Key Pitfalls

1. **Docker user permissions**: Set `DOCKER_USER=$(id -u):$(id -g)` in `.env` to avoid bind-mount permission issues.
2. **Tests use SQLite `:memory:`**, not MySQL — check `phpunit.xml` for environment overrides.
3. **Route caching**: Run `php artisan route:clear` if route changes aren't reflected.
4. **`php artisan config:export`** must be run when Laravel config values consumed by the Vue frontend change.

## Feature Flags

Controlled via `.env` with `FEATURE_*` prefix (e.g., `FEATURE_CSPEC_SUMMARY`, `FEATURE_NOTIFY_SCOPE_CHANGE`, `FEATURE_SPECS_UPLOAD`, `FEATURE_CHAIR_REVIEW`).

## Further Documentation

- `README.md`, `macos-setup.md` — local setup
- `documentation/approval_workflow_walkthrough.md` — approval workflow
- `documentation/dx-implementation.md` — Kafka/DX integration
- `.github/copilot-instructions.md` — additional conventions and patterns
