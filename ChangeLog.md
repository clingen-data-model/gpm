# Change Log

# 2022-03-25
* Performance improvements for User, Invite Admin, and people list screens and Applications admin screen.
* Application Admin screen updates:
  * Only show last activity date.  show last activity description on hover.
  * Show next actions on hover.

# 2022-03-16
## Fixes
* Fixed bug preventing people from seeing their own COI responses.
## New Features
* Coordinators and admins can 'unretire' a group member. #coordinators #members.
* Prepopulate COI form with last response for group if one exists.
* Add command to create an in-system notification: php artisan notify:system

# 2022-03-15
## Fixes
* Fixed bug in member filtering on name, email, and institution; 

## New Features
* Coordinators can edit a coordinated person's name and email. #coordinators #members
* Coordinators can view a coordinated person's Mail Log and resend mail. #coordinators #members
* Coordinators can view a coordinated person's event log. #coordinators #members
* Users can now filter by notes and expertise. #coordinators #members
* Coordinators can export member list. #coordinators #members
* Coordinators can start an email in there email client from the filtered members. #coordinators #members