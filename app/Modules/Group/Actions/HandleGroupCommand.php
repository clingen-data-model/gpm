<?php
namespace App\Modules\Group\Actions;

use Illuminate\Support\Str;
use App\Modules\Group\Models\Group;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class HandleGroupCommand
{
    use AsController;

    public function handle(Group $group, string $command, array $args)
    {
        $action = $this->resolveCommand($command);

        return $action->handle($group, ...$args);
    }

    public function asController(ActionRequest $request, Group $group)
    {
        $command = $request->command;
        $args = $request->except(['command']);

        return $this->handle($group, $command, $args);
    }

    public function rules(): array
    {

        /**
         * TODO: build validation based on resolved action
         */

        return [
           'command' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!class_exists($this->resolveCommandClass($value))) {
                        return 'The command '.$value.' is not supported.';
                    }
                }
            ],
        ];
    }
    
    public function authorize(ActionRequest $request): bool
    {
        /**
         * TODO: Authorize based on resolved action
         */
        if (!$request->command) {
            return true;
        }

        $action = $this->resolveCommand($request->command);
        if (method_exists($action, 'authorize')) {
            return $action->authorize($request);
        }
        
        return true;
    }

    private function resolveCommand($commandString)
    {
        return app()->make($this->resolveCommandClass($commandString));
    }

    private function resolveCommandClass($commandString): string
    {
        $namespaceParts = array_map(function ($i) { return Str::studly($i); }, explode('.', $commandString));
        $className = '\\'.implode('\\', $namespaceParts);
        
        return $className;
    }
}
