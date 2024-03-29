<?php

namespace App\Modules\Group\Actions;
use Illuminate\Validation\Rule;
use App\Modules\Group\Models\Group;
use Illuminate\Validation\Validator;
use App\Modules\Person\Models\Person;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Group\Events\JudgementEvent;
use Lorisleiva\Actions\Concerns\AsController;
use App\Modules\Group\Events\JudgementCreated;
use Illuminate\Validation\ValidationException;
use App\Modules\Group\Events\ApplicationJudgementCreated;

class JudgementCreate
{
    use AsController;

    public function handle(Group $group, Person $person, string $decision, ?string $notes = null)
    {
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
        return $this->handle($group, $request->user()->person, $request->decision, $request->notes);
    }

    public function rules(ActionRequest $request, Group $group): array
    {
        return [
            'decision' => ['required', Rule::in(['request-revisions', 'approve-after-revisions', 'approve'])]
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
