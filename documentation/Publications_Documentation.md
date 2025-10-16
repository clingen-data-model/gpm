
# Publications Feature

This document describes the **Publications** functionality added to the GPM application.  
The feature allows Expert Panels (EPs) and Working Groups (WGs) to track their publications directly within GPM.

---

## Overview

- Publications are attached to **Groups** (Expert Panels / Working Groups).
- Users can add publications by providing **PMID**, **PMCID**, **DOI**, or a publication **URL**.
- Metadata is automatically enriched (title, journal, date, type, link, etc.) via external APIs (PubMed, EuropePMC, Crossref).
- Publications are displayed in a dedicated **Publications tab** on the Group detail page.
- Publications are also logged and written to **Data Exchange** for downstream systems **only after enrichment completes**.
- No update or edit functionality is provided; users can only add or remove publications.

---

## Database

Table: `publications`

| Column         | Type         | Notes                                                                 |
|----------------|--------------|-----------------------------------------------------------------------|
| id             | bigint       | Primary key                                                           |
| group_id       | bigint       | FK to `groups` table                                                  |
| source         | varchar(50)  | Source type: `pmid`, `pmcid`, `doi`, `url`                            |
| identifier     | varchar(255) | Source identifier (normalized)                                        |
| pub_type       | varchar(50)  | Publication type (e.g., `preprint`, `published`)                      |
| link           | text         | Direct link to the publication                                        |
| meta           | json         | Enriched metadata snapshot                                            |
| published_at   | datetime     | Publication date (nullable; parsed from metadata)                     |
| status         | varchar(50)  | `pending`, `enriching`, `enriched`, `failed`                          |
| error          | text         | Error message if enrichment failed                                    |
| sent_to_dx_at  | timestamp    | **Set after `PublicationAdded` is emitted to DX** (idempotency guard) |
| added_by_id    | bigint       | User who added the publication                                        |
| created_at     | timestamp    |                                                                       |
| updated_at     | timestamp    |                                                                       |

**Model casts (excerpt)**

```php
protected $casts = [
  'meta'          => 'array',
  'published_at'  => 'datetime',
  'sent_to_dx_at' => 'datetime',
];
```

---

## Backend

### Actions
- **PublicationAdd** – authorizes + validates input, normalizes identifiers, creates records with `status=pending`, and **chains jobs** to enrich and then emit to DX.  
  **Note:** The action **does not** fire `PublicationAdded` directly. Emission happens after enrichment in the second job.

- **PublicationDelete** – removes publications and fires `PublicationDeleted` event.

### Jobs
- **EnrichPublication** – queued job that:
  - sets `status` to `enriching`,
  - fetches metadata from PubMed / EuropePMC / Crossref via `RemotePublicationClient`,
  - updates `meta`, `published_at`, `pub_type`, and sets `status` to `enriched` (or `failed` on error).
  - **Does not** emit events.

- **SendPublicationAdded** – queued job that:
  - loads the fresh `Publication` and `Group`,
  - proceeds **only if** `status === 'enriched'` and minimal meta (e.g., `title`) is present,
  - emits `PublicationAdded($group, $publication)`,
  - sets `sent_to_dx_at = now()` to ensure **idempotent** single send to DX.

### Events
- **PublicationAdded**
- **PublicationDeleted**

All events extend `GroupEvent`, so they log changes in the application log and are published to **Data Exchange**.  
For `PublicationAdded`, the DX publish now occurs **after enrichment** via the `SendPublicationAdded` job.

### Services
- **PublicationLookup** – normalizes raw user input into `[source, identifier]` pair.
- **RemotePublicationClient** – encapsulates calls to external APIs (PubMed, EuropePMC, Crossref, etc.), extracts metadata consistently.

---

## Chained Job Flow

We use `Bus::chain([ EnrichPublication, SendPublicationAdded ])` and dispatch **after the DB commit**.

**PublicationAdd (core lines):**
```php
use Illuminate\\Support\\Facades\\Bus;
use Illuminate\\Support\\Facades\\DB;

DB::afterCommit(function () use ($pub, $group) {
    Bus::chain([
        new EnrichPublication($pub->id),
        new SendPublicationAdded($group->id, $pub->id),
    ])->dispatch();
});
```

