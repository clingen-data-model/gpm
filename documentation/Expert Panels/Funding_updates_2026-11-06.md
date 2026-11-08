# Funding updates — November 6, 2026

Implemented without committing changes or running migrations against the local application database.

## Behavior

- Users who cannot manage funding see the existing GPM support message. Users with either admin role or the explicit funding permission can manage awards and are not directed to support. The existing canManage logic controls both behaviors.
- Each representative contact can select zero or more of its award's funding sources using `SearchSelect`, keyed by UUID. Editing restores associations. Removing an award source removes it from contacts in the UI and on the backend, including source-only partial updates.
- The backend stores canonical source names from the selected source records and strips IDs/extra source properties. Missing contact associations become `[]`. `publish` is a JSON boolean. The API enforces the existing UI limit of three contacts.
- Sources have nullable `city` and `country_id`. The UI reuses `countries/getItems` and `countries/items`. Source responses include the country relationship; DX uses its name, or null. Deleting a country sets country_id to null.
- The display name is **Manage Funding** in config, exported frontend config, through the existing config export workflow. `funding-sources-manage` remains unchanged. The existing permission seeder already updates records from config.
- Multiple source and PI relationships, primary PI handling, agreement handling, and event/checkpoint schema entry points remain intact.

## Stored representative-contact JSON

`rep_contacts` is an array with at most three contacts. Text fields are trimmed strings or null. Empty contacts are discarded as before. Each contact has exactly these keys after saving:

```json
[
  {
    "role": "Program Officer",
    "name": "Jane Smith",
    "email": "jane@example.org",
    "phone": null,
    "publish": true,
    "funding_sources": [
      {
        "uuid": "205832e6-c156-44b7-b20c-ec5e242cdcc8",
        "name": "NIH"
      }
    ]
  }
]
```

## Example award DX payload

```json
{
  "uuid": "b3bb12ed-4640-4a46-b3c1-948f41c20ec7",
  "funding_sources": [
    {
      "uuid": "205832e6-c156-44b7-b20c-ec5e242cdcc8",
      "name": "NIH",
      "city": "Bethesda",
      "country": "United States",
      "funding_type": "Government",
      "caption": null,
      "website_url": "https://www.nih.gov",
      "logo_path": null
    }
  ],
  "award_number": "EXAMPLE-001",
  "start_date": "2026-01-01",
  "end_date": null,
  "award_url": null,
  "funding_source_division": null,
  "rep_contacts": [
    {
      "role": "Program Officer",
      "name": "Jane Smith",
      "email": "jane@example.org",
      "phone": null,
      "publish": true,
      "funding_sources": [
        {
          "uuid": "205832e6-c156-44b7-b20c-ec5e242cdcc8",
          "name": "NIH"
        }
      ]
    }
  ],
  "notes": null,
  "contact_pis": []
}
```

Standalone source DX messages use the same source object shown above.

## Migrations

The only new November migration is `database/migrations/2026_11_06_000001_add_location_to_funding_sources.php`: nullable city and country foreign key with `nullOnDelete()`.

Removed the proposed legacy-column preservation and permission-rename migrations.

Updated `database/migrations/2026_09_01_133117_add_funding_award_funding_source_pivot_and_remove_funding_source_id.php` to copy legacy associations into the pivot before dropping the old foreign key, index, and column. The filename remains unchanged for migration tracking. Its rollback restores the old nullable column and removes the pivot, as before; rolling back cannot represent multiple sources in the old schema.

`funding_awards.funding_source_id` is absent from the intended final schema. No runtime application code depends on it. Remaining singular references are the pivot foreign key, historical create/backfill/drop/rollback migrations, migration tests, and historical documentation. The plural API input `funding_source_ids` is the array used to sync the pivot.

An environment that already applied the original September migration already has the intended schema and will not rerun that migration. An environment that has not applied it receives the backfill. No interim preservation migration was applied during this work. If someone independently applied that interim version, its migration history/schema requires reconciliation before deployment; editing an applied migration does not rerun it or recover previously dropped legacy data.

## Permission deployment workflow

The display name comes from `config/system.php`, under `permissions.funding-sources-manage`. The identifier stays unchanged. `RolesAndPermissionsSeeder` updates database permissions from that config and clears the permission cache. `resources/js/configs.json` is generated, not an independent source.

Run the existing commands in the deployment environment:

```sh
php artisan config:cache
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan config:export
```

Run config export before the production frontend build so the bundle includes the updated label. The exporter writes the whole JSON file; do not export only `system`, which would remove other sections. Locally, export was run with an isolated config-cache path and the existing exported mail address to avoid unrelated environment-specific changes. No permission seeder or migration was run against the local application database.

## Existing differences and decisions

- Existing contact normalization converted `publish` to a string; it now stores a boolean.
- The three-contact maximum previously existed only in the UI; backend validation now enforces it too.
- Countries currently have names but no maintained country-code field. Institution API responses already expose country names, while institution DX embeds the country model. Funding DX uses the existing country name to avoid exporting environment-specific numeric IDs or introducing codes.
- Fixed the missing `FundingAward` import in `FundingSource::fundingAwards()`.

## Files changed

In addition to the September migration, the new November location migration, and this report:

- `app/Modules/ExpertPanel/Actions/FundingAwardUpsert.php`
- `app/Modules/ExpertPanel/Http/Controllers/Api/FundingAwardController.php`
- `app/Modules/ExpertPanel/Service/FundingAwardSchema.php`
- `app/Modules/Funding/Actions/FundingSourceCreate.php`
- `app/Modules/Funding/Actions/FundingSourceUpdate.php`
- `app/Modules/Funding/Http/Controllers/Api/FundingSourceController.php`
- `app/Modules/Funding/Models/FundingSource.php`
- `app/Modules/Funding/Support/FundingSourceSchema.php`
- `config/system.php`
- `resources/js/configs.json`
- `resources/js/components/expert_panels/FundingAwardsTab.vue`
- `resources/js/views/FundingSourceList.vue`
- `tests/Feature/FundingAwardsTest.php` (new)
- `tests/Feature/FundingMigrationTest.php` (new)
- `documentation/Expert Panels/CGSP-771_funding_sources_funding_awards_tech_doc.md` (historical-document notice)
- `resources/js/tests/unit/funding.spec.js` (new)

## Validation

- Backend: **5 tests, 37 assertions passed**. Covers source location create/update/clear/validation, contact validation, multiple associations, UUID-only persistence, canonical names, boolean publish, old contacts, partial updates, award-list responses, DX/event dispatch, country deletion, and migration backfill/drop/rollback.
- Frontend: **5 tests passed**. Covers support messaging for non-managers and its absence for authorized managers, restoring contacts, source options and pruning, contact limits/removal, UUID-only payloads, and country/city form submission/clearing.
- Production frontend build passed; Vite reported its large-chunk warning.
- `git diff --check` passed.

Commands used:

```powershell
$env:APP_CONFIG_CACHE = 'bootstrap/cache/config-testing-unused.php'
php vendor/phpunit/phpunit/phpunit tests/Feature/FundingAwardsTest.php tests/Feature/FundingMigrationTest.php --do-not-cache-result
npm.cmd run test:unit -- --run resources/js/tests/unit/funding.spec.js
npm.cmd run build -- --outDir .tmp-funding-build
```

The isolated config path bypasses the existing local cached configuration so PHPUnit uses its SQLite in-memory database. Temporary build and PHPUnit cache artifacts were removed after validation. Apply the added migrations and refresh cached config through the normal deployment process.
