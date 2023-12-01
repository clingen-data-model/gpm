<?php

namespace App\Tasks\Providers;

use App\Modules\Foundation\ModuleServiceProvider;

class TaskServiceProvider extends ModuleServiceProvider
{
    public function boot()
    {
        parent::boot();

        $this->mergeConfigFrom(
            __DIR__.'/../configs/tasks.php',
            'tasks'
        );
    }

    public function getModulePath()
    {
        return __DIR__.'/./..';
    }
}
