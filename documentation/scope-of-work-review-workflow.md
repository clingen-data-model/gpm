# Scope of Work / application review workflow review

Reviewed branch: `CGSP-756-gcep-scope-of-work`. Legacy reference: local `main` at `b1457a8dc`. Existing uncommitted comment-summary, Review History, and attestation changes were preserved. No migration was added.

## 1. Legacy email and logging behavior

On main, `ApplicationSubmissionReject::asController` sends the edited request-revision email through `NotifyContacts` → `MailSendUserDefined` → `UserDefinedMailable`. `MessageSent` invokes `StoreMailInDatabase`, creating the Mail Log record and linking it to recipient people. The default email is built by `ApplicationRevisionRequestTemplate` and `revision_request.blade.php`.

The rejection action also passes the outgoing body into `Submission::reject` and `ApplicationRevisionsRequested`. The event's description includes that body, so the email appears in the group activity history as part of the revision-request activity. `RecordEvent` creates that activity. The current Application Log UI and Group Log both fetch the group's `activity-logs` endpoint, so the group activity appears in both views.

Initial approval and approval after revision rounds both use `StepApprove`, with the step-specific template (`InitialApprovalMailTemplate` for step 1; `SustainedCurationApprovalMailTemplate` for step 4). They send through the same `NotifyContacts` path and preserve `ApplicationStepApprovedNotification`. `StepApproved` records the approval activity. Main archives the outgoing approval email in Mail Log, but does **not** copy its full body into a separate group activity entry.

## 2. Bypassed behavior and resulting logging

The separate reviewer-note feature changed initial request revisions to pass the note, rather than the email body, into `ApplicationRevisionsRequested`. Mail Log still worked, but the outgoing email body was no longer represented in that activity. The SoW path used `ScopeOfWorkReviewCompleted` instead and likewise did not include the outgoing email body.

`NotifyContacts` now emits one `ApplicationEmailSent` event after sending. The established `RecordEvent` listener records the edited subject/body against the group, and the existing Mail Log listener remains unchanged. This restores email visibility for the separate-note workflows and adds the same visibility for approvals. Ordinary step-4 rejection still embeds its email in its legacy revision activity, so that path explicitly skips the additional email activity to prevent duplication.

| Workflow | Sending action / template | Activity and mail history |
| --- | --- | --- |
| Initial request revisions, including later rounds | `ApplicationSubmissionReject` / `ApplicationRevisionRequestTemplate` | Reviewer note in `ApplicationRevisionsRequested`; edited email in one `ApplicationEmailSent`; existing Mail Log archive |
| Initial approval, including approval after resubmission | `StepApprove` / existing step-specific approval template | Existing `StepApproved`; one email activity when notification is enabled; existing Mail Log and contact notification |
| SoW request revisions, including later rounds | `ApplicationSubmissionReject` / `ScopeOfWorkRevisionRequestTemplate` | Existing `ScopeOfWorkReviewCompleted`; one email activity; existing Mail Log archive |
| SoW approval, including approval after resubmission | `RevisionSubmissionApprove` / existing step-1 or step-4 approval template | Existing `ScopeOfWorkReviewCompleted`; one email activity when enabled; existing Mail Log archive |
| Initial submission/resubmission | `ApplicationSubmitStep` | Existing `ApplicationStepSubmitted`, receipt/admin notifications, snapshots and next actions |
| SoW submission/resubmission | `RevisionSubmit` | Same existing submission event/listeners; explicit SoW label and submission/version metadata |

Activity entries above are visible through the shared Application Log / Group Log endpoint. Mail Log is a separate archive, not an extra copy in either activity list.

## 3. Request Revisions: note, email, and log

For initial scope/membership applications and SoW submissions, `submission.response_content` remains exclusively the reviewer note. The separately edited `body` is sent to contacts and retained in email history. Review History and the status banner continue reading the note. The final email is never concatenated into or substituted for the note.

## 4. Reviewer-note initialization

