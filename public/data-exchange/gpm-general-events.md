# GPM General Events Stream
The `gpm-general-events` DataExchange topic publishes event messages about ClinGen groups and group membership in the GPM.  
For messages about people and their profiles see the `gpm-person-events`.

This topic is intended for use by other ClinGen applications that would like to monitor groups in the GPM and/or keep their own records synchronized with the GPM.

This topic is currently subscribed to by:
* CSPEC Registry

## Messages

### Structure
Each message in this topic will have:
* `event_type`: A string defining the type of event.
* `schema_version`: A string representing the semantic version of the message.
* `data`: An object with the event data for the group.  The structure varies based on event type. See [gpm-general-events.json](./gpm-general-events.json) for details.

Required attributes are **bolded**.

### Event types
Event types in this topic include
 * `ep_definition_approved` -  Membership and Scope has been give first approval by the CDWG OC. and VCEPs are ready to start drafting specifications
 * `vcep_draft_specifications_approved` -  Drafts for all scopes in VCEP specifications have been approved in the CSPEC Registry for all gene/diseases in the approved scope.
 * `vcep_pilot_approved` -  All specification scopes and pilot classifications for gene/diseases in the approved scope have been given final approval in the CSPEC Registry.
 * `sustained_curation_approved` -  Step 4 approval for VCEPS respectively.  Indicates plans for review have been approved and all necessary attestations have been completed by approved membership.  EP is now fully approved.
 * `gene_added` -  CDWG OC has approved an updated scope and gene list for a VCEP
 * `gene_removed` -  CDWG OC has approved an updated scope and gene list for a VCEP
 * `member_added` -  EP has added a new member.
 * `member_removed` -  EP has removed a new member completely.
 * `member_retired` -  EP has retired a member, who is now considered alumni.
 * `member_role_assigned` -  Role given to user.
 * `member_role_removed` -  Role removed from user.
 * `member_permission_granted` -  A new group permission has ben granted to the group member.
 * `member_permission_revoked` -  A group permission has been revoked from the group member.
 * `account_updated` -  GPM user has updated their email or they have been their roles/permissions have changed.
 * `profile_updated` -  ClinGen Member has updated their profile.

### Event Schema
The full JSON schema can be found at [gpm-general-events.json](./gpm-general-events.json).

## Questions & Comments
Questions and comments and issues can be directed to TJ Ward or via github issues on this repository.