**SendPublicationAdded (guards + idempotency):**
```php
if ($pub->status !== 'enriched' || empty($pub->meta['title'] ?? null)) {
    $this->release($this->backoff);
    return;
}

if ($pub->sent_to_dx_at) {
    return;
}

event(new PublicationAdded($group, $pub->fresh()));
$pub->forceFill(['sent_to_dx_at' => now()])->save();
```

---

## Frontend

### GroupPublications.vue
- Displays publications in a table.
- Fetches data from `/api/groups/{uuid}/publications`.
- Allows adding new entries via modal dialog (textarea for multiple PMIDs/DOIs/URLs).
- Shows enriched metadata (type, title, journal, IDs, publication date, status).
- Provides **Details** button to inspect all metadata fields in a modal.
- Provides **Remove** button with confirmation.

### UI Features
- **Add button** becomes “Adding…” while posting.
- Textarea is disabled while posting.
- **Details modal** displays meta data in a property/value grid with `Copy JSON` and `Open Link` buttons.
- **Action buttons** use flex-wrap and spacing to render nicely even on narrow screens.

---

## API Endpoints

- `GET /api/groups/{uuid}/publications` – list publications for a group.
- `POST /api/groups/{uuid}/publications` – add one or more publications.
- `DELETE /api/groups/{uuid}/publications/{id}` – delete a publication.

---

## Workflow

1. User opens the **Publications** tab in Group detail.
2. User pastes one or more identifiers (PMID, PMCID, DOI, URL).
3. `PublicationAdd` creates records with `status = pending` and dispatches a chained job **after commit**.
4. `EnrichPublication` runs, sets `status = enriching`, fetches and stores metadata, then sets `status = enriched`.
5. `SendPublicationAdded` verifies the record is enriched, emits `PublicationAdded`, and sets `sent_to_dx_at`.
6. Events write to the application log and DX; UI refreshes to display enriched metadata.

---

## Resilience

- Jobs use retries/backoff (e.g., `tries=5`, `backoff=30`). On terminal failure, `EnrichPublication` sets `status=failed` and stores a trimmed error message in `error`.

---

## DeX Message Sample
**Publication Added**
What's inside the meta key is define by response recevied from API.
URL: https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esummary.fcgi

```php
{
    "event_type": "publication_added",
    "schema_version": "1.9.9",
    "date": "2025-10-16 09:00:06",
    "data": {
        "publication_id": "82adebdc-a860-4d33-b465-2e011263b575",
        "source": "pmid",
        "identifier": "37761008",
        "pub_type": "research-article; journal article",
        "published_at": "2023-09-19",
        "meta_keys": {
            "id": "37761008",
            "source": "MED",
            "pmid": "37761008",
            "pmcid": "PMC10526923",
            "fullTextIdList": {
                "fullTextId": [
                    "PMC10526923"
                ]
            },
            "doi": "10.3390\/biomedicines11092567",
            "title": "ABCC1, ABCG2 and FOXP3: Predictive Biomarkers of Toxicity from Methotrexate Treatment in Patients Diagnosed with Moderate-to-Severe Psoriasis.",
            "authorString": "Membrive-Jim\u00e9nez C, Vieira-Maroun S, M\u00e1rquez-Pete N, Cura Y, P\u00e9rez-Ram\u00edrez C, Tercedor-S\u00e1nchez J, Jim\u00e9nez-Morales A, Ram\u00edrez-Tortosa MDC.",
            "journalTitle": "Biomedicines",
            "issue": "9",
            "journalVolume": "11",
            "pubYear": "2023",
            "journalIssn": "2227-9059",
            "pageInfo": "2567",
            "pubType": "research-article; journal article",
            "isOpenAccess": "Y",
            "inEPMC": "Y",
            "inPMC": "Y",
            "hasPDF": "Y",
            "hasBook": "N",
            "hasSuppl": "Y",
            "citedByCount": 1,
            "hasReferences": "Y",
            "hasTextMinedTerms": "Y",
            "hasDbCrossReferences": "N",
            "hasLabsLinks": "Y",
            "hasTMAccessionNumbers": "Y",
            "tmAccessionTypeList": {
                "accessionType": [
                    "refsnp"
                ]
            },
            "firstIndexDate": "2023-09-29",
            "firstPublicationDate": "2023-09-19",
            "url": "https:\/\/pubmed.ncbi.nlm.nih.gov\/37761008\/"
        },
        "has_meta": true,
        "group": {
            "id": "aa3e9ec2-aad1-4b1e-ac64-fa8a007108f5",
            "name": "Prenatal",
            "description": "...",
            "status": "active",
            "type": "gcep",
            "parent_group": {
                "id": "17574b3d-...-551c6e79dcaf",
                "name": "Reproduction and Pregnancy",
                "description": null,
                "status": "active",
                "type": "cdwg"
            },
            "ep_id": "aa3e9ec2-...-fa8a007108f5",
            "affiliation_id": "40106",
            "scope_description": null,
            "short_name": "Prenatal"
        }
    }
}
```

