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

### ClinGen Data Exchange Integration
The ClinGen Data Exchange (DX) is a [Kafka](https://kafka.apache.org/) message broker run by the Broad Grant, hosted on https://conflluent.io.

The GPM integrates with the DX to publish messages about its data and consume messages from the [CSPEC Registry](https://https://cspec.genome.network/cspec/ui/svi/) about VCEP AMCG/AMP Guidelines Specfications.


The GPM produces to one (soon two) topic(s):
* `gpm-general-events` - While a bit mis-named, this topic publishes messages about events related to GPM groups, their scope, and their members.  See [gpm-general-events documention](public/data-exchange/gpm-general-events.md) for details.
* `gpm-person-events` - This topic publishes messages about events related to people in the GPM (i.e. profile information about group members). See [gpm-person-events documentation](public/data-exchange/gpm-person-events.md) for details.

#### Notes about the DX implementation
* All topics are on a single partition.

#### Configuration
A number of environment variables can be set to configure DX authentication and topics.
[TODO]

#### Concepts
`MessageStream`: An object with consumes messages from a set of topics, yielding `DxMessage` DTOs as messages are consumed.

`DxMessage`: A data transfer object representing a DX message.

`MessageProcessor`: An action who's handle method takes a `DxMessage` an argument, does some processing, and returns the `DxMessage`.

#### Consuming Topics
The GPM consumes all available fmessages from subscribed topics every hour.
Consuming topics from the data exchange can be done using `App\DataExchnage\Actions\DxConsume`:
```
    // or $consume = app()->make(DxConsume::class);
    $consume->handle(['topic_1', 'topic_2']);
```
The consumer adds topics to a `MessageStream` and passes consumed messages to a `MessageProcessor` which processes the messages.

By default the GPM binds `KafkaMessageStream` to the `MessageStream` contract, and `IncomingMessageProcess` to the `MessageProcessorContract`.

`IncomingMessageProcessor` delegates creation of a `StreamMessage` model based on the `DxMessage` to `IncomingMessageStore` and uses the `MessageHandlerFactory` to instantiate message specific processing.

#### Actions taken on receipt of CSPEC messages
If the affiliation associated with the event does not exist, or has an incompatible application status a `DataSynchronizationException` is thrown, an error is logged and the message is left unprocessed.

<table>
    <tr>
        <th>Event Type</th>
        <th>Action Taken</th>
        <th>Action Class</th>
    </tr>
    <tr>
        <td>classified-rules-approved</td>
        <td>Marks Draft Rules step (2) approved.</td>
        <td>App\DataExchange\Actions\ClassifiedRulesApprovedProcessor</td>
    </tr>
    <tr>
        <td>pilot-rules-approved</td>
        <td>Marks Pilot Rules step (3) approved if not previously already approved.  If previously approved, assigns a task to review sustained curation responses.</td>
        <td>App\DataExchange\Actions\ClassifiedRulesApprovedProcessor</td>
    </tr>
</table>