`ApplicationReviewFeedback` centralizes selection of unresolved Required Revision and Suggestion comments. The draft endpoint returns a plain-text `reviewer_note`, grouped under Required Revisions and Suggestions. `RejectStepForm` initializes the textbox once per form session. Subsequent note edits, including intentionally clearing it, survive email reloads or notification toggles. Editing either value does not synchronize it with the other.

## 5. Missing SoW approval checkbox

`ScopeOfWorkApproveForm` contained only the approval date, and `RevisionSubmissionApprove` accepted only the date. Neither had email support. Both initial and SoW forms now use `ApprovalNotificationFields`, which loads the existing mail draft and renders `UserDefinedMailForm`. An unchecked option sends no approval email. A checked option supports edits and attachments. Loading failures block submission until retry or unchecking. SoW approval uses the normal templates and `NotifyContacts`; it does not advance `current_step` or rewrite application progress.

## 6. Approval comments

The initial and sustained-curation approval templates use `ApprovalMailTemplate` and the same `ApplicationReviewFeedback` selector and Blade partial as request revisions. Only unresolved Required Revision and Suggestion comments appear. Internal and resolved comments are excluded. The resulting email remains editable.

The existing step managers and SoW review guards permit approval with unresolved Required Revision comments. No approval validation was weakened, and preparing or sending mail does not resolve comments.

## 7. SoW submission visibility

Inspection did not find a missing submission event: `RevisionSubmit` already dispatches `ApplicationStepSubmitted`. Its generic label did not identify the SoW revision. The Group Log endpoint also deduplicated automatic activities by type and minute, hiding separate rapid submissions of the same type.

The existing event now labels SoW submissions explicitly and includes submission/version IDs and the submission's approval step alongside the existing submission snapshot, actor and timestamp. The activity endpoint preserves separate submission, review-completion and email entries instead of collapsing them by minute. No direct log-table write or extra submission event was added. Tests exercise two real submissions in one minute, distinct frozen snapshots, and preservation of the first round's reviewer note.

## 8. Reviewer gene comparison

`ApplicationDetail` already loaded and provided the frozen `ReviewRoundComparisonGet` response. `DefinitionReview` used it for scope text but did not pass it to the gene lists. The existing gene-row adapter also preferred live objects when IDs matched a snapshot.

`ReviewGenes` now consumes that shared state for GCEP, VCEP and SC-VCEP. It is used by `DefinitionReview` and, for SoW comparisons, `SustainedCurationReview`; these are the same review bodies used by ApplicationReview and the manager's StepOne/StepFour views. It adds no per-gene endpoint calls.

Submitted lists use only captured `after`/`before` rows, retaining Added, Removed, Changed and Unchanged semantics and the existing friendly tier labels. Every frozen row is non-mutable, including snapshot-only removed genes. Unavailable snapshots show an unavailable message rather than substituting live genes. The backend baseline remains the approved snapshot for the first round and the previous frozen submission for later rounds.

Live draft responses explicitly have `source: live`; they keep the existing live objects, editing and partial-discard behavior even though they share comparison mode names with submitted responses. Review History production code was not changed by this task.

## 9. Files changed by this task

Backend:

- `app/Services/ApplicationReviewFeedback.php` (new)
- `app/Mail/UserDefinedMailTemplates/ApprovalMailTemplate.php` (new)
- `app/Mail/UserDefinedMailTemplates/ApplicationRevisionRequestTemplate.php`
- `app/Mail/UserDefinedMailTemplates/ScopeOfWorkRevisionRequestTemplate.php`
- `app/Mail/UserDefinedMailTemplates/InitialApprovalMailTemplate.php`
- `app/Mail/UserDefinedMailTemplates/SustainedCurationApprovalMailTemplate.php`
- `app/Http/Controllers/Api/MailDraftController.php`
- `app/Modules/ExpertPanel/Actions/NotifyContacts.php`
- `app/Modules/ExpertPanel/Actions/StepApprove.php`
- `app/Modules/Group/Actions/ApplicationSubmissionReject.php`
- `app/Modules/Group/Actions/ScopeOfWork/RevisionSubmissionApprove.php`
- `app/Modules/Group/Events/ApplicationEmailSent.php` (new)
- `app/Modules/Group/Events/ApplicationStepSubmitted.php`
- `app/Modules/Group/Http/Controllers/Api/ActivityLogsController.php`

