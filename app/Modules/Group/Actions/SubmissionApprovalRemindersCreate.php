<?php

namespace App\Modules\Group\Actions;

use App\Modules\User\Models\User;
use App\Modules\Group\Models\Submission;
use Lorisleiva\Actions\Concerns\AsCommand;
use Illuminate\Support\Facades\Notification;
use App\Modules\Group\Notifications\ApprovalReminder;

class SubmissionApprovalRemindersCreate
{
    use AsCommand;

    public $commandSignature = 'submissions:create-approval-reminders';

    public function handle()
    {
        $approvers = User::permission('ep-applications-approve')
                        ->with('person')
                        ->get()
                        ->map(fn ($u) => $u->person);

        $approvers->each(function ($person) {
            $query = Submission::sentToChair()
                                ->with('group')
                                ->whereDoesntHave(
                                    'judgements',
                                    fn ($q) => $q->where('person_id', $person->id)
                                );

            $query->get()->each(function ($submission) use ($person) {
                Notification::send($person, new ApprovalReminder($submission->group, $submission, $person));
            });
        });



    }

}
