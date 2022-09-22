<?php

namespace App\Modules\Group\Providers;

use App\Models\Activity;
use App\Models\LogEntry;
use App\Events\PublishableEvent;
use App\Policies\LogEntryPolicy;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Event;
use App\Modules\Group\Events\GenesAdded;
use App\Modules\Group\Events\GeneRemoved;
use App\Modules\Group\Policies\GroupPolicy;
use App\Modules\Group\Actions\NotifyGenesAdded;
use App\Modules\ExpertPanel\Events\StepApproved;
use App\Modules\Group\Actions\GenesChangeNotify;
use App\Modules\Group\Actions\GroupStatusUpdate;
use App\Modules\Foundation\ModuleServiceProvider;
use App\Modules\Group\Actions\NotifyGenesRemoved;
use App\Modules\Group\Actions\EventApplicationPublish;
use App\Modules\Group\Events\ApplicationStepSubmitted;
use App\Modules\ExpertPanel\Events\ApplicationCompleted;
use App\Modules\Group\Actions\ApplicationSnapshotCreate;
use App\Modules\Group\Events\PublishableApplicationEvent;
use App\Modules\Group\Events\ApplicationRevisionsRequested;
use App\Modules\Group\Actions\ApplicationSubmissionReceiptSend;
use App\Modules\Group\Actions\NextActionReviewSubmissionComplete;
use App\Modules\Group\Actions\ApplicationSubmissionMailAdminGroup;
use App\Modules\Group\Actions\ApplicationSubmissionAssignNextAction;
use App\Modules\Group\Actions\ApplicationSubmissionNotificationSend;
use App\Modules\Group\Actions\ApplicationRevisionsRequestedAssignNextAction;

class GroupModuleServiceProvider extends ModuleServiceProvider
{
    protected $listeners = [
        ApplicationStepSubmitted::class => [
            ApplicationSubmissionMailAdminGroup::class,
            ApplicationSubmissionReceiptSend::class,
            ApplicationSubmissionAssignNextAction::class,
            ApplicationSnapshotCreate::class
        ],
        ApplicationRevisionsRequested::class => [
            ApplicationRevisionsRequestedAssignNextAction::class,
            NextActionReviewSubmissionComplete::class
        ],
        ApplicationCompleted::class => [GroupStatusUpdate::class],
        GenesAdded::class => [NotifyGenesAdded::class],
        GeneRemoved::class => [NotifyGenesRemoved::class],
    ];

    protected $policies = [
        Group::class => GroupPolicy::class,
        Activity::class => LogEntryPolicy::class
    ];


    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        parent::register();
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
        $this->registerPolicies();

        $eventClasses = array_merge(
            $this->classGetter->atPath($this->getEventPath()),
            $this->classGetter->atPath(app_path('Modules/ExpertPanel/Events'))
        );
        foreach ($eventClasses as $class) {
            if (array_key_exists(PublishableEvent::class, class_implements($class))) {
                Event::listen($class, [EventApplicationPublish::class, 'handle']);
            }
        }

        $this->mergeConfigFrom(
            __DIR__.'/../groups.php',
            'groups'
        );
    }

    protected function getModulePath()
    {
        return (__DIR__.'/..');
    }
}
