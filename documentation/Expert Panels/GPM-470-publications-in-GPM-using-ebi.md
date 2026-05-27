# GPM-470 Publications in GPM using Europe PMC/EBI and Annual Update Integration

## Summary

This work updates the **Group Detail > Publications** tab to use **Europe PMC/EBI publication lookup** for adding publications, and simplifies how publication data is stored and consumed by the **Annual Update** publications section.

The main goal is to support lookup by **DOI**, **PMID**, or **PMCID** through one external publication lookup endpoint while keeping a **single, consistent publication representation** as much as possible.

PMCID is preferred when available because it is important for the publication workflow and is more consistently supported by Europe PMC for biomedical publication records than it was in the previous OpenAlex-based lookup.

---

## Scope

### Included
- Add publication from the **Group Detail > Publications** tab, for all group types, including EPs and WGs
- Lookup by **DOI**, **PMID**, or **PMCID** using the Europe PMC/EBI search endpoint
- Preview publication before saving
- Prefer **PMCID** when Europe PMC returns one
- Save publication into the `publications` table using the app's own schema
- Store a **trimmed metadata object** instead of the full raw Europe PMC response
- Update the Publications tab UI to display publications using the saved DB row shape
- Simplify the Annual Update publications flow to work from the backend publication response instead of relying on the older exchange-shaped format

### Not included
- General publication text/title search
- Full raw Europe PMC payload storage
- Exposing Europe PMC/EBI-specific provider details in the UI
- Maintaining OpenAlex as a fallback lookup provider

---

## Why Europe PMC/EBI instead of OpenAlex

OpenAlex works well for general publication metadata, but it did not reliably support our PMCID use case. One tested example was `PMID: 34445366`, which has `PMCID: PMC8395394`. OpenAlex returned the publication for the PMID lookup but did not include the PMCID, and a direct PMCID lookup did not return a matching record.

Because PMCID is important for the publication workflow, staying with OpenAlex would likely require adding another fallback service anyway. Europe PMC/EBI is a better fit because it supports DOI, PMID, and PMCID lookup through a single search endpoint and is more aligned with biomedical publication records.

---

## Configuration

The publication lookup base URL is configured through `services.publication.base_url`.

```php
'publication' => [
    'base_url' => env('PUBLICATION_BASE_URL', 'https://www.ebi.ac.uk/europepmc/webservices/rest/search'),
],
```

The backend action should call the configured URL directly and pass the Europe PMC search criteria as query parameters.

Example:

```php
$response = Http::acceptJson()
    ->timeout(15)
    ->get(config('services.publication.base_url'), [
        'query' => $lookup['query'],
        'format' => 'json',
        'pageSize' => 1,
        'resultType' => 'core',
    ]);
```

No API key is required.

---

## Europe PMC lookup approach

The Publications tab uses the Europe PMC/EBI REST search endpoint.

```text
https://www.ebi.ac.uk/europepmc/webservices/rest/search
```

### Supported input formats

#### DOI
- `10.12968/hmed.2021.0459`
- `DOI:10.12968/hmed.2021.0459`
- `DOI: 10.12968/hmed.2021.0459`
- `https://doi.org/10.12968/hmed.2021.0459`

#### PMID
- `35243878`
- `PMID:35243878`
- `PMID: 35243878`
- `https://pubmed.ncbi.nlm.nih.gov/35243878/`

#### PMCID
- `PMC1234567`
- `PMCID:PMC1234567`
- `PMCID: PMC1234567`
- `https://pmc.ncbi.nlm.nih.gov/articles/PMC1234567/`

### Lookup query behavior

Input is parsed into a normalized identifier type (`doi`, `pmid`, or `pmcid`) and converted into a Europe PMC search query.

| Input type | Europe PMC query |
|---|---|
| DOI | `DOI:"{doi}"` |
| PMID | `EXT_ID:{pmid} AND SRC:MED` |
| PMCID | `PMCID:{pmcid}` |

Example for `PMID: 34445366`:

```text
EXT_ID:34445366 AND SRC:MED
```

Europe PMC returns a `resultList.result` array. The publication lookup action uses the first result and normalizes it into GPM's publication payload shape so it can be used directly for preview and save.

---

## Identifier priority

When multiple identifiers are available, GPM should preserve all available identifiers in `meta`, but **PMCID is preferred as the canonical publication identifier when available**.

Preferred identifier order:

1. `pmcid`
2. `pmid`
3. `doi`

This means a user may search by PMID or DOI, but if Europe PMC returns a PMCID, the saved publication can still use PMCID as the preferred source/identifier/link while keeping the original DOI and PMID inside `meta`.

---

## Europe PMC response mapping

Europe PMC response fields are not the same as OpenAlex fields, so the normalizer needs to map from Europe PMC's shape.

