<?php

namespace App\Modules\Group\Providers;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Event;
use App\Modules\Group\Events\GenesAdded;
use App\Modules\Group\Events\GeneRemoved;
use App\Modules\Group\Policies\GroupPolicy;
use App\Modules\Group\Actions\NotifyGenesAdded;
use App\Modules\Group\Actions\GenesChangeNotify;
use App\Modules\Group\Actions\GroupStatusUpdate;
use App\Modules\Foundation\ModuleServiceProvider;
use App\Modules\Group\Actions\NotifyGenesRemoved;
use App\Modules\Group\Actions\EventApplicationPublish;
use App\Modules\Group\Events\ApplicationStepSubmitted;
use App\Modules\ExpertPanel\Events\ApplicationCompleted;
use App\Modules\Groups\Events\PublishableApplicationEvent;
use App\Modules\Group\Actions\ApplicationSubmissionNotificationSend;

class GroupModuleServiceProvider extends ModuleServiceProvider
{
    protected $listeners = [
        ApplicationStepSubmitted::class => [ApplicationSubmissionNotificationSend::class],
        ApplicationCompleted::class => [GroupStatusUpdate::class],
        GenesAdded::class => [NotifyGenesAdded::class],
        GeneRemoved::class => [NotifyGenesRemoved::class],
    ];

    protected $policies = [
        Group::class => GroupPolicy::class,
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
            if (array_key_exists(PublishableApplicationEvent::class, class_implements($class))) {
                Event::listen($class, [EventApplicationPublish::class, 'handle']);
            }
        }

        $this->mergeConfigFrom(
            __DIR__.'/../groups.php',
            'groups'
        );
        $this->mergeConfigFrom(
            __DIR__.'/../specifications.php',
            'specifications'
        );
    }

    protected function getModulePath()
    {
        return (__DIR__.'/..');
    }
}