UI/templates:

- `resources/js/components/applications/ApprovalNotificationFields.vue` (new)
- `resources/js/components/applications/ApproveStepForm.vue`
- `resources/js/components/applications/ScopeOfWorkApproveForm.vue`
- `resources/js/components/applications/RejectStepForm.vue`
- `resources/js/components/expert_panels/ReviewGenes.vue` (new)
- `resources/js/components/expert_panels/DefinitionReview.vue`
- `resources/js/components/expert_panels/SustainedCurationReview.vue`
- `resources/js/composables/scope_of_work_gene_rows.js`
- `resources/views/email/applications/approval/feedback.blade.php` (new)
- `resources/views/email/applications/approval/layout.blade.php`
- `resources/views/email/applications/approval/revision_request.blade.php`
- `resources/views/email/applications/approval/scope_of_work_revision_request.blade.php`
- `public/build/manifest.json` and generated assets

Tests/documentation:

- `tests/Feature/End2End/Groups/Submissions/ApplicationReviewEmailTest.php` (new)
- `resources/js/components/applications/ReviewEmailForms.test.js` (new)
- `resources/js/components/expert_panels/ReviewGenes.test.js` (new)
- `resources/js/components/expert_panels/ContextualGeneComparison.test.js`
- `resources/js/views/applications/ApplicationDetail.test.js`
- `resources/js/components/applications/Review/ReviewHistory.test.js`
- `resources/js/components/applications/Review/ReviewRoundDetail.test.js`
- `resources/js/components/groups/ScopeOfWorkNameComparison.test.js`
- This report

The existing lifecycle mock and four stale expectations were updated to match the branch's existing comment-summary placement, versioning eligibility and structured historical gene payload. Existing uncommitted production Review History/comment-summary changes were not overwritten.

## 10. Validation

- Backend: **157 tests passed, 1,025 assertions**. Includes email/logging, initial submission/rejection/approval, SoW workflow, eligibility, snapshots, frozen comparisons, all partial discard suites, Review History and comment summary.
- Frontend: **154 tests passed across 19 files**. Includes independent edits, approval option/loading/unchecked behavior, all gene renderers, frozen/live source separation, comparison refresh, partial discard, historical rendering and comment summary.
- Production build: `npm.cmd run build` passed. Vite reports a bundle-size warning for chunks over 500 kB.
- `git diff --check -- app resources tests documentation` passed before this report was added.
- No schema, DX publication/suppression logic, comment resolution policy, or review-history snapshot construction was changed.

Commands:

```text
php vendor/bin/phpunit --filter 'ApplicationReviewEmailTest|ScopeOfWork|ApplicationApprovalTest|ApplicationReviewHistoryTest|ApplicationReviewSummaryTest|SubmitApplicationStepTest|RejectSubmissionTest' --no-progress
node node_modules/vitest/vitest.mjs run resources/js/components/applications resources/js/components/groups resources/js/components/expert_panels/ContextualGeneComparison.test.js resources/js/components/expert_panels/ReviewGenes.test.js resources/js/composables/scope_of_work_comparison.test.js resources/js/views/applications/ApplicationDetail.test.js
npm.cmd run build
```

## 11. Remaining product choices

Revision approval currently reuses the established step-specific approval wording and CC lists. Dedicated wording that explicitly names an updated SoW version is an optional product refinement. Likewise, blocking approval whenever Required Revision comments remain open would be a policy change; current behavior permits it and remains intact.

Validation used automated component/backend tests and a production build; no manual browser session or external email delivery was performed.
