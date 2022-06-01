<?php

namespace App\Modules\ExpertPanel\Providers;

use App\Listeners\RecordEvent;
use App\Events\RecordableEvent;
use Illuminate\Support\Facades\Event;
use App\Modules\Foundation\ClassGetter;
use Illuminate\Support\ServiceProvider;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\StepApproved;
use App\Modules\Foundation\ModuleServiceProvider;
use App\Modules\ExpertPanels\Policies\ExpertPanelPolicy;
use App\Modules\Group\Actions\NextActionReviewSubmissionComplete;

class ExpertPanelModuleServiceProvider extends ModuleServiceProvider
{
    protected $listeners = [
        StepApproved::class => [
            NextActionReviewSubmissionComplete::class
        ]
    ];

    protected $policies = [
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
            __DIR__.'/../config.php',
            'expert-panels'
        );
    }

    protected function getModulePath()
    {
        return (__DIR__.'/..');
    }
}
