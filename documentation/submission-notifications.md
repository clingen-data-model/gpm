# Submission notifications
The chairs requested notifications sent twice a week about new comments, and judgements on applications by other chairs.  To reduce the number of emails we're sending the to the chairs we decided to include approval reminders with these emails as well.

To accomplish twice-weekly digests of submission notifications the GPM creates `DigestibleNotifications` each time a comment is created, updated, deleted, or approved, and each time a judgement is created, updated, or deleted.

Notifications are only sent to users with `ep-applications-approve` and `ep-applications-comment` system permissions.  Notifications about comment and judgement activity are not sent to the creator of the comment or judgement.

Twice a week approval reminder notifications are generated and `SendSubmissionNotificationsDigest` is run to aggregate the digestible notifications and send them as a single email.

## Manual Test Script
While automated tests exist to test the units and aspects of their integration, it seemed prudent to manually test a sequence of actions taken by different users and ensure the expected notifications are sent.

This script describes a sequence of actions and the expected notification outcomes.

1. Submit VCEP application
2. Comment as Courtney & Send to chairs.
    - Expect email sent to each chair about application

3. Comment as Sharon.
4. Reply as Courtney.

5. Generate ApprovalReminders and send digest
    - Expect email to all chairs:
        * Jonathan: approval reminder, comment
        * Heidi: approval reminder, comment
        * Sharon: approval reminder

6. Comment as Jonathan
7. Comment as Heidi
8. Comment as Danielle
9. Judgement as Sharon

10. Submit & approve GCEP application
    - Expect email sent to each chair about application
11. Comment as Jonathan

10. Generate ApprovalReminders and send digest
    - Expect email to all chairs:
        * Jonathan: 2 approval reminders, 2 comments, 1 judgement
        * Heidi: 2 approval reminders, 2 comments, 1 judgment
        * Sharon: 1 approval reminder, 3 comments (2 groups)
