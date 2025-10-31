# ClinGen Group & Personnel Management

A system for managing and streamlining the Application process for ClinGen Expert panels.
For lack of a better name we'll call it by its initials "GPM"

## Installation
### Prerequisites
You must have the following to stand up the application locally
* Docker
* (if on a mac, you'll also need docker-deskop or colima-- for instance as in [macos-setup.md](macos-setup.md))

### Setup DOCKER_USER variable (probably only once ever)

To handle docker permissions issues in bind mounts, this project uses the convention of running
containers using the `DOCKER_USER` (formatted like `$UID:$GID`) as the user for the container.
You could set this in the `.env` file (described later on), or to avoid having to do this in other
projects that use the same convention, you can add a line like `export DOCKER_USER=$(id -u):$(id -g)`
or `export DOCKER_USER=${UID}:${GID}` to your `.zshrc`, `.bashrc` or equivalant in your home directory.

### Setup other configurable options (probably don't need to re-do often)

This project uses the convention that Laravel uses with most configurable options definable in
a `.env` file in the project root. There is an `.env.example` file to guide this...

```bash
cp .env.example .env
```

Then edit as needed... maybe you'll want to change the things that are defined as `changeme`...

### Seeding the database

Not necessarily "one-time setup", but probably not something that needs doing frequently. This needs
to be done at least once before anything will work, though.

Right now, seeding via Laravel has accumulated some technical debt, so the easiest way to do this
is from an existing database dump.

The database uses a standard mysql docker image, which runs sql or sql.gz files places in a certain
directory, but only if the container volume hasn't been initialized before. So if you have an existing
docker volume, you may need to remove it.

```bash
docker volume rm gpm_db
```

Your database dump file (as sql or compressed as sql.gz) needs to go in the subdirectory
`.docker/mysql/db-init/` under the project root. `*.sql.gz` files in this directory are .gitignore-ed
to help avoid unintential committing of database dump files.

Then just start the database container with:

```bash
docker compose up -d db
```

This will take a minute or so-- if you watch things with `docker compose logs -f`, you'll see a
local-only server started for initialization, then it should restart listening on the docker network.

### Populate `vendor/` directory with dependencies

If you have php and composer on your host system, you could just run `composer app install`, but in
the name of reproducibility, it is probably better to run this using the php and composer versions
in the container.

**Note**: You may not need to do this initially-- the entrypoint script should take care
of running this if the vendor directory hasn't been populated. But you would need to do this whenever
dependencies get updated. This is the set of commands to run if you get errors about missing
dependencies in the PHP code.

```bash
docker compose run --no-deps --rm -it --entrypoint composer app install --no-interaction --no-plugins --no-scripts --prefer-dist --no-dev 
docker compose run --no-deps --rm -it --entrypoint composer app dump-autoload
```

## Running

Once everything above is setup, you should be able to just run the following:

```bash
docker compose up -d
```

After some initialization (which you can watch using `docker compose logs -f`), the app should
start responding to requests at the port given by `APP_PORT` in the `.env` file. By default,
this will be reachable at `http://localhost:8013`.

## Accessing container services

The `docker-compose.yml` only exposes the nginx container to the host. The primary reason for this is
to prevent various containers in different project (e.g., mysql or redis containers) from stepping
on each others' toes by trying to open the same port. Your options for getting to those services are:

* running `docker compose exec -it db`, then using the command line utilities there to access data
* running a one-off container with `docker compose run` and a `socat` container to be on that network
  and forward from this container to the one you're trying to access. This temporary port forwarding
  is left as an exercise to the reader.

## Frontend (this section needs to be re-worked since the frontend is run by default in the container)

[Vue.js](https://vuejs.org) is used as the primary frontend framework. Source is under `resources/js`.

For now, the "bundled" javascript code is being checked in to the repo, so you should be able to run
things without additional frontend initialization steps. For frontend development, though, you would
need to install the dependencies (`npm i`) then either run `npm run dev` to start a development server
(which is set up to communicate with the `localhost:8013` backend) or call `npm run build` to generate
the bundled javascript.

The frontend testing setup is currently broken as of Feb 2025 with the transition to vite.

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

### Caveats for scheduling on openshift/kubernetes
There are distinct advantages to running the scheduler as a kubernetes CronJob resource instead of a constantly-running container triggering on a minute-by-minute basis. The main
ones being that the scheduler rarely does anything, so we don't need to waste allocation of CPU all of the time, but can give the scheduler "burst" amounts as might be needed
for large batch processes.

But there's also no point in running the scheduler as a CronJob every minute-- that's spinning up a lot of pods that would end right away and someone in ops would probably notice and
get unhappy. Unfortunately, Laravel does not keep track of "scheduler jobs scheduled but not run", so we have to be sure to schedule things when we expect the scheduler to run.

**Currently, the openshift setup on the prod instance only runs the scheduler at the top of the hour and 10 minutes past.** This is probably still way more than we need...

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

