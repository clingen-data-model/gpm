# GPM ClinGen DataExchange (DeX) Implementation

> Purpose: This document is the single source of truth for GPM's Data Exchange (DX) integration. It consolidates and supersedes the content previously kept in dx-notes.

## Overview
GPM integrates with ClinGen's Data Exchange (Kafka) to publish and consume domain events. All topics are configured via config/dx.php, and (currently) all topics are configured to use a single partition.

## Configuration

DX authentication, brokers, topics, and schema versions are configured in config/dx.php. Environment variables are read there.
- See: `config/dx.php`
- Typical configuration includes: brokers, SASL/SSL credentials, and a map of topic names to schema versions (e.g., dx.schema_versions.gpm-general-events).


## Core Concepts
- **`MessageStream` (contract)** : Abstraction that consumes messages from one or more topics and yields `DxMessage` DTOs.
    - Default binding: `KafkaMessageStream` ↔︎ `MessageStream`.
- **`DxMessage` DTO**: Neutral representation of a DX message (event type, payload, schema version, timestamp, headers, etc.).
- **`MessageProcessor` (contract)**: Service whose `handle(DxMessage $message): DxMessage` performs processing.
    - Default binding: `IncomingMessageProcessor` ↔︎ `MessageProcessor`.
    - Delegates persistence to `IncomingMessageStore` (creates a `StreamMessage` model) and dispatches message-specific logic via `MessageHandlerFactory`.
- **`StreamMessage` (model)**: Local record of each consumed/published DX message for traceability and retries.

## Publishing (Producing) Messages

Messages are constructed in `DxMessageFactory` from domain `PublishableEvent` instances.
```
// Fields used when building a DxMessage from a PublishableEvent
$dx = [
    'eventType'     => $event->getEventType(),
    'message'       => $event->getPublishableMessage(),
    'schemaVersion' => null, // populated from config: dx.schema_versions.gpm-general-events (or topic-specific)
    'date'          => $event->getLogDate(),
];
```
- Schema/representation is driven by the event’s getPublishableMessage() output.
- Topics
    - gpm-general-events: receives events from subclasses of ExpertPanelEvent and GroupEvent.
    - gpm-person-events: receives events from classes using the trait App\Modules\Person\Events\Traits\PublishesEvent.
> The concrete topic used is determined by the event type and factory routing rules.

## Consuming Topics
GPM consumes messages from subscribed topics on a scheduled cadence (hourly).
Use `App\DataExchange\Actions\DxConsume` to programmatically consume topics:
```
use App\DataExchange\Actions\DxConsume;

$consume = app()->make(DxConsume::class);
$consume->handle(['topic_1', 'topic_2']);
```
### Processing pipeline
1. Topics are added to a `MessageStream` (Kafka consumer).
1. Each consumed record is deserialized into a `DxMessage`.
1. `IncomingMessageProcessor` persists a `StreamMessage` via `IncomingMessageStore`.
1. `MessageHandlerFactory` resolves and invokes the handler for the specific event type.

### Default bindings
- `MessageStream` → `KafkaMessageStream`
- `MessageProcessor` → `IncomingMessageProcessor`
### Error handling
- If the affiliation referenced by the message does not exist or the application status is incompatible, a DataSynchronizationException is thrown, the error is logged, and the message remains unprocessed for later inspection.

## Actions on CSPEC Messages

When CSPEC-related events are received, the following actions occur. If prerequisites (e.g., valid affiliation and status) are not met, processing is aborted as described above.

| Event Type | Action Taken | Action Class |
|:---:|:---|:---:|
| classified-rules-approved | Marks Draft Rules step (2) as approved. | App\DataExchange\Actions\ClassifiedRulesApprovedProcessor |
| pilot-rules-approved | Marks Pilot Rules step (3) as approved if not already approved; otherwise, assigns a task to review sustained curation responses. | App\DataExchange\Actions\ClassifiedRulesApprovedProcessor |

> Note: The same processor currently handles both event types.

## Activity Logging
Operational activity is recorded in the activity_log table using Spatie Activitylog.
- `activity_type` is typically the kebab-cased class name of each RecordableEvent.
- `StepEvent` overrides `activity_type` based on the step number (1–4).

## Operational Notes
- **Scheduling**: An hourly scheduler triggers DX consumption.
- **Traceability**: All consumed and published messages are captured as `StreamMessage` records.
- **Schema Versions**: Managed centrally in `config/dx.php` under `dx.schema_versions.*`.

## Appendix: Key Classes
- Publishing
    - DxMessageFactory
    - PublishableEvent (and domain subclasses)
    - ExpertPanelEvent, GroupEvent
    - App\Modules\Person\Events\Traits\PublishesEvent
