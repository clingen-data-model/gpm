# Application Submissions
* Submission is disabled until all page requirements are met.
* When all page requirements are met and the coordinator clicks the submit button:
  1. A submission record is created.
  2. Admins are notified that an submission has occurred.
  3. The Application Form will look like ...
* When the admin logs in they will see a list of pending submissions on their dashboard.
* Clicking on a submission record will take the admin to the application detail screen (adapted from EPAM MVP).
* From the detail screen an admin can review the application, download a PDF version of the submission, and mark the application step approved.




## Planned functionality
### Pre-approval & Approval and revisions workflow.
* Application Pre-approver role/permissions added to the system.
* Application Approver roles/permissions added to the system.
* Approvers can make comments that are kept private to Pre-approvers/approvers/admins.
* A Pre-approver can mark a submission ready for Approver review.
* An admin/pre-approver/approver can add/update/delete request revisions for an application section.
  * When a revision is requested a RevisionRequest record is created and associated with the ExpertPanel & application section.
* An admin can "respond" to a submission with 'Approved', 'Approved w/ revisions', 'revisions requested'.  All three responses effectively "finish" the submission.
  * Approved - unconditionally approved.  
    * Automated emails will be sent indicating the submission has been approved.
  * Approved w/ Revisions - Approved when all requested revisions are completed.
  * Revisions Requested - Revise and resubmit.
* When a submission is marked "Approved w/ Revisions" or "Revisions Requested" the group coordinator or pre-approver/approver may respond to revision requests with comments.