Common fields used:

| GPM field | Europe PMC field |
|---|---|
| title | `title` |
| DOI | `doi` |
| PMID | `pmid` |
| PMCID | `pmcid` |
| publication type | `pubTypeList.pubType` |
| published date | `firstPublicationDate`, then `electronicPublicationDate`, then `journalInfo.printPublicationDate` |
| journal | `journalInfo.journal.title` |
| authors | `authorList.author[].fullName`, fallback to `authorString` |

For publication type, Europe PMC may return multiple values such as:

```json
["review-article", "Review", "Journal Article"]
```

The normalizer should select the most useful display value for GPM. For example, it may prefer `review-article` or `Review` over the generic `Journal Article`.

---

## Saved publication shape

Publications are saved using the existing `publications` table.

### Table fields used
- `source`
- `identifier`
- `link`
- `pub_type`
- `published_at`
- `meta`

### Payload used for save

The Europe PMC lookup result is normalized into this payload shape before posting to the backend:

```json
{
  "source": "pmcid",
  "identifier": "PMC8395394",
  "link": "https://pmc.ncbi.nlm.nih.gov/articles/PMC8395394/",
  "pub_type": "review-article",
  "published_at": "2021-08-12",
  "meta": {
    "title": "Current and Future Development in Lung Cancer Diagnosis.",
    "type": "review-article",
    "published_at": "2021-08-12",
    "journal": "International journal of molecular sciences",
    "doi": {
      "id": "10.3390/ijms22168661",
      "link": "https://doi.org/10.3390/ijms22168661"
    },
    "pmid": {
      "id": "34445366",
      "link": "https://pubmed.ncbi.nlm.nih.gov/34445366/"
    },
    "pmcid": {
      "id": "PMC8395394",
      "link": "https://pmc.ncbi.nlm.nih.gov/articles/PMC8395394/"
    },
    "authors": ["Nooreldeen R", "Bach H"]
  }
}
```

### Notes
- `source` reflects the preferred identifier selected after lookup, not necessarily the identifier type the user typed
- `identifier` stores the cleaned identifier value, not a full URL
- `link` should use the preferred identifier link; PMCID is preferred when available
- `meta` stores all available DOI, PMID, and PMCID values so the UI and downstream logic can use them when needed
- `meta` stores only the fields the UI and downstream logic actually need

---

## Trimmed metadata design

Instead of storing the full Europe PMC response JSON, the saved `meta` object is intentionally reduced to the fields we actually use.

### Current trimmed `meta`
- `title`
- `type`
- `published_at`
- `journal`
- `doi`
- `pmid`
- `pmcid`
- `authors`

### Why
This keeps the saved data:
- easier to read
- easier to debug
- smaller than the full provider payload
- free of unnecessary provider-specific clutter

It also avoids surfacing fields such as Europe PMC internal fields, raw indexing metadata, or unrelated provider details in the UI.

---

## Group Detail > Publications tab behavior

### Add publication
1. User opens the **Add publication** modal
2. User enters a DOI, PMID, or PMCID
3. The app looks up the publication through the backend `PublicationLookup` action
4. The backend calls Europe PMC/EBI using the configured publication lookup URL
5. The backend normalizes the Europe PMC response into the GPM publication payload shape
6. A preview is shown
7. If the preview looks correct, the user saves it
8. The backend stores the publication row and trimmed `meta`
9. The publications table refreshes

### Form helper text

Suggested helper text:

> Paste a DOI, PMID, or PMCID to preview the record before saving. PMCID is preferred when available.

### Preview
The preview uses the normalized payload directly, so the save request does not need to reformat the object again.

### Details modal
The details modal shows saved publication metadata. The intent is to show the information the app actually stores and uses, not the full raw Europe PMC response.

---

## Annual Update

### Current direction
The Annual Update publications form is being simplified so it can work from the **raw backend publication row shape** rather than depending on a separate exchange representation.

The backend response currently looks like this:

```json
[
  {
    "publication_id": "1aaa552a-9c4d-4fe8-99a5-d6ba1a721d1b",
    "type": "review-article",
    "source_type": "pmcid",
    "source_id": "PMC8395394",
    "title": "Current and Future Development in Lung Cancer Diagnosis.",
    "authors": ["Nooreldeen R", "Bach H"],
    "journal": "International journal of molecular sciences",
    "published": "2021-08-12T00:00:00.000000Z",
    "url": "https://pmc.ncbi.nlm.nih.gov/articles/PMC8395394/",
    "meta": {
      "doi": {
        "id": "10.3390/ijms22168661",
        "link": "https://doi.org/10.3390/ijms22168661"
      },
      "pmid": {
        "id": "34445366",
        "link": "https://pubmed.ncbi.nlm.nih.gov/34445366/"
      },
      "type": "review-article",
      "pmcid": {
        "id": "PMC8395394",
        "link": "https://pmc.ncbi.nlm.nih.gov/articles/PMC8395394/"
      },
      "title": "Current and Future Development in Lung Cancer Diagnosis.",
      "authors": ["Nooreldeen R", "Bach H"],
      "journal": "International journal of molecular sciences",
      "published_at": "2021-08-12"
    }
  }
]
```

