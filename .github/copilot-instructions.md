# GPM (ClinGen Group & Personnel Management) - AI Agent Guide

## Project Overview
Laravel 11 + Vue 3 application for managing ClinGen Expert Panel applications, people, groups, and workflow approvals. Backend runs in Docker with PHP 8.2+, MySQL 8.0, Redis, and Nginx. Frontend uses Vite for builds.

## Architecture

### Modular Structure
Code is organized into **Modules** under `app/Modules/`, each with its own namespace and structure:
- `ExpertPanel/` - Application submissions, COI, approval workflows, next actions
- `Group/` - Working groups, members, submissions, group types/statuses
- `Person/` - People, institutions, countries, demographics
- `User/` - User accounts and authentication
- `Foundation/` - Shared module utilities

Each module contains: `Actions/`, `Models/`, `Http/`, `Events/`, `Providers/`, `routes/`

### Actions Pattern (lorisleiva/laravel-actions)
Primary business logic lives in **Action classes**, not controllers. Actions use `AsController` trait:
```php
class CommentCreate {
    use AsController;
    
    public function handle(array $data) { /* core logic */ }
    public function asController(ActionRequest $request) { /* HTTP layer */ }
    public function rules(ActionRequest $request): array { /* validation */ }
    public function authorize(ActionRequest $request): bool { /* permissions */ }
}
```
See `app/Actions/` for app-level actions and `app/Modules/*/Actions/` for module-specific ones.

### Frontend Architecture
- Vue 3 with Vue Router 4 and Vuex 4
- Routes defined in `resources/js/router/` (split by domain: applications, people, groups, etc.)
- SPA with API backend at `/api/`
- Vite for builds (config: `vite.config.js`)
- Component structure: `resources/js/components/`, views: `resources/js/views/`

### Data Exchange (DX) Integration
GPM publishes to and consumes from Kafka topics for ClinGen data exchange:
- **Config**: `config/dx.php` defines topics, schema versions, brokers
- **Publishing**: Domain events implement `PublishableEvent`, processed by `DxMessageFactory`
  - Topics: `gpm-general-events` (ExpertPanel/Group events), `gpm-person-events`
- **Consuming**: Scheduled hourly via `App\DataExchange\Actions\DxConsume`
  - Pipeline: `KafkaMessageStream` → `IncomingMessageProcessor` → `MessageHandlerFactory`
  - All messages persisted as `StreamMessage` model for traceability
- See `documentation/dx-implementation.md` for complete details

## Development Workflow

### Running the Application
```bash
# Start all services (db, redis, app, nginx)
docker compose up -d

# Watch logs
docker compose logs -f

# Access at http://localhost:8013 (or APP_PORT from .env)
```

### Database Operations
```bash
# Fresh database from dump
docker volume rm gpm_db
# Place *.sql or *.sql.gz in .docker/mysql/db-init/
docker compose up -d db

# Migrations
docker compose exec -it app php artisan migrate
```

### PHP Commands (in Container)
```bash
# Run artisan commands
docker compose exec -it app php artisan [command]

# Composer (if dependencies change)
docker compose run --no-deps --rm -it --entrypoint composer app install --no-interaction --no-plugins --no-scripts --prefer-dist
docker compose run --no-deps --rm -it --entrypoint composer app dump-autoload
```

### Frontend Development
Vite runs automatically in the app container. For manual builds:
```bash
docker compose exec -it app npm run dev   # development
docker compose exec -it app npm run build # production
```

### Testing
Tests use SQLite in-memory databases (configured in `phpunit.xml`):
```bash
# Run tests from host or container
docker compose exec -it app php artisan test

# Specific suites
./vendor/bin/phpunit --testsuite=Unit
./vendor/bin/phpunit --testsuite=Feature
./vendor/bin/phpunit --testsuite=Integration
```

## Key Conventions

### UUIDs as Primary Identifiers
Models use UUIDs for external references. Most models include `HasUuid` trait and have `uuid` column indexed.

### Authentication (Clerk)
GPM uses **Clerk.com** as its IdP. Authentication is stateless Bearer tokens — no session cookies or Sanctum.

**Middleware**: `auth.clerk` alias → `App\Http\Middleware\VerifyClerkToken`.
- Returns 401 if no Bearer token; 403 if valid JWT but user not provisioned/active in GPM.
- `users.clerk_user_id` is the stable link between Clerk identity and local User records.
- `users.active = false` blocks access (403) without deleting the Clerk identity.

**Frontend**: `@clerk/vue` plugin populates `window.Clerk`; Axios interceptor in `resources/js/http/api.js` attaches `Authorization: Bearer` on every request.

**Provisioning**: Admin-led via `UserCreate` action (calls `ClerkApiService::findOrCreateByEmail()`). No auto-creation on `user.created` webhook.

**Impersonation**: DB-tracked in `active_impersonations` table. Use `ImpersonateStart` / `ImpersonateStop` actions. Real admin is in `request->attributes->get('impersonating_user')` during impersonation.

**Webhooks**: `POST /api/webhooks/clerk` (no auth, Svix-verified) — syncs name/email on `user.updated`, sets `active=false` on `user.deleted`.

### Permissions & Authorization
Uses Spatie Laravel Permission. Check permissions in Actions' `authorize()` method:
```php
return $request->user()->hasPermissionTo('ep-applications-comment');
```

### Event Recording
Models implementing `RecordsEvents` (via trait) publish domain events. Events are logged to `activity_log` table (Spatie Activitylog) and can be published to DX topics if they implement `PublishableEvent`.

### Polymorphic Relationships
Extensively used for comments, documents, notes, log entries:
```php
// e.g., Comment::class
'subject_type' => 'App\\Modules\\Group\\Models\\Group',
'subject_id' => 123
```

### Routes Structure
- Web routes: `routes/web.php` - SPA catchall + reports
- API routes: Module-specific in `app/Modules/*/routes/api.php`
- Route registration: Each module's `ServiceProvider` loads its routes

### Helper Functions
Global helpers in `app/helpers.php`: `renderQuery()`, `carbonToString()`, `implementsInterface()`, etc.

## Common Pitfalls

1. **Docker User Permissions**: Set `DOCKER_USER=$(id -u):$(id -g)` in `.env` or shell profile to avoid permission issues with bind mounts
2. **DB Connection in Tests**: Tests use `:memory:` SQLite, not MySQL. Check `phpunit.xml` for test environment config
3. **Route Caching**: In production mode, routes are cached. Run `php artisan route:clear` if routes don't update
4. **Module Routes**: API routes aren't automatically loaded - they're registered in each module's ServiceProvider
5. **Action vs Controller**: Prefer creating Actions over Controllers. Controllers mainly exist for legacy code

## Useful Commands
```bash
# Generate Action class
php artisan make:action Namespace/ActionName

# Export frontend configs (Laravel config → JSON for Vue)
php artisan config:export

# Check health
php artisan health:check

# View activity logs
docker compose exec -it app php artisan log:viewer
```

## Documentation
- Approval workflow: `documentation/approval_workflow_walkthrough.md`
- DX integration: `documentation/dx-implementation.md`
- Setup instructions: `README.md`, `macos-setup.md`
