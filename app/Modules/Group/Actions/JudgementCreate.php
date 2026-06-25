<?php

namespace App\Modules\Group\Actions;
use Illuminate\Validation\Rule;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Submission;
use App\Modules\Person\Models\Person;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\Group\Events\JudgementCreated;
use Illuminate\Validation\ValidationException;
use App\Modules\Group\Events\ApplicationJudgementCreated;

class JudgementCreate
{
    use AsController;

    public function handle(Group $group, Submission $submission, Person $person, string $decision, ?string $notes = null)
    {
        if ((int) $submission->group_id !== (int) $group->id) { abort(404); }
        if ((int) $submission->submission_status_id !== (int) config('submissions.statuses.under-chair-review.id')) 
        {
            throw ValidationException::withMessages([
                'submission_id' => ['A judgement can only be submitted after the submission has been sent to the Chairs.']
            ]);
        }

        $judgement = $group->latestPendingSubmission->judgements()->create([
            'decision' => $decision,
            'notes' => $notes,
            'person_id' => $person->id
        ]);

        event(new ApplicationJudgementCreated($judgement));
        event(new JudgementCreated($judgement));

        return $judgement;
    }

    public function asController(ActionRequest $request, Group $group)
    {
        $submission = $group->submissions()->findOrFail($request->integer('submission_id'));
        return $this->handle($group, submission: $submission, person: $request->user()->person, decision: $request->decision, notes: $request->notes);
    }

    public function rules(ActionRequest $request): array
    {
        return [
            'submission_id' => ['required', 'integer', Rule::exists('submissions', 'id')->where(fn ($query) => $query->where('group_id', $request->group->id)->whereNull('deleted_at'))],
            'decision' => ['required', Rule::in(['request-revisions', 'approve-after-revisions', 'approve'])],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function authorize(ActionRequest $request):bool
    {
        return $request->user()->hasPermissionTo('ep-applications-approve');
    }

}