- Consuming
    - App\DataExchange\Actions\DxConsume
    - KafkaMessageStream (↔︎ MessageStream)
    - IncomingMessageProcessor (↔︎ MessageProcessor)
    - IncomingMessageStore
    - MessageHandlerFactory
    - StreamMessage
- Errors
    - DataSynchronizationException

## What we’re publishing
Base data.group snapshot comes from GroupEvent::getPublishableMessage():
```
{ 
    id, 
    name, 
    description, 
    status, 
    type, 
    parent_group?, 
    ep_id?, 
    affiliation_id?, 
    scope_description?, 
    short_name?, 
    cspec_url? 
}
```

### EP / Group / Application / Membership (topic: gpm-general-events)

| Class (file) | Parent | Recordable | Publishable | Event Type | Sample data additions (besides "group": {...}) |
|:---|:---|:---:|:---:|:---|:---|
| app/Modules/ExpertPanel/Events/StepApproved.php | ExpertPanelEvent | yes | yes | ep_definition_approved | members: [...], scope: { statement, genes: [...] } |
| (same) |  |  |  | vcep_draft_specifications_approved | (no extra fields; base group) |
| (same) |  |  |  | vcep_pilot_approved | (no extra fields; base group) |
| (same) |  |  |  | ep_final_approval | (no extra fields; base group) |
| app/Modules/ExpertPanel/Events/StepDateApprovedUpdated.php | ExpertPanelEvent | yes | yes | step_date_approved_updated | (base group only in DX payload) |
| app/Modules/Group/Events/ExpertPanelAffiliationIdUpdated.php | GroupEvent | yes | yes | ep_info_updated | (base group only in DX payload) |
| app/Modules/Group/Events/ExpertPanelNameUpdated.php | GroupEvent | yes | yes | ep_info_updated | (base group only) |
| app/Modules/ExpertPanel/Events/ExpertPanelAttributesUpdated.php | ExpertPanelEvent | yes | yes | expert_panel_attributes_updated (default) | Implements PublishableEvent; if used, would go to gpm-general-events. Not seen |
| app/Modules/Group/Events/GenesAdded.php | GeneEvent → GroupEvent | yes | yes | gene_added* | genes: [{ hgnc_id, gene_symbol, (mondo_id?, disease_name?, disease_entity?) }] |
| app/Modules/Group/Events/GeneRemoved.php | GeneEvent → GroupEvent | yes | yes | gene_removed | genes: [{ hgnc_id, gene_symbol, (mondo_id?, disease_name?, disease_entity?) }] |
| app/Modules/Group/Events/MemberAdded.php | GroupMemberEvent → GroupEvent | yes | yes | member_added | members: [{ id, first_name, last_name, email, group_roles[], additional_permissions[] }] |
| app/Modules/Group/Events/MemberRemoved.php | GroupMemberEvent → GroupEvent | yes | yes | member_removed | members: [ … ] |
| app/Modules/Group/Events/MemberRetired.php | GroupMemberEvent → GroupEvent | yes | yes | member_retired | members: [ … ] |
| app/Modules/Group/Events/MemberUnretired.php | GroupMemberEvent → GroupEvent | yes | yes | member_unretired | members: [ … ] |
| app/Modules/Group/Events/MemberRoleAssigned.php | GroupMemberEvent → GroupEvent | yes | yes | member_role_assigned | members: [ … ] |
| app/Modules/Group/Events/MemberRoleRemoved.php | GroupMemberEvent → GroupEvent | yes | yes | member_role_removed | members: [ … ] |
| app/Modules/Group/Events/MemberPermissionsGranted.php | GroupMemberEvent → GroupEvent | yes | yes | member_permission_granted | members: [ … ] |
| app/Modules/Group/Events/MemberPermissionRevoked.php | GroupMemberEvent → GroupEvent | yes | yes | member_permission_revoked | members: [ … ] |
| app/Modules/Group/Events/MemberUpdated.php | GroupEvent |yes | yes | member_updated | members: [ … ]
| app/Modules/Group/Events/ScopeDescriptionUpdated.php | GroupEvent |yes | yes | scope_description_updated | (base group only)
| app/Modules/Group/Events/GroupDescriptionUpdated.php | GroupEvent |yes | yes | group_description_updated | (base group only)
| app/Modules/Group/Events/GroupStatusUpdated.php | GroupEvent |yes | yes | group_status_updated | (base group only)
| app/Modules/Group/Events/ParentUpdated.php | GroupEvent |yes | yes | parent_updated | (base group only)
| app/Modules/ExpertPanel/Events/CoiCompleted.php | GroupEvent |yes | yes | coi_completed | (base group only)


