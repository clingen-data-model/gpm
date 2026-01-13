<?php

namespace App\Modules\Funding\Providers;

use App\Modules\Foundation\ModuleServiceProvider;

class FundingModuleServiceProvider extends ModuleServiceProvider
{
    protected $listeners = [];

    protected $policies = [];

    public function register()
    {
        parent::register();
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
