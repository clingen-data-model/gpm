<?php

namespace App\Actions;

use App\Modules\Person\Models\Person;
use App\Modules\User\Models\User;
use App\Notifications\UserDefinedMarkdownDatabaseNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsController;

class NotifyPeople
{
    use AsCommand, AsController;

    public $commandSignature = 'notify:system {--message= : text or markdown message.} {--role=* : Only send to people with the specified roles.} {--file= : file to use as message; overrides --message.} {--type=info : Type of notification: success(green), info (blue), warning (yellow), error (red), bland (gray)}';

    public function handle(string $message, string $type, array $roles = [])
    {
        $channel = $channel ?? 'database';

        $userQuery = User::query()->with('person');
        $personQuery = Person::query();
        if (count($roles) > 0) {
            $userQuery->role($roles);
            $personQuery->whereHas('memberships', function ($q) use ($roles) {
                $q->role($roles);
            });
        }

        $notifiables = $userQuery->get()->pluck('person')->merge($personQuery->get());

        Notification::send($notifiables, new UserDefinedMarkdownDatabaseNotification($message, $type));
    }

    public function asCommand(Command $command)
    {
        $message = $command->option('message');
        if ($command->option('file')) {
            if (! file_exists($command->option('file'))) {
                $command->error('The file '.$command->option('file').' Does not exist');
            }
            $message = file_get_contents($command->option('file'));
        }
        while (! $message) {
            $message = $command->ask('What should the notificaiton say?', null);
        }
        $this->handle(trim($message), $command->option('type'), $command->option('role'));
    }

    public function asController(ActionRequest $request)
    {
        $this->handle($request->message, $request->type, preg_split('/[, ]/', $request->roles));
    }

    public function rules()
    {
        return [
            'message' => 'required',
            'type' => 'required|in:info,success,bland,warning,danger',
            'roles' => 'nullable',
        ];
    }
}
