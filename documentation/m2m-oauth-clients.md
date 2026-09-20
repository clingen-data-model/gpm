# Machine-to-machine access: OAuth clients

Other systems call GPM with OAuth 2.0 client credentials (Laravel Passport).
This is the same pattern GPM itself uses to call GeneTracker
(`app/Services/Api/AccessTokenManager.php`). The SPA is unaffected: it keeps
its Sanctum cookie session.

## For the caller

You receive a **client id** and a **client secret**. Keep the secret out of
source control. Exchange them for a short-lived access token, then send that
token as a bearer token.

```http
POST /oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=client_credentials
client_id=<CLIENT_ID>
client_secret=<CLIENT_SECRET>
scope=reports:read
```

```bash
curl -X POST https://gpm.clinicalgenome.org/oauth/token \
  -d grant_type=client_credentials \
  -d client_id=YOUR_CLIENT_ID \
  -d client_secret=YOUR_CLIENT_SECRET \
  -d scope=reports:read
```

```json
{ "token_type": "Bearer", "expires_in": 600, "access_token": "..." }
```

Tokens expire after 10 minutes; cache the token and request a new one when
`expires_in` runs out. Then:

```bash
curl https://gpm.clinicalgenome.org/api/report/people \
  -H "Authorization: Bearer ACCESS_TOKEN" -H "Accept: application/json"
```

| Status | Meaning |
|---|---|
| 400 at `/oauth/token` | unknown grant type or a scope the client may not request |
| 401 at `/oauth/token` | wrong client id/secret, or the client was revoked |
| 401 on an API route | missing, expired, revoked or malformed token |
| 403 on an API route | valid token without the scope that route requires |
| 429 | more than 20 token requests per minute from one address |

## Scopes and routes

Scopes are declared in `App\Providers\OAuthServiceProvider::SCOPES`
(`reports:read`, `people:read`, `groups:read`). A route accepts machine
callers when it carries the `auth.session-or-client:<scope>[,<scope>]`
middleware (`app/Http/Middleware/AuthenticateSessionOrClient.php`): a signed-in
session passes, anything else must present a client token with every listed
scope. Today only `/api/report/*` (`reports:read`) is open to machines. To open
another route, add its scope to the provider and the middleware to the route.

Client-credentials tokens carry no user, so `$request->user()` is null on
these requests and nothing is recorded as the activity-log causer. Keep
machine routes read-only, or add a causer resolver before exposing writes.

## Operating clients

```bash
php artisan passport:client --client --name="GeneTracker"   # prints id + secret once
php artisan oauth-client:list [--all]
php artisan oauth-client:revoke <client-id>                 # also revokes outstanding tokens
```

Secrets are stored hashed; a lost secret means creating a new client. Only
`POST /oauth/token` is registered (`Passport::ignoreRoutes()` in
`OAuthServiceProvider`); Passport's authorize, device and token-management
routes do not exist here.

## Keys

Tokens are RS256 JWTs signed with an RSA key pair.

- **Local development:** `php artisan passport:keys` writes
  `storage/oauth-private.key` and `storage/oauth-public.key` (gitignored).
- **Deployed environments:** put the PEM contents in the
  `PASSPORT_PRIVATE_KEY` and `PASSPORT_PUBLIC_KEY` secrets; `config/passport.php`
  reads them. Rotating the pair invalidates outstanding tokens, which callers
  simply re-request.

## Database

`database/migrations/2026_09_20_000001_create_passport_tables.php` creates the
Passport 13 tables. Never run `passport:install` or publish Passport's own
migrations here: they share names with copies this app deleted years ago and
would be skipped as already run on every existing database.

## Tests

`tests/TestCase.php::usePassportTestKeys()` gives every test a throwaway key
pair, so tests issue real tokens through `POST /oauth/token` (see
`tests/Feature/End2End/Auth/OAuthClientCredentialsTest.php`). Note that
`ClientRepository::find()` is memoized per process with `once()`; after
revoking a client inside a test call `Illuminate\Support\Once::flush()`.
