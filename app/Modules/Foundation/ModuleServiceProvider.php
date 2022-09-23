<?php

namespace App\Modules\Foundation;

use Exception;
use RegexIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use App\Actions\FollowActionRun;
use App\Providers\RegisterFollowActionEventListeners;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Lorisleiva\Actions\Facades\Actions;
use App\Providers\RegistersExplicitEventListeners;
use App\Providers\RegistersRecordableEventListeners;
use App\Providers\RegistersPublishableEventListeners;

abstract class ModuleServiceProvider extends ServiceProvider
{
    use RegistersRecordableEventListeners;
    use RegistersPublishableEventListeners;
    use RegistersExplicitEventListeners;
    use RegisterFollowActionEventListeners;

    protected $classGetter;
    protected $listeners = [
        // EventClass::class => [ListenerClass::class]
    ];

    public function __construct($app)
    {
        parent::__construct($app);
        $this->classGetter = new ClassGetter;
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $eventClasses = $this->classGetter->atPath($this->getEventPath());
        $this->registerRecordableEventListeners($eventClasses);
        $this->registerPublishableEventListeners($eventClasses);
        $this->registerExplicitListeners($this->listeners);
        $this->registerFollowActionListeners($eventClasses);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutes();
        $this->registerCommands();
    }

    protected function registerPolicies()
    {
        foreach ($this->policies as $key => $value) {
            if (!class_exists($key)) {
                throw new Exception('You are trying to register a policy for a class that does not exist: '.$key);
            }
            if (!class_exists($key)) {
                throw new Exception('You are trying to register a policy that does not exist: '.$value);
            }
            Gate::policy($key, $value);
        }
    }

    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            Actions::registerCommands($this->getActionsPath());
        }
    }

    protected function loadRoutes()
    {
        $routesDir = $this->getRoutesPath();
        if (!file_exists($routesDir) || !is_dir($routesDir)) {
            return;
        }
        foreach (scandir($routesDir) as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }
            $this->loadRoutesFrom($routesDir.'/'.$file);
        }
    }

    abstract protected function getModulePath();

    protected function getEventPath()
    {
        return $this->getModulePath().'/Events';
    }

    protected function getRoutesPath()
    {
        return $this->getModulePath().'/routes';
    }

    protected function getActionsPath()
    {
        return $this->getModulePath().'/Actions';
    }
}
