<?php

namespace App\Actions;

use Illuminate\Console\Command;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use Illuminate\Mail\Events\MessageSent;
use Lorisleiva\Actions\Concerns\AsCommand;
use App\Notifications\V2ReleaseNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

class V2SendReleaseEmail
{
    use AsCommand;

    public $commandSignature = 'v2:send-email {--except= : Emails to skip} {--limit= : number to send} {--dry-run : set mailer to log and disable database storage.} {--only-send-to= : email address of person to send email to; will not send to others.}';

    public function handle($people)
    {
        $people->each(function ($person) {
            $person->notify(new V2ReleaseNotification());
        });
    }

    public function asCommand(Command $command)
    {
        $except = $command->option('except') ? explode(',', $command->option('except')) : [];
        $limit = $command->option('limit') ?? 0;
        $dryRun = $command->option('dry-run');
        $recipient = $command->option('only-send-to');

        if ($dryRun) {
            $command->info('setting mailer to log...');
            config(['mail.default' => 'log']);
            $command->info('disabling database outgoing mail storage...');
            Event::forget(MessageSent::class);
        }
        
        if ($recipient) {
            $command->info('retrieving person with email address '.$recipient.'...');
            $people = Person::where('email', $recipient)->get();
        } else {
            if (!$dryRun) {
                if (!$command->confirm('You are about to send the emails ot everyone.  this could be a cluster fuck.  Do you want to continue?', false)) {
                    return false;
                }
            }
            $people = $this->getRecipients($except, $limit);
        }

        $this->handle($people);
    }

    private function getRecipients($except = [], $limit = 0)
    {
        $peopleQuery = Person::query()
                    ->whereNull('user_id')
                    ->with('memberships', 'memberships.group', 'invite')
                    ->whereNotIn('email', $except);
        if ($limit > 0) {
            $peopleQuery->limit($limit);
        }

        return $peopleQuery->get();
    }
}
