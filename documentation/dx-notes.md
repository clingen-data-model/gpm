Messages are made in `DxMessageFactory`, by taking `PublishableEvent`s and constructing from:

```
            eventType: $event->getEventType(),
            message: $event->getPublishableMessage(),
            schemaVersion: null, // gets filled from dx.schema_versions.gpm-general-events
            date: $event->getLogDate()
```

So `getPublishableMessage` ends up determining the schema/representation

Things that might get sent to `gpm-general-events` include subclasses of `ExpertPanelEvent` and `GroupEvent`.
For `gpm-person-events`, it looks like this applies to things with trait `App\Modules\Person\Events\Traits\PublishesEvent`

# Activity stuff

Things are recorded in the table activity_log using [Spatie ActivityLog](https://spatie.be/docs/laravel-activitylog/v4).

`activity_type` is usually set from a kebab-cased version of the class name for each RecordableEvent, but StepEvent
overrides this based on the `step` of 1 to 4.
