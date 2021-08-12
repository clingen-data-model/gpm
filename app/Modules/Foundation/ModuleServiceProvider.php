<?php

namespace App\Modules\Foundation;

use RegexIterator;
use App\Listeners\RecordEvent;
use RecursiveIteratorIterator;
use App\Events\RecordableEvent;
use RecursiveDirectoryIterator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

abstract class ModuleServiceProvider extends ServiceProvider
{
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
        $this->registerRecordableEventListeners();
        $this->registerExpliciteListeners();
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutes();
    }

    private function registerRecordableEventListeners()
    {
        $eventClasses = $this->classGetter->atPath($this->getEventPath());
        foreach ($eventClasses as $class) {
            if (is_subclass_of($class, RecordableEvent::class)) {
                Event::listen($class, [RecordEvent::class, 'handle']);
            }
        }
    }

    private function registerExpliciteListeners()
    {
        foreach ($this->listeners as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, [$listener, 'handle']);
            }
        }        
    }

    protected function getRoutesPath()
    {
        return __DIR__.'/../routes';
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

    protected abstract function getEventPath();
}
