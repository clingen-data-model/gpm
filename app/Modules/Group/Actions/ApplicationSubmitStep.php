<?php

namespace App\Modules\Group\Actions;

use App\Modules\ExpertPanel\Actions\NextActionComplete;
use Exception;
use Carbon\Carbon;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Group\Models\Submission;
use App\Modules\Group\Models\SubmissionType;
use Lorisleiva\Actions\Concerns\AsController;
use Illuminate\Validation\ValidationException;
use App\Modules\Group\Events\ApplicationStepSubmitted;

class ApplicationSubmitStep
{
    use AsController;
    
    public function handle(Group $group, Person $submitter, ?String $notes = null): Submission
    {
        $submissionType = $this->resolveSubmissionType($group);
        
        if ($group->hasApprovedSubmissionFor($submissionType->id)) {
            $message = 'This group\'s '.$submissionType->name.' has already been approved.';
            throw ValidationException::withMessages(['group' => $message]);
        }

        $submission = new Submission([
            'submission_type_id' => $submissionType->id,
            'submission_status_id' => config('submissions.statuses.pending.id'),
            'notes' => $notes,
            'submitter_id' => $submitter->id,
            //submission_status_id defaults to 1
        ]);

        $submission = $group->submissions()->save($submission);
        
        // Do a couple of things I shouldn't have to do:
        // 1. fresh the instance and load the status and type (b/c not eager loading for some reason)
        $submission = $submission->fresh()->load(['status', 'type']);
        // 2. change the model's wasRecentlyCreated attriute to true so the response status is 201.
        $submission->wasRecentlyCreated = true;

        $group = $this->setReceivedDate($group);
        
        event(new ApplicationStepSubmitted($group, $submission));

        $group->expertPanel->nextActions()->ofType(config('next_actions.types.make-revisions.id'))
            ->each(function ($nextAction) use ($group){
                app()->make(NextActionComplete::class)->handle(
                    expertPanel: $group->expertPanel,
                    nextAction: $nextAction,
                    dateCompleted: Carbon::now()
                );
            });

        return $submission;
    }

    public function asController(ActionRequest $request, Group $group)
    {
        $submission = $this->handle($group, $request->user()->person, $request->notes);
        return $submission;
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('updateApplicationAttribute', $request->group);
    }

    private function setReceivedDate(Group $group)
    {
        switch ($group->expertPanel->current_step) {
            case 2:
            case 3:
                return;
            case 1:
                $group->expertPanel->step_1_received_date = Carbon::now();
                break;
            case 4:
                $group->expertPanel->step_4_received_date = Carbon::now();
                break;
        }
        $group->expertPanel->save();
        
        return $group;
    }

    private function resolveSubmissionType($group): object
    {
        switch ($group->expertPanel->current_step) {
            case 1:
                return (object) config('submissions.types.application.definition');
            case 4:
                return (object) config('submissions.types.application.sustained-curation');
            default:
                throw new Exception('Only steps 1 and 4 can be submitted for approval in the GPM.');
                break;
        }
    }
}
