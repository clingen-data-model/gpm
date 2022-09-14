<?php

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;
// use Illuminate\Console\Concerns\CreatesMatchingTest;

// #[AsCommand(name: 'make:action')]
class ActionMakeCommand extends GeneratorCommand
{
    // use CreatesMatchingTest;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Makes a new Action';


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:action {name} {--as-controller : Use action as controller.} {--as-command : Use action as command.} {--as-listener= : Use action as listener.}';
    
    protected function getStub()
    {
        return app_path('Console/stubs/action.stub');
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Actions';
    }

    protected function buildClass($name)
    {        
        try {
            $replace = $this->buildReplacements();
            $output = str_replace(
                array_keys($replace), array_values($replace), parent::buildClass($name)
            );
            return $output;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }


    }
    

    protected function buildReplacements()
    {
        $replacements = [
            '{{ as_controller_methods }}' => '',
            '{{ command_signature }}' => '',
            '{{ as_command_methods }}' => '',
            '{{ as_listener_methods }}' => ''
        ];
        $useStatements = [];
        $useTraits = [];

        if ($this->option('as-controller')) {
            $useStatements = [
                'use Lorisleiva\Actions\ActionRequest;',
                'use Lorisleiva\Actions\Concerns\AsController;',
            ];
            $useTraits = ["use AsController;"];

            $replacements['{{ as_controller_methods }}'] = file_get_contents($this->getControllerMethodsStub());
        }

        if ($this->option('as-command') === true) {

            $useStatements[] = 'use Lorisleiva\Actions\Concerns\AsCommand';
            $useStatements[] = 'use Illumniate\Console\Command';
            $useTraits[] = "use AsCommand;";
            $replacements['{{ command_signature }}'] = "\t".'$commandSignature = \'command\'';
            $replacements['{{ as_command_methods }}'] = file_get_contents($this->getCommandMethodsStub());
        }

        if ($this->option('as-listener')) {

            $useStatements[] = 'use Lorisleiva\Actions\Concerns\AsListener;';
            $useTraits[] = "use AsListener;";
            $replacements['{{ event_type }}'] = $this->option('as-listener');
            $replacements['{{ as_listener_methods }}'] = file_get_contents($this->getListenerMethodsStub());
        }
        
        $replacements['{{ use_statements }}'] = implode("\n    ", $useStatements);
        $replacements['{{ use_traits }}'] = implode("\n", $useTraits);

        return $replacements;
    }

    private function getControllerMethodsStub()
    {
        return app_path('Console/stubs/action_controller_methods.stub');
    }

    private function getCommandMethodsStub()
    {
        return app_path('Console/stubs/action_command_methods.stub');
    }

    private function getListenerMethodsStub()
    {
        return app_path('Console/stubs/action_listener_methods.stub');
    }

}