### Intended AU behavior
The Annual Update component should use the raw row fields directly:
- `publication_id`
- `source_type`
- `source_id`
- `title`
- `journal`
- `type`
- `published`
- `url`

The only extra field Annual Update needs on top of the raw row is:
- `included` — a UI flag indicating whether that publication should be counted in the Annual Update

### Resulting direction
If the Annual Update form works directly from the raw backend row shape, then the old broad `normalizePublication()` function can be removed or reduced significantly.

---

## `included` field in Annual Update

`included` is **not** a field from the `publications` table.

It is an Annual Update UI flag used for:
- the include/exclude checkbox per publication
- include all / exclude all actions
- counting how many publications are included in the current Annual Update

So the Annual Update row shape becomes roughly:

```json
{
  "publication_id": "...",
  "source_type": "pmcid",
  "source_id": "PMC8395394",
  "title": "...",
  "journal": "...",
  "type": "review-article",
  "published": "2021-08-12T00:00:00.000000Z",
  "url": "https://pmc.ncbi.nlm.nih.gov/articles/PMC8395394/",
  "meta": { ... },
  "included": false
}
```

---

## Notes shown in Annual Update

A note was added/considered to clarify the publication date range being used for the Annual Update.

Example wording:

> Only publications with a publication date within this Annual Update period (`start` to `end`) are shown.

This is intended to clarify that the list is filtered by the Annual Update date range, not just by calendar year.

---

## DeX Message Sample

### GPM General Events for Publication Added

