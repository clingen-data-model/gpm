# ClinGen Group & Personnel Management

A system for managing and streamlining the Application process for ClinGen Expert panels.
For lack of a better name we'll call it the EPAM.

## Installation
### Prerequisites
You must have the following to stand up the application locally
* PHP 8.0
* Composer
* Docker

The stand up a local instance of the application
1. Clone this repository
2. cd into the directory
3. `composer install`
3. `cp .env.example .env`
4. `php artisan key:generate`
3. `docker-compose up -d --build`
4. `docker-compose exec app php artisan migrate --seed`

The development server is available at http://localhost:8080

To work on the front end client you will need:
* node
* npm
* vue-cli

To start the development server:
1. `cd resources/app`
2. `npm run serve`

This will start up the webpack development server which will server which you can access at http://localhost:8081.

The development server supports hot module replacement (HMR) so changes to code will be hot swapped when the dev server is running.  The dev server will proxy api requests to http://localhost:8080.  Note that the dev server's proxy does only supports xhttp requests.  The handful of regular requests (i.e. impersonation, report downloads, etc.) will require pointing you browser directly at port 8080.

For more information see [vue-cli documentation](https://cli.vuejs.org/)

## Backend Architecture

The GPM is a Laravel application using MySQL for persistance and Redis as a cache and pub-sub for laravel's queue.

### Modules

### Actions
The GPM makes heavy use of "actions" and in particular the [laravel-actions](https://github.com/lorisleiva/laravel-actions) package.  In this context, and action can be defined as class that performs a task.  Actions can take advantage of Laravel's dependency injection to compose an action of other actions.

The laravel-actions package adds decorator traits that allow you use an action as a controller, event listener, queued job, or artisan command.

Actions are used as controllers for nearly all write operations in the GPM as well as some read operations.

By convention, actions in this code-base are named NounVerb to support easy location of the action you're looking for.

Actions are currently found in `app/Actions` and in the `app/Modules/ModuleName/Actions`.

### Activity Log
The GPM records domain events to an activity log (based on [spatie/laravel-activitylog](https://spatie.be/docs/laravel-activitylog/v4/introduction)).  

"Recordable" events extend the abstract `App\Events\RecorableEvent` class.  The app registers the `App\Listeners\RecordEvent` to all events in expected directories (app/Events, app/Modules/*/Events).  Recordable events are then persisted to the activity_logs table.

### Notifications
Most notifications in the GPM use standard [laravel notifications infrastructure](https://laravel.com/docs/9.x/notifications).  The exception is notifications intended to be aggregated into digest emails. 

#### DigestibleNotification
Digestible notification should implement `App\Notifications\ContractsDigestibleNotificationInterface` and be sent via the database.  These notifications will be stored in the database and sent together on a schedule.  Currently all digestible notifications are related to EP application submissions and are sent together by `App\Actions\SendSubmissionDigestNotifications`.  For details on these notifications see the [Submission Notifications docs](/documentation/submission-notifications.md).

### Follow Actions
A follow action is an operation that has been serialized and persisted that is waiting to be run when an event of a certain type is fired.

For example, suppose we wanted to assign a system permission to all members of group XYZ.  This is not as simple a task as it seems because system roles are assigned to User models, but at the time of adding a member to a group, the member may not yet have a User account.  To handle this we could do the following create a listener for the `MemberAdd` event that does one of two things:
1. If `$memberAddEvent->member->person->user` exists we can assign the system roles
2. Otherwise create a `FollowAction` that listens for `InviteRedeemed` events and if `$inviteRedeemedEvent->person->id == $memberAddEvent->member->person_id` it gives the user the system permission.

The follower attribute of a FollowAction is an instance of an invokable class that accepts the Event as it's argument:
```
namespace App\Actions

use App\Notifications\Contracts\DigestibleNotificationInterface;

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
### Controlled vocabularies and synonyms
In the GPM there are several controlled vocabularies.  Some controlled vocabularies make use of synonyms, a polymorphic relationship allowing synonyms to be attached to a term in the vocabulary.  These include:
* Credentials
* Expertises

A model with synonyms implements the `App\ControlledVocabularies\HasSynonymsInterface` interface.  A base implementation can be found in `App\ControlledVocabularies\HasSynonymsTrait` trait.


### ClinGen Data Exchange Integration
The ClinGen Data Exchange (DX) is a [Kafka](https://kafka.apache.org/) message broker run by the Broad Grant, hosted on https://conflluent.io.

The GPM integrates with the DX to publish messages about its data and consume messages from the [CSPEC Registry](https://cspec.genome.network/cspec/ui/svi/) about VCEP AMCG/AMP Guidelines Specfications.

The GPM produces to one (soon two) topic(s):
* `gpm-general-events` - While a bit mis-named, this topic publishes messages about events related to GPM groups, their scope, and their members.  See [gpm-general-events documention](public/data-exchange/gpm-general-events.md) for details.
* `gpm-person-events` - This topic publishes messages about events related to people in the GPM (i.e. profile information about group members). See [gpm-person-events documentation](public/data-exchange/gpm-person-events.md) for details.

For more details about implementation of DX integration see the [DX Implementation documentation](/documentation/dx-implementation.md)

For more information on the ClinGen DX in general see the [ClinGen Data Exchange Infrastructure Developer Guide](https://docs.google.com/document/d/19D9QmxxzlxQEvMzNNpKccCx_e3Sj8bXZcEEIWN7zK5A/).  For information about available topics see the [Topic Inventory](https://docs.google.com/spreadsheets/d/1yuO9-IM-2MRM1AacKekNJdHRb8fl6ozxO7OFu6WPQ2Q/edit?usp=sharing).

## Frontend
The frontend client is developed using [Vue3](https://vuejs.org/guide/introduction.html), [vue-router](https://router.vuejs.org/) for client-side routing, and [vuex](https://vuex.vuejs.org/) for a global data store. [TailwindCss](https://tailwindcss.com/) is the css framework.

Because this vue app was migrated from vue2 you will find components written using both the **options** and **composition** (see https://vuejs.org/api/) apis (including the [script-setup]https://vuejs.org/api/sfc-script-setup.html) syntax).

The GPM js client is built using [vue-cli](https://cli.vuejs.org/) rather than Laravel-mix due to limitations of code splitting in mix at the time of development.  Because if this you will not find the javascript or css in `resources/js` and `resources/css`, respectively. 

All javascript and css is located in `resources/app` and follows the standard vue-cli directory structure with a few additions:
1. `src/composables` contains javascript modules intended for re-use via vue's composition API.
2. `src/domain` contains javascript modules specific to the GPM domain, including entity definition classes (see below), ExpertPanel application related js modules, and other services.
3. `src/forms` contains javascript modules that help with some forms.
4. `src/repositories` contains repository modules for comments and comment types. (sorry about using all the patterns!)

There are also a few utility files that provide common helper methods.
* auth_utils.js includes helpers to check a user/person/group_member's roles and permissions.
* date_utils.js includes date helpers.
* string_utils.js includes casing helpers.
* utils.js includes helpers that don't clearly belong in one of the other util modules.

### Store
The vuex store is fairly idiomatic.  There are separate modules for the most relevant entities in the domain.  Most modules follow a similar pattern and where that pattern is followed without varation the `module_factory` is used to generate a module.

## DevOps
The demo and production instances of the GPM are hosted on UNC's [Cloudapps OpenShift cluster](https://console.cloudapps.unc.edu) in the `dept-gpm` project.  OpenShift is RedHat's value-add to the Kubernetes open source project.  You're better off referencing Kubernetes documentation for anything that is not a proprietary OpenShift thing (i.e. Builds, BuildConfigs, etc.).

### Architecture
At a high level, the project is composed of: 
* MySQL server: persistent store for the application. Based on the [jward3/openshift-mysql](https://hub.docker.com/repository/docker/jward3/php) image.
* Redis server: application cache, and queue
* Laravel app, running in three "roles", web app, scheduled task runner, and queue worker. Based on the [jward3/php](https://hub.docker.com/repository/docker/jward3/php) image.
* A cronjob that backs up the database and writes to a persistent volume.
* A cronjob that cleans database backups.

This repository is built into a docker image via the *app* build config and stored in the app ImageStream.

The application image is deployed by three separate DeploymentConfigs to use the Laravel app in different contexts:
* `app` - Runs an apache web server with php.  This is deployment of the web-accessible app.
* `schuduler` - Runs `php artisan schudule:run` every minute to ensure scheduled tasks are executed. See the [Laravel scheduled docs](https://laravel.com/docs/8.x/scheduling) for details.
* `queue` - Starts a queue worker `php artisan queue:work` which processes jobs queued to the redis DeploymentConfig.  See https://laravel.com/docs/8.x/queues for details on queued jobs

The `CMD` for the image runs `.docker/start.sh` which runs the appropriate command based on the `CONTAINER_ROLE` environment variable.  Valid container roles include  `app`, `queue`, and `scheduler`.

### SSL 
* [Updating SSL Certificates](documentation/ssl-cert-updated.md)


### TODO: Training
ClinGen needs to track the training of bio-curators to ensure compliance with FDA regulations.  

ClinGen members in all curation activities must receive training to participate in ClinGen groups and expert panels.

Training tracking is currently managed by the CCDB, but b/c the CCDB was only ever intended to track C3 volunteers relying on it to track training of all ClinGen members is problematic.
### Training Topics
Topics of Training include:
* Baseline Curation (CCDB baseline volunteers only)
* Actionability
* Dosage
* Gene
* Somatic Variant
* Variant - Variant curators must complete an additional level of training that is specific to each of their VCEPs.

### Training Sessions
A training session is a synchronous event via teleconferencing.
Level 1 training sessions occur at regular intervals.  In general the person coordinating a training session for a topic follows this general workflow:
1. Identify ClinGen members who require training for the topic.
1. Make inquiries about scheduling to find a date and time that work for the most people. (Doodle?)
1. Schedule the training session in the CCDB and invite ClinGen members who require training.

When a person is invited to training the CCDB sends them an customized email with the time, date, and "location" of the session.

During or following the training session the trainer can mark training complete for any invitees.

#### Possible integrations
##### Zoom
The Zoom API allows the [creating meetings](https://marketplace.zoom.us/docs/api-reference/zoom-api/methods/#operation/createMeeting) once a user has authorized the client via OAuth.  
A Zoom API, the client can also:  
* [Get a meeting invitation](https://marketplace.zoom.us/docs/api-reference/zoom-api/methods/#operation/meetingInvitation)
* [Get a list of a past meeting's participants](https://marketplace.zoom.us/docs/api-reference/zoom-api/methods/#operation/pastMeetingParticipants)
* [Update a meeting](https://marketplace.zoom.us/docs/api-reference/zoom-api/methods/#operation/meetingUpdate)
* Many other features.