**Publication Deleted**
```php
{
    "event_type": "publication_deleted",
    "schema_version": "1.9.9",
    "date": "2025-10-16 09:25:46",
    "data": {
        "publication_id": "2525cd7c-...-89bda2f4fe9f",
        "source": "pmid",
        "identifier": "37834183",
        "group": {
            "id": "aa3e9ec2-...-fa8a007108f5",
            "name": "Prenatal",
            "description": "...",
            "status": "active",
            "type": "gcep",
            "parent_group": {
                "id": "17574b3d-...-551c6e79dcaf",
                "name": "Reproduction and Pregnancy",
                "description": null,
                "status": "active",
                "type": "cdwg"
            },
            "ep_id": "aa3e9ec2-...-fa8a007108f5",
            "affiliation_id": "40106",
            "scope_description": null,
            "short_name": "Prenatal"
        }
    }
}
```

**Publications on CheckPoint**
```php
{
    "event_type": "group_checkpoint_event",
    "schema_version": "2.0.0",
    "date": "2025-10-16 09:45:05",
    "data": {
        "uuid": "aa3e9ec2-...-fa8a007108f5",
        "name": "Prenatal",
        "description": "...",
        "caption": null,
        "publications": [
            {
                "uuid": "67ec7831-...-8d9c2a726b62",
                "type": "research-article; multicenter study; journal article",
                "title": "Switches between biologics in patients with moderate-to-severe psoriasis: results from the French cohort PSOBIOTEQ.",
                "authors": [
                    "Curmin R",
                    "Guillo S",
                    "De Rycke Y",
                    "Bachelez H",
                    "Beylot-Barry M",
                    "Beneton N",
                    "Chosidow O",
                    "Dupuy A",
                    "Joly P",
                    "Jullien D",
                    "Richard MA",
                    "Viguier M",
                    "Sbidian E",
                    "Paul C",
                    "Mah\u00e9 E",
                    "Tubach F",
                    "PSOBIOTEQ Study Group."
                ],
                "journal": "J Eur Acad Dermatol Venereol",
                "identifiers": {
                    "pmid": "35793473",
                    "pmcid": "PMC9796114",
                    "doi": "10.1111\/jdv.18409"
                },
                "published": "2022-07-22",
                "url": "https:\/\/pubmed.ncbi.nlm.nih.gov\/35793473\/"
            },
            {
                "uuid": "6267657b-...-e21b46190345",
                "type": "research-article; journal article",
                "title": "Biologic Therapies Decrease Disease Severity and Improve Depression and Anxiety Symptoms in Psoriasis Patients.",
                "authors": [
                    "Timis TL",
                    "Beni L",
                    "Mocan T",
                    "Florian IA",
                    "Florian IA",
                    "Orasan RI."
                ],
                "journal": "Life (Basel)",
                "identifiers": {
                    "pmid": "37240864",
                    "pmcid": "PMC10220716",
                    "doi": "10.3390\/life13051219"
                },
                "published": "2023-05-19",
                "url": "https:\/\/pubmed.ncbi.nlm.nih.gov\/37240864\/"
            }
        ],
        "status": "active",
        ...
    }
}
```