**Note on genes**: The current class name is GenesAdded (plural). GroupEvent::getEventType() would normally emit genes_added, but the data on DB shows gene_added. That tells us a historical normalization or an earlier class name produced gene_added. The live outbox should be treated as source of truth for the event type string consumers expect.

### Person (topic: gpm-person-events)
| Class (file) | Parent | Recordable | Publishable | Event Type | Sample data additions (besides "group": {...}) |
|:---|:---|:---:|:---:|:---|:---|
| app/Modules/Person/Events/PersonCreated.php | PersonEvent | yes | yes | person_created | person: $person->getAttributes() |
| app/Modules/Person/Events/ProfileUpdated.php | PersonEvent | yes | yes | person_updated | person: { …changed fields… } |
| app/Modules/Person/Events/PersonDeleted.php | PersonEvent | yes | yes | person_deleted | person: {} |

### Minimal sample payloads
gene_added
```
{
    "event_type":"gene_added",
    "schema_version":"1.1.0",
    "date":"2025-06-10 20:46:26",
    "data":
    {
        "expert_panel":
        {
            "id":"c21de5f9-896e-4204-80c7-65f35ff964b2",
            "name":"Childhood, Adolescent and Young Adult Cancer Predisposition GCEP",
            "type":"gcep",
            "affiliation_id":"40157"
        },
        "genes":
        {
            "120":
            {
                "hgnc_id":3446,
                "gene_symbol":"ERG"
            },
            "121":
            {
                "hgnc_id":17904,
                "gene_symbol":"DROSHA"
            },
            "122":
            {
                "hgnc_id":12632,
                "gene_symbol":"USP9X"
            }
        }
    }
}
```

member_added
```
{
    "event_type":"member_added",
    "schema_version":"1.9.9",
    "date":"2025-07-11 15:26:56",
    "data":
    {
        "group":
        {
            "id":"bdfc67d3-58fc-460c-8f43-c4dc72b2d24c",
            "name":"Severe Combined Immunodeficiency Disease",
            "description":"<p style=\"margin-left:0in;margin-right:0in;\">Severe Combined Immune Deficiency (SCID) represents ...",
            "status":"active",
            "type":"vcep",
            "parent_group":
            {
                "id":"a6a8a8d6-9c86-4874-a34c-ca9ecdf66d25",
                "name":"Immunology",
                "description":"<p>The Immunology CDWG aims to curate ...",
                "status":"active",
                "type":"cdwg"
            },
            "ep_id":"bdfc67d3-58fc-460c-8f43-c4dc72b2d24c",
            "affiliation_id":"50091",
            "scope_description":null,
            "short_name":"SCID",
            "cspec_url":"50091"
        },
        "members":
        [
            {
                "id":"28ad4399-5474-4d66-b6e5-fdbca08961c8",
                "first_name":"Joshi",
                "last_name":"Stephen",
                "email":"Joshi.Stephen@bcm.edu",
                "group_roles":[],
                "additional_permissions":[]
            }
        ]
    }
}
```

ep_info_updated
```
{
    "event_type":"ep_info_updated",
    "schema_version":"1.9.9",
    "date":"2025-06-26 23:01:06",
    "data":
    {
        "group":
        {
            "id":"d6cd4077-4c79-4c45-a826-1e394e0a5b5c",
            "name":"Propionic Acidemia and Methylmalonic Acidemia",
            "description":null,
            "status":"applying",
            "type":"vcep",
            "parent_group":{"id":"2e312275-a400-47ab-a34c-cad7698703cf",
            "name":"Inborn Errors Metabolism",
            "description":null,
            "status":"active",
            "type":"cdwg"},
            "ep_id":"d6cd4077-4c79-4c45-a826-1e394e0a5b5c",
            "affiliation_id":"50162",
            "scope_description":"<p>The ClinGen PA\/MMA VCEP will ...",
            "short_name":"PA\/MMA",        
            "cspec_url":"50162"
        }
    }
}
```

