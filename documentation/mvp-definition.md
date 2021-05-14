
# MVP Definition
The goal of the MVP is to consolidate the tracking of EPs moving through the steps of the application process and make that information readily available to users managing the process and those that would simply like an at-a-glance update.

## Use Cases
Note: use cases are numbered for reference and do not necessarily denote an "order of operations" except where preconditions are given.

## Minimal use cases
This set of minimal use cases assumes that administrators will make all changes updates to the **Expert Panel** application and statuses. 

1. An *administrator* logs in.
1. Given a group that is ready to apply to be a ClinGen **ExpertPanel**, an *adminstrator* creates a new **ExpertPanel** with a working name, one or more contacts (name, email), and associated with an appropriate CDWG
2. Given an **Expert Panel** has been created...
    1. A customized **COI** url shold be created in _SurveyMonkey_
    3. A link to the [coordinator resource](https://docs.google.com/document/d/1GeyR1CBqlzLHOdlPLJt0uA29Z-2ysmTX1dtH9PDmqRo) is sent to the coordinator email.
    3. An *administrator* creates a **Required Action** note.
4. An *administrator* adds Full base name, short base name, and **EP contacts** (name, email, phone) to an **Expert Panel**
4. An *administrator* uploads a finished **Application** for an **Expert Panel**.
5. An *administrator* enters the date the **Application** was recieved.
5. An *administrator* marks the application reviewed.
5. Given an *administrator* has marked an application reviewed, he or she can mark the **Application** 'ready for approval' CDWG OC for approval.
1. Given an **Application** has been submitted for approval, an *adminstrator* sends it to CDWG OC for approval
2. When the CDWG OC approves the **Application**, an *administrator* marks the **Application** approved with "group definition approval date".
9. Given an **Application** has been approved
    1. all contacts for the EP will be sent the approval email (see [GCEP/VCEP Approval Checklist](https://docs.google.com/document/d/1OvECRLrxa7NeqxrFUsrLMz6qxPUkq5zxa9pu6vCdbJQ) for details)
    1. An *administrator* adds an GCI/VCI affiliation id to th **Expert Panel**
11. Given the **Expert Panel** is approved and is a *VCEP*...
    1. an *administrator* can enter a **Progress Log Entry** for ACMG/AMP Specifications Draft.
    2. an *adminstrator* sets the **Expert Panel**'s "draft specifications approved date"
13. Given the **Expert Panel** is an approved VCEP and has approved draft specifications... 
        1. an *administrator* enters a **Progress Log Entry** for Final ACMG/AMP Specifications.
        1. an *administrator* sets the **Expert Panel**'s "final specifcations approved date".
14. Given the **Expert Panel** is an approved VCEP with approved final specifications...
    1. an *administrator* sets **Expert Panel**'s 'step 4 application recieved date
    2. an *administrator* uploads the **Step 4 Application**
14. Given the **Expert Panel** is an approved VCEP and it's step 4 application has been received an *administrator* can set **Expert Panel**'s 'step 4 application approval date'
1. A logged in *administrator*...
    1. lists of all **Expert Panels**s, their **Application** progress, and next **Action**.
    1. Views the details of an **Expert Panel** including


## Additional Feature Use cases

### Automating Step 1 Application Approval:
1. Given an **Application** has been submitted for approval, CDWG OC members will be notified that the **Application** is ready for approval.
7. Given an **Application** has been submitted for approval, CDWG OC members can vote to approve (or not) the **Application**.
8. Given an **Application** has been submitted for approval, CDWG OC members can make comments on the **Application**
9. Given OC votes to approve, the **Application** will be marked as approved.

### Interim support draft/final specifications review and approval:
1. Given the **Expert Panel**'s **Application** has been approved and the **Expert Panel** is a *VCEP*, an *administrator* will upload **Specifications Draft** with a version number.


### Coordinator user accounts and EP application management
1. Given an **ExpertPanel** has been created, an *adminstrator* adds a *coordinator* group member.
1. Given a *coordinator has been added to an **Expert Panel**, they will receive an email telling them how to log into the system.
2. Given an **Expert Panel** has been created, the *coordinator* adds a new **Person** to the EP.
    * A person has a first name, last name, email address, an orcid, an institution
    * Relative to the group, a **Person** has an expertise, and a role.
3. Given an existing **Person**, the *coordinator* should be able to add the **Person** to the **Expert Panel** and declare their expertise and role.
4. Given a **Person** is added to an **Expert Panel**, that **Person** should be emailed a link to a **COI** form.
4. The *coordinator* adds Full base name, short base name, scope of work,  to an **Expert Panel**
4. The *coordinator* indicates one or more members are contacts for the **Expert Panel**
4. A *coordinator* submits their **Application** for approval.
5. An *administrator* marks the **Application** reviewed.
6. A *coordinator* can see a list of all **Expert Panels** they are coordinating navigate to any of them.