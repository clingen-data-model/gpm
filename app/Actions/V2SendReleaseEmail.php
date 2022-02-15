<?php

namespace App\Actions;

use App\Modules\Person\Models\Person;
use Lorisleiva\Actions\Concerns\AsCommand;
use App\Notifications\V2ReleaseNotification;
use Illuminate\Support\Facades\Notification;

class V2SendReleaseEmail
{
    use AsCommand;

    public $commandSignature = 'v2:send-email';

    public function handle()
    {
        $people = Person::query()
                    ->whereNull('user_id')
                    ->with('memberships', 'memberships.group', 'invite')
                    ->get();

        $people->each(function ($person) {
            $person->notify(new V2ReleaseNotification());
        });
    }
}
