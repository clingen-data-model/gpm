<?php

namespace App\Modules\Group\Actions;
use Illuminate\Validation\Rule;
use App\Modules\Group\Models\Group;
use Illuminate\Validation\Validator;
use App\Modules\Person\Models\Person;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use Illuminate\Validation\ValidationException;

class ApplicationJudgementSubmit
{
    use AsController;

    public function handle(Group $group, Person $person, string $judgement, ?string $notes = null)
    {
        return $group->latestPendingSubmission->judgements()->create([
            'judgement' => $judgement,
            'notes' => $notes,
            'person_id' => $person->id
        ]);
    }

    public function asController(ActionRequest $request, Group $group)
    {
        return $this->handle($group, $request->user()->person, $request->judgement, $request->judgement_notes);
    }

    public function rules(ActionRequest $request, Group $group): array
    {
        return [
            'judgement' => ['required', Rule::in(['request-revisions', 'approve-after-revisions', 'approve'])]
        ];
    }

    public function withValidator(Validator $validator, ActionRequest $request): void
    {
        if (is_null($request->group->latestPendingSubmission)) {
            throw ValidationException::withMessages(['group' => ['This group does not have a pending submission.']]);
        }
    }
    

    public function authorize(ActionRequest $request):bool
    {
        return $request->user()->hasPermissionTo('ep-applications-approve');
    }

}