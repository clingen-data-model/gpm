<?php

namespace App\Actions;

use Illuminate\Console\Command;
use App\Modules\Person\Models\Person;
use Lorisleiva\Actions\Concerns\AsCommand;
use App\Notifications\V2ReleaseNotification;
use Illuminate\Support\Facades\Notification;

class V2SendReleaseEmail
{
    use AsCommand;

    public $commandSignature = 'v2:send-email {--except= : Emails to skip} {--limit= : number to send}';

    public function handle($except = [], $limit = 0)
    {
        $peopleQuery = Person::query()
                    ->whereNull('user_id')
                    ->with('memberships', 'memberships.group', 'invite')
                    ->whereNotIn('email', $except);
        if ($limit > 0) {
            $peopleQuery->limit($limit);
        }

        $people = $peopleQuery->get();

        $people->each(function ($person) {
            $person->notify(new V2ReleaseNotification());
        });
    }

    public function asCommand(Command $command)
    {
        $except = $command->option('except') ? explode(',', $command->option('except')) : [];
        $limit = $command->option('limit') ?? 0;

        $this->handle($except, $limit);
    }
}
