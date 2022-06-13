# Change Log

## 2022-06-13
* Summary and VCEP application reports have been added for users with the 'Pull Reports' permission.  Super-users, super-admins, and admins have been granted the permission.

## 2022-05-31
### Enhancements
* Users with EP Application Management permissions can now 'Request revisions' for a submitted application.
* Group contacts are emailed a receipt of submission when an application step is submitted.
* Appropriate Admin Group is emailed when a group submits an application: GCWG for GCEPS, CDWG_OC for VCEPS.

## 2022-05-19
### Fixes
* Make inactive badge gray so text is legible.
* Fix broken evidence summary form.

## 2022-04-29
### Enhancements
*  Add Chairs and Gene Curation Small group to next action assignees.
### Fixes
* Fix VCEP Gene List.

## 2022-04-19
### Enhancements
* Add latest COI date to subgroup members export
* Fix AnnualUpdate data export
* 
## 2022-04-12
### Fixes
* Fix bug for search-as-you-type person.
* Set steps 1 and 4 received_date when an application is submitted.
* 

## 2022-04-07
### Enhanchements
* Ensure user is properly transfered when merging people, including user email updated to match authority person.
* Display system roles and additional permissions on Users List Screen.
* Add All members export for Parent groups.
* People can now upload profile photos.

## 2022-04-05
### Fixes
* Bring all activity log UI in line with optimized data coming from the server.
* Use SpecificationSection component in Application admin instead of CspecSummary.
## 2022-03-30

### Enhancements
* Allow Coordinators to upload specification related documents in the application.

## 2022-03-29
### Fixes
* Typo fix in applicaiton admin.
* Fix person "search".

### Enhancements
* Performance improvements for more screens.


## 2022-03-25
### Enhancements
* Performance improvements for User, Invite Admin, and people list screens and Applications admin screen.
* Application Admin screen updates:
  * Only show last activity date.  show last activity description on hover.
  * Show next actions on hover.

## 2022-03-16
### Fixes
* Fixed bug preventing people from seeing their own COI responses.
### Enhancements
* Coordinators and admins can 'unretire' a group member. #coordinators #members.
* Prepopulate COI form with last response for group if one exists.
* Add command to create an in-system notification: php artisan notify:system

## 2022-03-15
### Fixes
* Fixed bug in member filtering on name, email, and institution; 

### Enhancements
* Coordinators can edit a coordinated person's name and email. #coordinators #members
* Coordinators can view a coordinated person's Mail Log and resend mail. #coordinators #members
* Coordinators can view a coordinated person's event log. #coordinators #members
* Users can now filter by notes and expertise. #coordinators #members
* Coordinators can export member list. #coordinators #members
* Coordinators can start an email in there email client from the filtered members. #coordinators #members