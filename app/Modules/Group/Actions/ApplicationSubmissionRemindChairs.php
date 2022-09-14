<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Submission;
use App\Modules\Group\Notifications\ApprovalReminderNotification;
use App\Modules\User\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Lorisleiva\Actions\Concerns\AsCommand;

class ApplicationSubmissionRemindChairs
{
    use AsCommand;

    public $commandSignature = 'submissions:remind-approvers';

    public function handle()
    {
        $approvers = User::permission('ep-applications-approve')
                        ->with('person')
                        ->get()
                        ->map(fn ($u) => $u->person);

        $approvers->each(function ($person) {
            $query = Submission::sentToChair()
                                ->whereDoesntHave(
                                    'judgements',
                                    fn ($q) => $q->where('person_id', $person->id)
                                );

            $waitingSubmissions = $query->get();

            if ($waitingSubmissions->count() > 0) {
                Notification::send($person, new ApprovalReminderNotification($waitingSubmissions));
            }
        });


    }

    public function asCommand(Command $command)
    {
        $this->handle();
    }
}
