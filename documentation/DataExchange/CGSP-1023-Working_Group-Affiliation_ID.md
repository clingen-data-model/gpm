# CGSP-1023 Working Group Affiliation ID

Right now, Affiliation ID lives on expert_panels, which made sense when only GCEP, VCEP, and SC-VCEP groups had Affiliation IDs. But if Affiliation ID now applies to all group types, then the cleaner long-term model is to move it up to groups.

The plan is to move affiliation_id to the groups table, backfill existing EP group IDs from expert_panels, update the code to read from groups.affiliation_id, and then generate new IDs for non-EP groups in GPM. For non-EP groups, the generated ID would be five digits, numeric only, and start with 6, so something like 6XXXX, and expose a single affiliation_id property at the group level.

Working Group Affiliation ID scope include Working Group, CDWG, and SC-CDWG. 

#### GPM CHECKPOINT EVENT DX SCHEMA MESSAGES FORMAT

```
{
  "event_type": "group_checkpoint_event",
  "schema_version": "2.0.1",
  "date": "2026-06-17 19:20:18",
  "data": {
    "uuid": "c828...0ff2",
    "affiliation_id": "50998", ------------------------------------> NEW AFFILIATION ID MOVED UP HERE
    "name": "SCVCEP Long Base Name 1",
    "description": "Morbi ... non.",
    "excerpt": null,
    "publications": [],
    "status": "active",
    "visibility": "public",
    "status_date": "2022-02-14T23:47:01+00:00",
    "type": "scvcep",
    "coi": "https://gpm...edu/coi-group/c828...0ff2",
    "members": [
      {
        "uuid": "3e7c...f481",
        "first_name": "Daniel",
        "last_name": "Gale",
        "roles": [ ... ],
        "additional_permissions": [],
        "institution": "University College London",
        "credentials": [ ... ],
        "code_of_conduct": {
          ...
        },
        "email": "d...@...uk"
      },
      ...
    ],
    "expert_panel": {
      "uuid": "c828...0ff2",
      "affiliation_id": "50998", ------------------------------------> DEPRECATED
      "name": "SCVCEP Long Base Name 1",
      "short_name": "SCVCEP Short1",
      "scope_description": "Curabitur... risus.",
      "membership_description": "Phasellus... dictum.",
      "type": "scvcep",
      "date_completed": "2026-06-20T04:00:00.000000Z",
      "inactive_date": null,
      "current_step": 4,
      "all_genes": [
        {
          ...
        },
        ...
      ],
      "clinvar_org_id": null,
      "vcep_definition_approval": "2026-06-17T04:00:00.000000Z",
      "vcep_draft_specification_approval": "2026-06-18T04:00:00.000000Z",
      "vcep_pilot_approval": "2026-06-19T04:00:00.000000Z",
      "vcep_final_approval": "2026-06-20T04:00:00.000000Z",
      "funding_awards": [],
      "scvcep_final_specification_documents": [
        {
          "uuid": "82d1...695f",
          "filename": "DATA 740 Syllabus.pdf",
          "download_url": "https://gpm...edu/downloads/groups/c828...0ff2/final-specifications/82d1...695f"
        },
        ...
      ]
    },
    "parent": {
      "uuid": "0016...747c",
      "name": "SCCDWG Test 1",
      "type": "sccdwg",
      "status": "active"
    }
  }
}
```

#### GPM GENERAL EVENTS DX SCHEMA MESSAGES FORMAT

```
{
  "event_type": "member_role_assigned", ------------------------------------> THIS EVENT TYPE JUST AN EXAMPLE FROM SO MANY GPM GENERAL EVENTS THAT WE HAVE. FOR A COMPLETE LIST PLEASE REFERS TO THE OTHER DOCUMENTATION
  "schema_version": "2.0.1",
  "date": "2026-06-30 05:32:06",
  "data": {

    --------------- THIS PART IS FOR THE DATA THAT HAS BEEN CHANGED IN GPM ---------------

    "members": [
      {
        "uuid": "8880...0c03",
        "first_name": "Maria Isabel",
        "last_name": "Achatz",
        "roles": [ ... ],
        "additional_permissions": [],
        "institution": "Hospital Sírio-Libanês",
        "credentials": [ ... ],
        "code_of_conduct": {
            ...
        },
        "email": "m...@...com"
      }
    ],
    "roles": [
      "Coordinator",
      "Chair"
    ],

    --------------- THIS PART IS ALWAYS CONSISTENT FOR GPM-GENERAL-EVENTS ---------------
    "group": {
      "uuid": "c21d...64b2",
      "affiliation_id": "40157", ------------------------------------> NEW AFFILIATION ID MOVED UP HERE
      "name": "Childhood,... research.",
      "excerpt": null,
      "publications": [],
      "status": "active",
      "visibility": "public",
      "status_date": "2022-02-14T23:47:01+00:00",
      "type": "gcep",
      "coi": "https://gpm...edu/coi-group/c21d...64b2",
      "expert_panel": {
        "uuid": "c21d...64b2",
        "affiliation_id": "40157", ------------------------------------> DEPRECATED
        "name": "Child...sition",
        "short_name": "CAYA",
        "scope_description": "Technological ...research.",
        "membership_description": null,
        "type": "gcep",
        "date_completed": "2024-06-18T04:00:00.000000Z",
        "inactive_date": null,
        "current_step": 1,
        "gcep_final_approval": "2024-06-18T04:00:00.000000Z",
        "funding_awards": []
      },
      "parent": {
        "uuid": "3ef7...35a3",
        "name": "Hereditary Cancer",
        "type": "cdwg",
        "status": "active"
      }
    }
  }
}
```