```json
{
  "event_type": "publication_added",
  "schema_version": "2.0.1",
  "date": "2026-05-07 13:24:42",
  "data": {
    "publication_id": "47a6...94d3",
    "type": "review-article",
    "source_type": "pmcid",
    "source_id": "PMC8395394",
    "title": "Current and Future Development in Lung Cancer Diagnosis.",
    "authors": [
      "Nooreldeen R",
      "Bach H"
    ],
    "journal": "International journal of molecular sciences",
    "published_at": "2021-08-12T00:00:00.000000Z",
    "url": "https://pmc.ncbi.nlm.nih.gov/articles/PMC8395394/",
    "meta": {
      "doi": {
        "id": "10.3390/ijms22168661",
        "link": "https://doi.org/10.3390/ijms22168661"
      },
      "pmid": {
        "id": "34445366",
        "link": "https://pubmed.ncbi.nlm.nih.gov/34445366/"
      },
      "type": "review-article",
      "pmcid": {
        "id": "PMC8395394",
        "link": "https://pmc.ncbi.nlm.nih.gov/articles/PMC8395394/"
      },
      "title": "Current and Future Development in Lung Cancer Diagnosis.",
      "authors": [
        "Nooreldeen R",
        "Bach H"
      ],
      "journal": "International journal of molecular sciences",
      "published_at": "2021-08-12"
    },
    "group": {
      "uuid": "9162...8f65",
      "name": "Glaucoma",
      "description": "The Glaucoma ...have been reported.",
      "caption": null,
      "publications": [
        {
          "publication_id": "47a6...94d3",
          "type": "review-article",
          "source_type": "pmcid",
          "source_id": "PMC8395394",
          "title": "Current and Future Development in Lung Cancer Diagnosis.",
          "authors": [
            "Nooreldeen R",
            "Bach H"
          ],
          "journal": "International journal of molecular sciences",
          "published_at": "2021-08-12T00:00:00.000000Z",
          "url": "https://pmc.ncbi.nlm.nih.gov/articles/PMC8395394/",
          "meta": {
            "doi": {
              "id": "10.3390/ijms22168661",
              "link": "https://doi.org/10.3390/ijms22168661"
            },
            "pmid": {
              "id": "34445366",
              "link": "https://pubmed.ncbi.nlm.nih.gov/34445366/"
            },
            "type": "review-article",
            "pmcid": {
              "id": "PMC8395394",
              "link": "https://pmc.ncbi.nlm.nih.gov/articles/PMC8395394/"
            },
            "title": "Current and Future Development in Lung Cancer Diagnosis.",
            "authors": [
              "Nooreldeen R",
              "Bach H"
            ],
            "journal": "International journal of molecular sciences",
            "published_at": "2021-08-12"
          }
        }
      ],
      "icon_url": null,
      "status": "active",
      "visibility": "public",
      "status_date": "2022-02-14T18:47:01+00:00",
      "type": "vcep",
      "coi": "http://localhost/coi-group/9162...8f65",
      "expert_panel": {
        "uuid": "9162...8f65",
        "affiliation_id": "50053",
        "name": "Glaucoma",
        "short_name": "Glaucoma",
        "scope_description": "Lorem ipsum ...non turpis.",
        "membership_description": "Lorem ipsum...ex a nisl.",
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

### GPM General Events for Publication Removed

```json
{
  "event_type": "publication_deleted",
  "schema_version": "2.0.1",
  "date": "2026-05-07 13:25:18",
  "data": {
    "publication_id": "47a6...94d3",
    "source": "pmcid",
    "identifier": "PMC8395394",
    "group": {
      "uuid": "9162...8f65",
      "name": "Glaucoma",
      "description": "The Glaucoma ...been reported.",
      "caption": null,
      "publications": [],
      "icon_url": null,
      "status": "active",
      "visibility": "public",
      "status_date": "2022-02-14T18:47:01+00:00",
      "type": "vcep",
      "coi": "http://localhost/coi-group/9162...8f65",
      "expert_panel": {
        "uuid": "9162...8f65",
        "affiliation_id": "50053",
        "name": "Glaucoma",
        "short_name": "Glaucoma",
        "scope_description": "Lorem ipsum... amet non turpis.",
        "membership_description": "Lorem ipsum ...semper nisi ex a nisl.",
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

### GPM Checkpoint Events

```json
{
  "event_type": "group_checkpoint_event",
  "schema_version": "2.0.1",
  "date": "2026-05-07 14:15:29",
  "data": {
    "uuid": "9162...8f65",
    "name": "Glaucoma",
    "description": "The Glaucoma ... been reported.",
    "caption": null,
    "publications": [
      {
        "publication_id": "f52a...46b",
        "type": "review-article",
        "source_type": "pmcid",
        "source_id": "PMC8395394",
        "title": "Current and Future Development in Lung Cancer Diagnosis.",
        "authors": [
          "Nooreldeen R",
          "Bach H"
        ],
        "journal": "International journal of molecular sciences",
        "published_at": "2021-08-12T00:00:00.000000Z",
        "url": "https://pmc.ncbi.nlm.nih.gov/articles/PMC8395394/",
        "meta": {
          "doi": {
            "id": "10.3390/ijms22168661",
            "link": "https://doi.org/10.3390/ijms22168661"
          },
          "pmid": {
            "id": "34445366",
            "link": "https://pubmed.ncbi.nlm.nih.gov/34445366/"
          },
          "type": "review-article",
          "pmcid": {
            "id": "PMC8395394",
            "link": "https://pmc.ncbi.nlm.nih.gov/articles/PMC8395394/"
          },
          "title": "Current and Future Development in Lung Cancer Diagnosis.",
          "authors": [
            "Nooreldeen R",
            "Bach H"
          ],
          "journal": "International journal of molecular sciences",
          "published_at": "2021-08-12"
        }
      }
    ],
    "icon_url": null,
    "status": "active",
    "visibility": "public",
    "status_date": "2022-02-14T18:47:01+00:00",
    "type": "vcep",
    "coi": "http://localhost/coi-group/9162...8f65",
    "members": [
      {
        "uuid": "532d...990b",
        "first_name": "Emmanuelle",
        "last_name": "Souzeau",
        "roles": [],
        "additional_permissions": [],
        "institution": "Flinders University",
        "credentials": [
          "PhD"
        ],
        "code_of_conduct": {
          "status": "missing",
          "current_version": "1.0.0",
          "completed_at": null,
          "expires_at": null,
          "days_remaining": null
        },
        "profile_photo": "http://localhost/profile-photos/cG5qlDoTCm28mrGaDJ5AD3aDCAMgwLohUAu4OrrU.png"
      }
    ],
    "expert_panel": {
      "uuid": "9162...8f65",
      "affiliation_id": "50053",
      "name": "Glaucoma",
      "short_name": "Glaucoma",
      "scope_description": "Lorem ipsum... ex a nisl.",
      "type": "vcep",
      "date_completed": "2021-10-27T04:00:00.000000Z",
      "inactive_date": null,
      "current_step": 4,
      "all_genes": [
        {
          "hgnc_id": 7610,
          "gene_symbol": "MYOC",
          "mondo_id": "MONDO:0007665",
          "disease_name": "glaucoma 1, open angle, E",
          "disease_entity": null
        },
        {
          "hgnc_id": 2597,
          "gene_symbol": "CYP1B1",
          "mondo_id": "MONDO:0009277",
          "disease_name": "glaucoma 3A",
          "disease_entity": null
        }
      ],
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
```
