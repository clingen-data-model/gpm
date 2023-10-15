<?php

namespace App\Modules\ExpertPanel\Providers;

use App\Modules\ExpertPanel\Events\StepApproved;
use App\Modules\Foundation\ModuleServiceProvider;
use App\Modules\Group\Actions\NextActionReviewSubmissionComplete;

class ExpertPanelModuleServiceProvider extends ModuleServiceProvider
{
    protected $listeners = [
        StepApproved::class => [
            NextActionReviewSubmissionComplete::class,
        ],
    ];

    protected $policies = [
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        parent::register();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
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
        return __DIR__.'/..';
    }
}
