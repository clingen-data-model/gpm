# CGSP-771 — Funding Sources & Funding Awards (GPM)  
Last updated: 2026-05-06

---

## 1) Goal

Provide a systematic way to capture **funding support** for Expert Panels (EPs) in GPM so downstream systems (e.g., the ClinGen website via Data Exchange) can display current and historical funding.

Two main concepts:

- **Funding Source**: a global catalog of organizations/programs that provide funding.
- **Funding Award**: an EP-specific record linking an EP to a Funding Source with award-specific details.

---

## 2) Definitions

### Funding Source
A reusable catalog entry (no duplication across EPs). Example: "American Society of Hematology".

Fields (current):
- `uuid`
- `name`
- `funding_type_id` → `funding_types`
- `caption` (text-only)
- `website_url`
- `logo_path` (optional; filename stored, file stored on disk)

### Funding Award (per Expert Panel)
A record owned by an **Expert Panel** and linked to a **Funding Source**. Used to represent one funding period; renewals are represented by creating a new award record.

Fields (current):
- `uuid`
- `expert_panel_id`
- `funding_source_id`
- `award_number` (optional)
- `start_date`, `end_date` (optional; end_date can be null meaning “Present”)
- `award_url` (optional; previously `nih_reporter_url`)
- `funding_source_division` (optional; previously `nih_ic`)
- `rep_contacts` (JSON; up to 2 contacts, each `{role,name,email,phone}`)
- `notes` (text-only)
- Contact PI(s): many-to-many to `people` via pivot table with `is_primary`

---

## 3) Permissions (current agreed rules)

### Funding Sources
- **View**: super-user or super-admin only + someone with `Manage Funding Sources` permission
- **Add/Edit/Delete**: super-user or super-admin only + someone with `Manage Funding Sources` permission

### Funding Awards
- **View**: all authenticated users (including regular EP members)
- **Add/Edit/Delete**: super-user or super-admin only + someone with `Manage Funding Sources` permission

> UI hides manage controls for non-super roles, but backend authorization is the source of truth.

---

## 4) Data Model

### 4.1 `funding_types`
Lookup table. Seeded values:
- Industry
- Foundation
- Advocacy
- Government

Seeder: `Database\Seeders\FundingTypeSeeder`

### 4.2 `funding_sources`
Recommended structure (id + uuid pattern used across GPM):
- `id` BIGINT PK (auto increment)
- `uuid` UUID (unique; used as public identifier where needed)
- `name` (string)
- `funding_type_id` (FK)
- `caption` (string/text; text-only)
- `website_url` (string)
- `logo_path` (string; stores filename only)
- `created_at`, `updated_at`, `deleted_at`

### 4.3 `funding_awards`
- `id` BIGINT PK
- `uuid` UUID unique
- `expert_panel_id` (FK → expert_panels)
- `funding_source_id` (FK → funding_sources)
- `award_number` (string, nullable)
- `start_date`, `end_date` (date, nullable)
- `award_url` (string, nullable)
- `funding_source_division` (string, nullable)
- `rep_contacts` (JSON nullable)
- `notes` (text nullable)
- timestamps + soft deletes

### 4.4 `funding_award_contact_pis` (pivot)
- `id` BIGINT PK
- `funding_award_id` FK → funding_awards
- `person_id` FK → people
- `is_primary` boolean default false
- unique index `(funding_award_id, person_id)`
- timestamps

---

## 5) Key implementation details

### 5.1 Rep contacts design
`rep_contacts` is stored as JSON and currently limited to 3 contacts.
Implementation should cap to 3 and drop fully-empty contacts. Keep logic centralized so future changes (e.g., 3+) are easier.

---

## 7) Data Exchange (DX) Events

### 7.1 Funding Award events (EP-scoped)

