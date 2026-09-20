# Future tasks

Small follow-up items intentionally deferred during other work.

* **Refactor object-based cache sites to arrays** (deferred during the Laravel 13 upgrade, 2026-09). `config/cache.php` opts in to Laravel 13's `cache.serializable_classes` allow-list, which restricts what may be unserialized from the cache. Two sites cache Eloquent objects and were allow-listed instead of refactored, since converting them risks breaking downstream code that expects model instances:
  * `app/Modules/User/Models/User.php` `getAllPermissions()` — caches a `Collection` of `App\Models\Permission` models.
  * `app/Modules/ExpertPanel/Http/Controllers/Api/NextActionAssigneeController.php` — caches `NextActionAssignee::all()`.

  These are currently allow-listed in `config/cache.php`. Note that the allow-list has to name every class reachable from a cached value — the permissions cache also drags in `App\Models\Role`, the `User` model and the `Pivot`/`MorphPivot` instances attached to each permission — and a class left out comes back as `__PHP_Incomplete_Class` with no error, so the omission surfaces as silently missing data. The test suite runs on the `array` cache driver, which does not serialize, so it cannot catch a gap here.

  Converting these sites to cache plain arrays instead (and adapting the downstream consumers) would fully align with the hardening's intent and remove the allow-list entries along with that footgun.

* **Password changes made inside Clerk do not reach GPM** (Clerk IdP work, 2026-09). Local password
  changes are pushed to Clerk, but a password changed through Clerk's own account UI leaves the local
  hash untouched, so the local password form keeps accepting the old password for linked users. Options:
  hide the local password form for linked users once the cutover has settled, or add a Clerk webhook
  endpoint (`user.updated`/`user.deleted`, svix-signed) and sync from it. Name/email are already
  refreshed from Clerk at each session login.

* **Extend OAuth scopes beyond reports** (2026-09). Only `/api/report/*` carries the
  `auth.session-or-client` middleware. When other systems need machine access (e.g. people or groups read
  endpoints), add the scope to `App\Providers\OAuthServiceProvider::SCOPES` and the middleware to those
  routes. If a machine-callable route ever writes, add an activity-log causer for OAuth clients first:
  client-credentials tokens carry no user, so `RecordEvent` records none today.
