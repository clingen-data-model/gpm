<?php

namespace App\Actions;

use Illuminate\Support\Str;
use App\Models\FollowAction;
use Illuminate\Console\Command;
use Lorisleiva\Actions\Concerns\AsCommand;

class FollowActionCreate
{
    use AsCommand;
    public $commandSignature = 'follow-action:create
                                    {event : event to follow.}
                                    {class : class from which to build follower.}
                                    {--args=* : Args with which to instantiate follower.}
                                    {--description= : Description of the follow action.}
                                    {--name= : Human readable name for the follow action}
                                ';

    public function handle(
        string $eventClass,
        String $follower,
        ?array $args = null,
        ?string $name = null,
        ?string $description = null
    )
    {
        if (!class_exists($follower)) {
            throw new \InvalidArgumentException('Failed to create FollowAction. The follower class '.$follower.' does not appear to exist.');
        }

        return FollowAction::create([
            'name' => $name ?? $this->generateName($eventClass, $follower),
            'event_class' => $eventClass,
            'follower' => $follower,
            'args' => $args,
            'description' => $description
        ]);
    }

    public function asCommand(Command $command)
    {
        $event = $command->argument('event');
        $class = $command->argument('class');
        $name = $command->option('name');
        $description = $command->option('description');

        $args = [];
        array_map(function ($arg) use (&$args) {
            [$key, $val] = [...explode('=', $arg)];
            $args[$key] = $val;
        }, $command->option('args'));

        $errors = false;
        if (!class_exists($event)) {
            $command->error('The event class '.$event.' does not exist');
            $errors = true;
        }

        if (!class_exists($class)) {
            $command->error('The class '.$class.' does not exist');
            $errors = true;
        }

        if ($errors) {
            return;
        }

        try {
            $this->handle(
                eventClass: $event,
                follower: $class,
                args: $args,
                name: $name,
                description: $description
            );
        } catch (\Exception $e) {
            $command->error('failed to instantiate follower object.'."\n\t".$e->getMessage());
        }
    }

    public function generateName(string $eventClass, string $followerClass): string
    {
        $pattern = '/App\\\|Modules\\\|Actions\\\|Events\\\\/';
        $eventNameString = preg_replace($pattern, '', $eventClass);
        $classNameString = preg_replace($pattern, '', $followerClass);

        return $classNameString.'_ON_'.$eventNameString;//Str::kebab();
    }

}