- `funding_award_created`
```
{
    "event_type": "funding_award_created",
    "schema_version": "2.0.1",
    "date": "2026-05-06 19:57:12",
    "data": {
        "uuid": "b6a6...d090",
        "funding_source": {
            "uuid": "bdc5...1b5a",
            "name": "American Society of Hematology",
            "funding_type": "Foundation",
            "caption": "ASH is a...cancer clinical domain.",
            "website_url": "https:\/\/www.hematology.org\/",
            "logo_path": "http:\/\/localhost\/funding-sources\/logo\/IHlF88mcRciLhyht8nomDvQ0OsSqJ3Uef6QIbLfb.jpg"
        },
        "award_number": "AWD 23423",
        "start_date": "2026-03-01",
        "end_date": "2030-10-31",
        "award_url": "https:\/\/www.lipsum.com\/feed\/html",
        "funding_source_division": null,
        "rep_contacts": [
            {
                "name": "Andreas Natulanggan",
                "role": "PIC",
                "email": "and...@....edu",
                "phone": "(854) 214 ..."
            }
        ],
        "notes": "Ut in feugiat magna.... Nulla feugiat placerat quam.",
        "contact_pis": [
            {
                "uuid": "9f89...e863",
                "first_name": "Andrew",
                "last_name": "Dubowsky",
                "email": "and...@....au",
                "is_primary": true
            },
            {
                "uuid": "3e93...ab70",
                "first_name": "Ales",
                "last_name": "Cvekl",
                "email": "ale...@....edu",
                "is_primary": false
            },
            {
                "uuid": "acee...a0ec",
                "first_name": "Eleanor",
                "last_name": "Seaby",
                "email": "ese...@....org",
                "is_primary": false
            }
        ],
        "group": {
            "uuid": "9162...8f65",
            "name": "Glaucoma",
            "description": "The Glaucoma ...variants have been reported.",
            "caption": null,
            "icon_url": null,
            "status": "active",
            "visibility": "public",
            "status_date": "2022-02-14T18:47:01+00:00",
            "type": "vcep",
            "coi": "http:\/\/localhost\/coi-group\/91623e1e-ca2d-458f-9e61-feb23c948f65",
            "expert_panel": {
                "uuid": "9162...8f65",
                "affiliation_id": "50053",
                "name": "Glaucoma",
                "short_name": "Glaucoma",
                "scope_description": "Lorem ipsum dolor ...amet non turpis.",
                "membership_description": "Lorem ipsum dolor...nisi ex a nisl.",
                "type": "vcep",
                "date_completed": "2021-10-27T04:00:00.000000Z",
                "inactive_date": null,
                "current_step": 4,
                "clinvar_org_id": "234234",
                "vcep_definition_approval": "2019-08-19T04:00:00.000000Z",
                "vcep_draft_specification_approval": "2021-03-02T05:00:00.000000Z",
                "vcep_pilot_approval": "2021-09-16T04:00:00.000000Z",
                "vcep_final_approval": "2021-10-27T04:00:00.000000Z"
            },
            "parent": {
                "uuid": "eb3c...c54a",
                "name": "Ocular",
                "type": "cdwg",
                "status": "active"
            }
        }
    }
}
```
- `funding_award_updated`
```
{
    "event_type": "funding_award_updated",
    "schema_version": "2.0.1",
    "date": "2026-05-06 20:00:28",
    "data": {
        "uuid": "b6a6...d090",
        "funding_source": {
            "uuid": "bdc5...1b5a",
            "name": "American Society of Hematology",
            "funding_type": "Foundation",
            "caption": "ASH is a ... clinical domain.",
            "website_url": "https:\/\/www.hematology.org\/",
            "logo_path": "http:\/\/localhost\/funding-sources\/logo\/IHlF88mcRciLhyht8nomDvQ0OsSqJ3Uef6QIbLfb.jpg"
        },
        "award_number": "AWD 234235",
        "start_date": "2026-03-01",
        "end_date": "2030-10-31",
        "award_url": "https:\/\/www.lipsum.com",
        "funding_source_division": null,
        "rep_contacts": [
            {
                "name": "Andreas Natulanggan",
                "role": "PIC",
                "email": "and...@....edu",
                "phone": "(854) 214 ...."
            }
        ],
        "notes": "Ut in feugiat ... feugiat placerat quam.",
        "contact_pis": [
            {
                "uuid": "9f89...e863",
                "first_name": "Andrew",
                "last_name": "Dubowsky",
                "email": "and...@....au",
                "is_primary": false
            },
            {
                "uuid": "3e93...ab70",
                "first_name": "Ales",
                "last_name": "Cvekl",
                "email": "ale...@....edu",
                "is_primary": true
            },
            {
                "uuid": "acee...a0ec",
                "first_name": "Eleanor",
                "last_name": "Seaby",
                "email": "ese...@....org",
                "is_primary": false
            }
        ],
        "group": {
            "uuid": "9162...8f65",
            "name": "Glaucoma",
            "description": "The Glaucoma .... been reported.",
            "caption": null,
            "icon_url": null,
            "status": "active",
            "visibility": "public",
            "status_date": "2022-02-14T18:47:01+00:00",
            "type": "vcep",
            "coi": "http:\/\/localhost\/coi-group\/91623e1e-ca2d-458f-9e61-feb23c948f65",
            "expert_panel": {
                "uuid": "9162...8f65",
                "affiliation_id": "50053",
                "name": "Glaucoma",
                "short_name": "Glaucoma",
                "scope_description": "Lorem ipsum...amet non turpis.",
                "membership_description": "Lorem ipsum ...ex a nisl.",
                "type": "vcep",
                "date_completed": "2021-10-27T04:00:00.000000Z",
                "inactive_date": null,
                "current_step": 4,
                "clinvar_org_id": "234234",
                "vcep_definition_approval": "2019-08-19T04:00:00.000000Z",
                "vcep_draft_specification_approval": "2021-03-02T05:00:00.000000Z",
                "vcep_pilot_approval": "2021-09-16T04:00:00.000000Z",
                "vcep_final_approval": "2021-10-27T04:00:00.000000Z"
            },
            "parent": {
                "uuid": "eb3c...c54a",
                "name": "Ocular",
                "type": "cdwg",
                "status": "active"
            }
        }
    }
}
```
- `funding_award_deleted`
```
{
    "event_type": "funding_award_deleted",
    "schema_version": "2.0.1",
    "date": "2026-05-06 20:05:23",
    "data": {
        "uuid": "1429...1537",
        "group": {
            "uuid": "9162...8f65",
            "name": "Glaucoma",
            "description": "The Glaucoma ...reported.",
            "caption": null,
            "icon_url": null,
            "status": "active",
            "visibility": "public",
            "status_date": "2022-02-14T18:47:01+00:00",
            "type": "vcep",
            "coi": "http:\/\/localhost\/coi-group\/91623e1e-ca2d-458f-9e61-feb23c948f65",
            "expert_panel": {
                "uuid": "9162...8f65",
                "affiliation_id": "50053",
                "name": "Glaucoma",
                "short_name": "Glaucoma",
                "scope_description": "Lorem ...t non turpis.",
                "membership_description": "Lorem ipsum do...i ex a nisl.",
                "type": "vcep",
                "date_completed": "2021-10-27T04:00:00.000000Z",
                "inactive_date": null,
                "current_step": 4,
                "clinvar_org_id": "234234",
                "vcep_definition_approval": "2019-08-19T04:00:00.000000Z",
                "vcep_draft_specification_approval": "2021-03-02T05:00:00.000000Z",
                "vcep_pilot_approval": "2021-09-16T04:00:00.000000Z",
                "vcep_final_approval": "2021-10-27T04:00:00.000000Z"
            },
            "parent": {
                "uuid": "eb3c...c54a",
                "name": "Ocular",
                "type": "cdwg",
                "status": "active"
            }
        }
    }
}
```

