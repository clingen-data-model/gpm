<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;

class MakeModule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module {moduleName : name of the module.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffolds directory structure and provider for a module.';

    /**
     * Directory where modules should be placed.
     */
    protected $modDir;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->modDir = base_path('app/Modules');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $moduleName = $this->argument('moduleName');

        $studlyName = Str::studly($moduleName);

        if (!file_exists($this->modDir)) {
            mkdir($this->modDir);
        }

        $newModDir = $this->modDir.'/'.$studlyName;

        if (!file_exists($newModDir)) {
            mkdir($newModDir);
        }

        $dirs = ['Events', 'Exceptions', 'Jobs', 'Models', 'Providers'];
        
        foreach ($dirs as $dir) {
            $newDir = $newModDir.'/'.$dir;
            if (!file_exists($newDir)) {
                mkdir($newDir);
            }
        }

        $providerName = $studlyName.'ModuleServiceProvider.php';
        $providerCode = str_replace('[STUDLY_NAME]', $studlyName, file_get_contents(app_path('Console/Commands/stubs/ModuleProvider.stub')));
        file_put_contents($newModDir.'/Providers/'.$providerName, $providerCode);

        $this->info('Scaffolded out '.$studlyName.' module in '.$newModDir);
        $this->info("Don't forget to add add the service provider to config/app.php: \n App\Modules\\$studlyName\\Providers\\$providerName::class");

        
        return 0;
    }
}
