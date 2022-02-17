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

    public $commandSignature = 'v2:send-email {--except= : Emails to skip}';

    public function handle($except = [])
    {
        $people = Person::query()
                    ->whereNull('user_id')
                    ->with('memberships', 'memberships.group', 'invite')
                    ->whereNotIn('email', $except)
                    ->get();

        $people->each(function ($person) {
            $person->notify(new V2ReleaseNotification());
        });
    }

    public function asCommand(Command $command)
    {
        $except = $command->option('except') ? explode(',', $command->option('except')) : [];

        $this->handle($except);
    }
}
