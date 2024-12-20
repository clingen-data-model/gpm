In the GPM we have Users and People.  Users can log in. People are records of individuals that are part of ClinGen.  The system was designed to allow people to exist without ever activating a User account.  Most People also have a User Account.  A common case where a user account doesn't exist is if the person doesn't accept their invitation to join ClinGen.  

The distinction between Users and People is important in how deletions are handled.  

Person records can be soft-deleted.  We add a timestamp to the `deleted_at` column in the database so the application knows they are effectively deleted.

User records are always hard-deleted.  The record is completely removed from the database.

To “hard” delete a record:

1. Go into the artisan console in one of the containers `php artisan tinker` 
2. Loading up the record.  For example: `$ebony = Person::withTrashed()->find(2547)`
3. Hard deleting them.  For example:  `$ebony->forceDelete()`

You could also restore the soft-deleted record by following steps 1 & 2 above, then `$ebony->restore()`

Needed enhancements to this process are to make people deletion more visible: 

1. Update the People list view to allow the user to include/exclude/only-show deleted people
2. Update the Person detail screen to show soft-deleted people and allow admins to hard-delete people

Issues raised about the distinction are:
1. Are retired persons still users?  Yes, retired persons are users.  I retired a person who belonged to only one group.  She was removed immediately from the group, but she remains a user.
2. For inactive groups, are the members persons but not users?  For our inactive groups, typically only the Coordinator remains a member of the group.  For example, for the Long QT syndrome GCEP group, Emma is the only member.
3. Which roles can perform hard deletes and soft deletes?  For the hard delete process described above, only Bradford and Mell (super-users) can perform this procedure.  Coordinators can perform soft deletes as well as super admins.
4. We need the ability to add a person but not send an invite.  The current process automatically sends an invite for new people.


