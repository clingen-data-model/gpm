<?php

namespace App\Modules\Funding\Providers;

use App\Modules\Foundation\ModuleServiceProvider;

use App\Modules\Funding\Models\FundingSource;
use App\Modules\Funding\Policies\FundingSourcePolicy;

class FundingModuleServiceProvider extends ModuleServiceProvider
{
    protected $listeners = [];

    protected $policies = [
        FundingSource::class => FundingSourcePolicy::class,
    ];

    public function register()
    {
        parent::register();
        $this->mergeConfigFrom(
            __DIR__.'/../funding.php',
            'funding'
        );
    }

    public function boot()
    {
        parent::boot();
        $this->registerPolicies();
    }

    protected function getModulePath()
    {
        return (__DIR__.'/..');
    }
}