These extend `ExpertPanelEvent` → `GroupEvent` → `PublishableEvent`.

`GroupEvent::getPublishableMessage()` automatically appends a `group` payload to the event message.

**Funding Award event payload (getProperties)**
Recommended: keep mapping in one reusable mapper (`FundingAwardPayload` / trait -> MapsFundingAwardsForDx) and have events call that (avoid duplication and drift).

Target schema (data portion):
- `uuid`
- `funding_source` `{ uuid, name, funding_type, caption, website_url, logo_path }`
- `award_number`
- `start_date`, `end_date`
- `award_url`
- `funding_source_division`
- `rep_contacts`
- `notes`
- `contact_pis` array with `uuid, first_name, last_name, email, is_primary` 

### 7.2 Funding Source events (global)
Funding Sources do **not** belong to a Group/EP, so these should **not** extend `GroupEvent`.  
Instead create a separate base event (extends `RecordableEvent`, implements `PublishableEvent`) that publishes to `dx.topics.outgoing.gpm-general-events` without appending a group.

Planned:
- `FundingSourceCreated`
```
{
    "event_type": "funding_source_created",
    "schema_version": "2.0.1",
    "date": "2026-05-06 19:17:47",
    "data": {
        "uuid": "8e2f...fa56",
        "name": "Enzyvant",
        "funding_type": "Foundation",
        "caption": "Enzyvant is ... desperate need.",
        "website_url": "https:\/\/enzyvant.com",
        "logo_path": "http:\/\/gpm.clinicalgenome.org\/funding-sources\/logo\/JT5PS3u5SsqcT2oKCyXEDrCtRKmRz2YGIFpUq4kb.png"
    }
}
```
- `FundingSourceUpdated`
```
{
    "event_type": "funding_source_updated",
    "schema_version": "2.0.1",
    "date": "2026-05-06 19:19:11",
    "data": {
        "uuid": "8e2f...fa56",
        "name": "Enzyvant",
        "funding_type": "Government",
        "caption": "Enzyvant is... desperate need.",
        "website_url": "https:\/\/enzyvant.org",
        "logo_path": "http:\/\/gpm.clinicalgenome.org\/funding-sources\/logo\/JT5PS3u5SsqcT2oKCyXEDrCtRKmRz2YGIFpUq4kb.png"
    }
}
```
- `FundingSourceDeleted`
```
{
    "event_type": "funding_source_deleted",
    "schema_version": "2.0.1",
    "date": "2026-05-06 19:20:58",
    "data": {
        "uuid": "8e2f....fa56"
    }
}
```

**Single source of truth**
Use one schema builder for funding_source mapping that is reused by:
- Funding Award events (embedded funding_source)
- Funding Source events (top-level data)

---