ep_definition_approved (Step 1)
```
{
    "event_type":"ep_definition_approved",
    "schema_version":"1.9.9",
    "date":"2025-08-11 04:00:00",
    "data":
    {
        "group":
        {
            "id":"dafe65f6-7baa-4828-943e-487b3775e8c5",
            "name":"Long Test Name",
            "description":null,
            "status":"applying",
            "type":"gcep",
            "ep_id":"190d264a-060c-4868-9946-ff5f4d88d283",
            "affiliation_id":"43320",
            "scope_description":"<div><p>no description needed<\/p><\/div>",
            "short_name":"Short Test Name"
        },
        "members":
        [
            {
                "id":"34f7df1c-32d4-42de-8a5d-60df0c2aeafc",
                "first_name":"Agung",
                "last_name":"Setiadha",
                "email":"setiadha@email.unc.edu",
                "group_roles":
                [
                    "coordinator",
                    "chair",
                    "biocurator",
                    "expert",
                    "core-approval-member",
                    "biocurator-trainer",
                    "grant-liaison",
                    "annotator",
                    "civic-editor"
                ],
                "additional_permissions":[]
            }, {
                "id":"462a598e-f1fc-4a2f-8535-e8388de71023",
                "first_name":"Bradford",
                "last_name":"Worrall",
                "email":"bbw9r@uvahealth.org",
                "group_roles":["coordinator",
                "chair",
                "biocurator",
                "expert",
                "core-approval-member",
                "biocurator-trainer",
                "grant-liaison",
                "annotator",
                "civic-editor"],
                "additional_permissions":[]
            }
        ],
        "scope":
        {
            "statement":"<div><p>no description needed<\/p><\/div>",
            "genes":
            [
                {
                    "hgnc_id":11335,
                    "gene_symbol":"SSX1"
                }
            ]
        }
    }
}
```

person_updated (person topic)
```
{
    "event_type":"person_updated",
    "schema_version":"1.9.9",
    "date":"2025-08-10 20:46:07",
    "data":
    {
        "person":
        {
            "id":"595a30ed-26c2-4440-a1f0-ec371889fd64",
            "first_name":"TJ",
            "last_name":"Ward",
            "email":"jward3@email.unc.edu",
            "institution":
            {
                "id":"58558d12-44e8-416e-a703-82998ed8f753",
                "website_id":4183,
                "name":"University of North Carolina",
                "abbreviation":null,
                "url":"https:\/\/www.unc.edu\/",
                "address":"",
                "country":{"id":226,
                "name":"United States",
                "created_at":"2022-02-14T23:47:18.000000Z",
                "updated_at":"2022-02-14T23:47:18.000000Z"
            }
        },
        "credentials":
        [
            {
                "id":3,
                "name":"MSc",
                "approved":true,
                "created_at":"2022-10-12T20:42:34.000000Z",
                "updated_at":"2022-10-12T20:42:34.000000Z"
            }
        ],
        "biography":null,
        "profile_photo":"http:\/\/localhost:8081\/profile-photos\/Pdv6i85LGKM5YcutT8WN76Et0YTLy2GBS0lSFOua.png",
        "orcid_id":null,
        "hypothesis_id":null,
        "timezone":"America\/New_York"
        }
    }
}
```

- schema_version defaults from config('dx.schema_versions.gpm-general-events').
- The topic is selected per event:
    - Group/ExpertPanel/Application → gpm-general-events
    - Person → gpm-person-events

### Base Group Snapshot
Most general-topic events include a current-state group snapshot (see App\Modules\Group\Events\GroupEvent::getPublishableMessage()):
```
"data": {
  "group": {
    "id": "<group uuid>",
    "name": "<group name>",
    "description": "<html or null>",
    "status": "<status name>",
    "type": "<wg|cdwg|gcep|vcep>",
    "parent_group": { /* same shape, recursively, if present */ },
    "ep_id": "<expert_panel uuid, if EP>",
    "affiliation_id": "<string or null, if EP>",
    "scope_description": "<html, if EP>",
    "short_name": "<string, if EP>",
    "cspec_url": "<string; used for vcep>"
  }
}
```
Note: Our DX payloads are not diffs. They reflect the current state at publish time. Old/new values live in activity logs.

## Appendix: Relevant Code Anchors
- Envelope: App\DataExchange\MessageFactories\DxMessageFactory
- Publisher: App\Actions\EventPublish
- Outbox model: App\DataExchange\Models\StreamMessage
- Base events:
    - App\Modules\Group\Events\GroupEvent
    - App\Modules\ExpertPanel\Events\ExpertPanelEvent
    - App\Modules\Person\Events\PersonEvent
- Publishable interface: App\Events\PublishableEvent
- Member/Gene mappers: App\Modules\Group\Events\Traits\IsPublishableApplicationEvent
Person topic trait: App\Modules\Person\Events\Traits\PublishesEven

## Changelog
- 2025‑08‑18: Consolidated dx-notes into this document; clarified publishing flow, topic routing, and CSPEC actions; fixed typos and class references.


