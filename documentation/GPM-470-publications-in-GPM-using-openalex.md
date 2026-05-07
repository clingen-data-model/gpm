# GPM-470 Publications in GPM using OpenAlex and Annual Update Integration

## Summary

This work updates the **Group Detail > Publications** tab to use **OpenAlex singleton lookup** for adding publications, and simplifies how publication data is stored and consumed by the **Annual Update** publications section.

The main goal is to use a **single, consistent publication representation** as much as possible, rather than transforming publication data into multiple frontend-specific shapes.

---

## Scope

### Included
- Add publication from the **Group Detail > Publications** tab, for all type of group (both EPs and WGs)
- Lookup by **DOI**, **PMID**, or **PMCID** using OpenAlex singleton work lookup
- Preview publication before saving
- Save publication into the `publications` table using the app's own schema
- Store a **trimmed metadata object** instead of the full raw OpenAlex response
- Update the Publications tab UI to display publications using the saved DB row shape
- Simplify the Annual Update publications flow to work from the backend publication response instead of relying on the older exchange-shaped format

### Not included
- General publication text/title search
- Full raw OpenAlex payload storage
- Exposing OpenAlex-specific provider details in the UI

---

## OpenAlex lookup approach

The Publications tab uses **OpenAlex singleton lookup** against the `works` endpoint.

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

### Lookup behavior
- Input is parsed into a normalized identifier type (`doi`, `pmid`, or `pmcid`)
- A singleton OpenAlex request is made using the corresponding `works/{id}` path
- The result is transformed into the app's **publication payload shape** so it can be used directly for preview and save

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
The OpenAlex lookup result is normalized into this payload shape before posting to the backend:

```json
{
  "source": "pmid",
  "identifier": "35243878",
  "link": "https://pubmed.ncbi.nlm.nih.gov/35243878/",
  "pub_type": "review",
  "published_at": "2022-03-01",
  "meta": {
    "title": "...",
    "type": "review",
    "publication_date": "2022-03-01",
    "journal": "...",
    "doi": "10.xxxx/xxxx",
    "pmid": "35243878",
    "pmcid": null,
    "authors": ["Author One", "Author Two"]
  }
}
```

### Notes
- `source` reflects the identifier type used for the lookup (`doi`, `pmid`, or `pmcid`)
- `identifier` stores the cleaned identifier value, not a full URL
- `link` is chosen from the OpenAlex IDs based on the lookup type, so a PMID lookup uses the PubMed URL, a DOI lookup uses the DOI URL, etc.
- `meta` stores only the fields the UI and downstream logic actually need

---

## Trimmed metadata design

Instead of storing the full OpenAlex work JSON, the saved `meta` object is intentionally reduced to the fields we actually use.

### Current trimmed `meta`
- `title`
- `type`
- `publication_date`
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

It also avoids surfacing fields such as OpenAlex IDs or unrelated provider metadata in the UI.

---

## Group Detail > Publications tab behavior

### Add publication
1. User opens the **Add publication** modal
2. User enters a DOI, PMID, or PMCID
3. The app looks up the publication through OpenAlex
4. A preview is shown
5. If the preview looks correct, the user saves it
6. The backend stores the publication row and trimmed `meta`
7. The publications table refreshes

### Preview
The preview uses the normalized payload directly, so the save request does not need to reformat the object again.

### Details modal
The details modal shows saved publication metadata. The intent is to show the information the app actually stores and uses, not the full raw OpenAlex payload.

---

## Annual Update

### Current direction
The Annual Update publications form is being simplified so it can work from the **raw backend publication row shape** rather than depending on a separate exchange representation.

The backend response currently looks like this:

```json
[
  {
    "publication_id": "1aaa552a-9c4d-4fe8-99a5-d6ba1a721d1b",
    "type": "review",
    "source_type": "pmid",
    "source_id": "26024342",
    "title": "Asbestos-Related Lung Cancer and Malignant Mesothelioma of the Pleura: Selected Current Issues",
    "authors": ["Steven Markowitz"],
    "journal": "Seminars in Respiratory and Critical Care Medicine",
    "published": "2015-05-29T00:00:00.000000Z",
    "url": "https://pubmed.ncbi.nlm.nih.gov/26024342",
    "meta": {
      "doi": {
        "id": "10.1055/s-0035-1549449",
        "link": "https://doi.org/10.1055/s-0035-1549449"
      },
      "pmid": {
        "id": "26024342",
        "link": "https://pubmed.ncbi.nlm.nih.gov/26024342"
      },
      "type": "review",
      "pmcid": {
        "id": null,
        "link": null
      },
      "title": "Asbestos-Related Lung Cancer and Malignant Mesothelioma of the Pleura: Selected Current Issues",
      "authors": ["Steven Markowitz"],
      "journal": "Seminars in Respiratory and Critical Care Medicine",
      "published_at": "2015-05-29"
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
  "source_type": "pmid",
  "source_id": "26024342",
  "title": "...",
  "journal": "...",
  "type": "review",
  "published": "2015-05-29T00:00:00.000000Z",
  "url": "https://pubmed.ncbi.nlm.nih.gov/26024342",
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

## DeX Message Sample

### GPM General Events for Publication Added

```
{
    "event_type": "publication_added",
    "schema_version": "2.0.1",
    "date": "2026-05-07 13:24:42",
    "data": {
        "publication_id": "47a6...94d3",
        "type": "review",
        "source_type": "pmid",
        "source_id": "34445366",
        "title": "Current and Future Development in Lung Cancer Diagnosis",
        "authors": [
            "Reem Nooreldeen",
            "Horacio Bach"
        ],
        "journal": "International Journal of Molecular Sciences",
        "published_at": "2021-08-12T00:00:00.000000Z",
        "url": "https:\/\/pubmed.ncbi.nlm.nih.gov\/34445366",
        "meta": {
            "doi": {
                "id": "10.3390\/ijms22168661",
                "link": "https:\/\/doi.org\/10.3390\/ijms22168661"
            },
            "pmid": {
                "id": "34445366",
                "link": "https:\/\/pubmed.ncbi.nlm.nih.gov\/34445366"
            },
            "type": "review",
            "pmcid": {
                "id": null,
                "link": null
            },
            "title": "Current and Future Development in Lung Cancer Diagnosis",
            "authors": [
                "Reem Nooreldeen",
                "Horacio Bach"
            ],
            "journal": "International Journal of Molecular Sciences",
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
                    "type": "review",
                    "source_type": "pmid",
                    "source_id": "34445366",
                    "title": "Current and Future Development in Lung Cancer Diagnosis",
                    "authors": [
                        "Reem Nooreldeen",
                        "Horacio Bach"
                    ],
                    "journal": "International Journal of Molecular Sciences",
                    "published_at": "2021-08-12T00:00:00.000000Z",
                    "url": "https:\/\/pubmed.ncbi.nlm.nih.gov\/34445366",
                    "meta": {
                        "doi": {
                            "id": "10.3390\/ijms22168661",
                            "link": "https:\/\/doi.org\/10.3390\/ijms22168661"
                        },
                        "pmid": {
                            "id": "34445366",
                            "link": "https:\/\/pubmed.ncbi.nlm.nih.gov\/34445366"
                        },
                        "type": "review",
                        "pmcid": {
                            "id": null,
                            "link": null
                        },
                        "title": "Current and Future Development in Lung Cancer Diagnosis",
                        "authors": [
                            "Reem Nooreldeen",
                            "Horacio Bach"
                        ],
                        "journal": "International Journal of Molecular Sciences",
                        "published_at": "2021-08-12"
                    }
                }
            ],
            "icon_url": null,
            "status": "active",
            "visibility": "public",
            "status_date": "2022-02-14T18:47:01+00:00",
            "type": "vcep",
            "coi": "http:\/\/localhost\/coi-group\/9162...8f65",
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

```
{
    "event_type": "publication_deleted",
    "schema_version": "2.0.1",
    "date": "2026-05-07 13:25:18",
    "data": {
        "publication_id": "47a6...94d3",
        "source": "pmid",
        "identifier": "34445366",
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
            "coi": "http:\/\/localhost\/coi-group\/9162...8f65",
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

```
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
                "type": "review",
                "source_type": "pmid",
                "source_id": "34445366",
                "title": "Current and Future Development in Lung Cancer Diagnosis",
                "authors": [
                    "Reem Nooreldeen",
                    "Horacio Bach"
                ],
                "journal": "International Journal of Molecular Sciences",
                "published_at": "2021-08-12T00:00:00.000000Z",
                "url": "https:\/\/pubmed.ncbi.nlm.nih.gov\/34445366",
                "meta": {
                    "doi": {
                        "id": "10.3390\/ijms22168661",
                        "link": "https:\/\/doi.org\/10.3390\/ijms22168661"
                    },
                    "pmid": {
                        "id": "34445366",
                        "link": "https:\/\/pubmed.ncbi.nlm.nih.gov\/34445366"
                    },
                    "type": "review",
                    "pmcid": {
                        "id": null,
                        "link": null
                    },
                    "title": "Current and Future Development in Lung Cancer Diagnosis",
                    "authors": [
                        "Reem Nooreldeen",
                        "Horacio Bach"
                    ],
                    "journal": "International Journal of Molecular Sciences",
                    "published_at": "2021-08-12"
                }
            }
        ],
        "icon_url": null,
        "status": "active",
        "visibility": "public",
        "status_date": "2022-02-14T18:47:01+00:00",
        "type": "vcep",
        "coi": "http:\/\/localhost\/coi-group\/9162...8f65",
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
                "profile_photo": "http:\/\/localhost\/profile-photos\/cG5qlDoTCm28mrGaDJ5AD3aDCAMgwLohUAu4OrrU.png"
            },
            ...
            {
                "uuid": "2b9d...6e4f",
                "first_name": "Stuart",
                "last_name": "Tompson",
                "roles": [],
                "additional_permissions": [],
                "institution": "University of Wisconsin-Madison",
                "credentials": [
                    "PhD",
                    "BSc"
                ],
                "code_of_conduct": {
                    "status": "missing",
                    "current_version": "1.0.0",
                    "completed_at": null,
                    "expires_at": null,
                    "days_remaining": null
                },
                "profile_photo": "http:\/\/localhost\/profile-photos\/QD1LQI2nccP3LoLmVxmpSpwdWEVDu9Upjf5DJGNK.png"
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