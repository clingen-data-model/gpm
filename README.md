# ClinGen Expert Panel Application Management

A system for managing and streamlining the Application process for ClinGen Expert panels.
For lack of a better name we'll call it the EPAM.

## Installation
### Using DockerCompose
`docker-compose up -d` will bring up all containers with the app available at http://localhost:8080.

## Architecture

The EPAM is a Laravel application using MySQL for persistance and Redis as a cache and pub-sub for laravel's queue.

The app, scheduler, and queue containers all use the same image. The entry point script (found in `.docker/start.sh`) determines the behavior of each container based on the `CONTAINER_ROLE` environment variable.

### Follow Actions
A follow action is an operation that has been serialized and persisted that is waiting to be run when an event of a certain type is fired.

For example, suppose we wanted to assign a system permission to all members of group XYZ.  This is not as simple a task as it seems because system roles are assigned to User models, but at the time of adding a member to a group, the member may not yet have a User account.  To handle this we could do the following create a listener for the `MemberAdd` event that does one of two things:
1. If `$memberAddEvent->member->person->user` exists we can assign the system roles
2. Otherwise create a `FollowAction` that listens for `InviteRedeemed` events and if `$inviteRedeemedEvent->person->id == $memberAddEvent->member->person_id` it gives the user the system permission.

The follower attribute of a FollowAction is an instance of an invokable class that accepts the Event as it's argument:
```
namespace App\Actions

class MyFollowAction implements AsFollowAction
{
    public function __construct()
    {}

    public function asFollowAction(Event\Of\Interest $event, ...$args)
    {
        if ($event->a == $args['contextArg1']) {
            // do the thing;
            return true;
        }

        return false;
    }
}
```

A follow action can be stored like so:
```
$followAction = FollowAction::create([
    'event_class' => Event\Of\Interest::class,
    'follower' => App\Actions\MyFollowAction::class,
    'args' => ['contextArg1' => 'blah blah'],
    'name' => 'An optional name for humans',
    'description' => 'Description about the follow action for humans.'
]);
```

The `FollowActionRun` action is registered as a listener for all module events.  It checks for incomplete FollowActions persisted for an event and runs them.

When a follower returns `true` `FollowActionRun` will set the `FollowAction.completed_at` timestamp so it will not be run again.

If you want to create a FollowAction that always runs just have `asFollowAction` always return false.

