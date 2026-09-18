# ClinGen People API — Client Guide

The GPM ClinGen People API provides current GPM person, group membership, and role information.

GPM is the authoritative source for the ClinGen Person UUID.

## Authentication

The API uses OAuth 2.0 Client Credentials.

Each consuming ClinGen application receives:

- Client ID
- Client Secret

Keep the client secret private and do not commit it to source control.

Request an access token:

```http
POST /oauth/token
Content-Type: application/x-www-form-urlencoded
```

Parameters:

```text
grant_type=client_credentials
client_id=<CLIENT_ID>
client_secret=<CLIENT_SECRET>
scope=clingen-people-read
```

Example:

```bash
curl -X POST https://gpm.clingen.org/oauth/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=client_credentials" \
  -d "client_id=YOUR_CLIENT_ID" \
  -d "client_secret=YOUR_CLIENT_SECRET" \
  -d "scope=clingen-people-read"
```

Example response:

```json
{
  "token_type": "Bearer",
  "expires_in": 180,
  "access_token": "..."
}
```

Access tokens expire after 3 minutes. When a token expires, request a new one using the same client credentials.

## Retrieve a Person

```http
GET /api/clingen/v1/people/{uuid}
```

`uuid` is the ClinGen Person UUID stored in Clerk `external_id`.

Example:

```bash
curl https://gpm.clingen.org/api/clingen/v1/people/PERSON_UUID \
  -H "Authorization: Bearer ACCESS_TOKEN" \
  -H "Accept: application/json"
```

## Response

Example response:

```json
{
  "data": {
    "uuid": "...",
    "first_name": "Alex",
    "last_name": "Smith",
    "email": "alex@example.org",
    "institution": "Example University",
    "credentials": [
      "PhD"
    ],
    "memberships": [
      {
        "status": "active",
        "start_date": "2025-01-01",
        "end_date": null,
        "group": {
          "uuid": "...",
          "name": "Example GCEP",
          "type": "gcep",
          "affiliation_id": "40001"
        },
        "roles": [
          "coordinator"
        ]
      }
    ]
  }
}
```

Both active and retired memberships may be returned. Clients should use `status`, `start_date`, and `end_date` to determine how each membership should be interpreted.

Permissions are not included in this API.

## Common Errors

- `401 Unauthorized` — missing, invalid, expired, or revoked access token
- `403 Forbidden` — token does not have the `clingen-people-read` scope
- `404 Not Found` — Person UUID does not exist in GPM
