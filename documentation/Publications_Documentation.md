
# Publications Feature

This document describes the **Publications** functionality added to the GPM application.  
The feature allows Expert Panels (EPs) and Working Groups (WGs) to track their publications directly within GPM.

---

## Overview

- Publications are attached to **Groups** (Expert Panels / Working Groups).
- Users can add publications by providing **PMID**, **PMCID**, **DOI**, or a publication **URL**.
- Metadata is automatically enriched (title, journal, date, type, link, etc.) via external APIs (PubMed, EuropePMC, Crossref).
- Publications are displayed in a dedicated **Publications tab** on the Group detail page.
- Publications are also logged and written to **Data Exchange** for downstream systems.
- No update or edit functionality is provided; users can only add or remove publications.

---

## Database

Table: `publications`

| Column         | Type         | Notes                                              |
|----------------|--------------|----------------------------------------------------|
| id             | bigint       | Primary key                                        |
| group_id       | bigint       | FK to `groups` table                               |
| source         | varchar(50)  | Source type: `pmid`, `pmcid`, `doi`, `url`         |
| identifier     | varchar(255) | Source identifier (normalized)                     |
| pub_type       | varchar(50)  | Publication type (e.g., `preprint`, `published`)   |
| link           | text         | Direct link to the publication                     |
| meta           | json         | Enriched metadata snapshot                         |
| published_at   | datetime     | Publication date                                   |
| status         | varchar(50)  | `pending`, `enriched`, `failed`                    |
| error          | text         | Error message if enrichment failed                 |
| added_by_id    | bigint       | User who added the publication                     |
| created_at     | timestamp    |                                                    |
| updated_at     | timestamp    |                                                    |

---

## Backend

### Actions
- **PublicationStore** – validates input, normalizes identifiers, creates new records, dispatches enrichment jobs, fires `PublicationAdded` event.
- **PublicationDelete** – removes publications and fires `PublicationDeleted` event.

### Jobs
- **EnrichPublication** – queued job that fetches metadata from PubMed / EuropePMC / Crossref, updates the record with title, journal, date, type, and link.

### Events
- **PublicationAdded**
- **PublicationDeleted**

All events extend `GroupEvent`, so they log changes in the application log and are published to **Data Exchange**.

### Services
- **PublicationLookup** – normalizes raw user input into `[source, identifier]` pair.
- **RemotePublicationClient** – encapsulates calls to external APIs (PubMed, EuropePMC, Crossref, etc.), extracts metadata consistently.

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
3. `PublicationStore` action creates records with status = `pending`.
4. `EnrichPublication` job runs asynchronously, fetches metadata, updates the records to `enriched`.
5. Events (`PublicationAdded`, etc.) write to the application log and Data Exchange.
6. UI refreshes to display enriched metadata.

---

## Future Enhancements

- Add publication to website DeX checkpoint.

---
