<?php

namespace App\Modules\Group\Providers;

use App\Models\Activity;
use App\Policies\LogEntryPolicy;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Events\GenesAdded;
use App\Modules\Group\Events\GeneRemoved;
use App\Modules\Group\Policies\GroupPolicy;
use App\Modules\Group\Actions\NotifyGenesAdded;
use App\Modules\Group\Actions\GroupAttributesUpdate;
use App\Modules\Foundation\ModuleServiceProvider;
use App\Modules\Group\Actions\NotifyGenesRemoved;
use App\Modules\Group\Events\ApplicationStepSubmitted;
use App\Modules\ExpertPanel\Events\ApplicationCompleted;
use App\Modules\Group\Actions\ApplicationSnapshotCreate;
use App\Modules\Group\Events\ApplicationRevisionsRequested;
use App\Modules\Group\Actions\ApplicationSubmissionReceiptSend;
use App\Modules\Group\Actions\NextActionReviewSubmissionComplete;
use App\Modules\Group\Actions\ApplicationSubmissionMailAdminGroup;
use App\Modules\Group\Actions\ApplicationSubmissionAssignNextAction;
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
        ApplicationCompleted::class => [GroupAttributesUpdate::class],
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
