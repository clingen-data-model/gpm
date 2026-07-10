# GPM DX Message Release Notes

This document tracks schema/message changes for GPM DX messages. New releases should be added at the top.

---

## v2.0.2

### Summary

Schema version `2.0.2` adds group-level Affiliation ID support to GPM DX messages.

The main purpose of this release is to support Affiliation IDs for all group types, including non-Expert Panel groups such as Working Groups, CDWGs, and SC-CDWGs, while keeping the message compatible with downstream systems that currently consume the `2.0.1` Expert Panel structure.

Affected topic:
- gpm-general-events
- gpm-checkpoint-events

---

### Affiliation ID moved from Expert Panel level to Group level

GPM now treats Affiliation ID as a group-level field.

Preferred location going forward: `data.group.affiliation_id`

Example:

```json
{
  "group": {
    "uuid": "9c46d40a-a5c6-4e4c-932a-09d4ae66bc6e",
    "affiliation_id": "40104",
    "name": "Hereditary Cardiovascular Disease",
    "type": "gcep"
  }
}
```

For Expert Panel groups, Affiliation ID is temporarily included in both places:

```json
{
  "group": {
    "affiliation_id": "40104",
    ...
    "expert_panel": {
      "affiliation_id": "40104"
      ..
    }
  }
}
```

Compatibility field for EP groups: `data.group.expert_panel.affiliation_id` <br />
This is included for compatibility with downstream systems that currently read `expert_panel.affiliation_id`. <br />
For non-EP groups, Affiliation ID only exists under `data.group.affiliation_id` because those groups do not have an `expert_panel` object.

GPM now generates `6XXXX` Affiliation IDs for these non-EP group types:

- Working Groups
- CDWGs
- SC-CDWGs

Existing non-EP groups will receive generated IDs as part of the release process. Newly created WG/CDWG/SC-CDWG groups will receive an Affiliation ID automatically during group creation. <br />
Once generated, these IDs are intended to be permanent. In the UI, they are treated as read-only for normal users. Backend correction is limited to system-level `super-user` access.

---

### New event type for Affiliation ID updates

Affiliation ID updates now use a dedicated event type: `group_affiliation_id_updated` <Br />
Previously, Affiliation ID updates for Expert Panels were included under: `ep_info_updated` <br />
Starting with `2.0.2`, `ep_info_updated` should continue to be used for Expert Panel information updates, such as EP name or base-name changes. Affiliation ID changes should be handled through `group_affiliation_id_updated`. <br />
Related topic: `gpm-general-events`

---

### Additional notes

Although `expert_panel.affiliation_id` is still included for Expert Panel groups in `2.0.2`, downstream systems should avoid depending on it going forward. <br />
Please start adjusting downstream consumers to read Affiliation ID from: `data.group.affiliation_id` <br />
The nested Expert Panel field: `data.group.expert_panel.affiliation_id` is preserved only for backward compatibility and may be deprecated in a future major schema update.