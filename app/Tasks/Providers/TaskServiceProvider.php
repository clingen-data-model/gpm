<?php

namespace App\Tasks\Providers;

use App\Modules\Foundation\ModuleServiceProvider;
use Illuminate\Support\ServiceProvider;

class TaskServiceProvider extends ModuleServiceProvider
{
    public function boot()
    {
        parent::boot();
        
        $this->mergeConfigFrom(
            __DIR__.'/../configs/Tasks.php',
            'tasks'
        );
    }
    

    public function getModulePath()
    {
        return __DIR__.'/./..';
    }
}
