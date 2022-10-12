<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Events\JudgementUpdated;
use Illuminate\Validation\Rule;
use App\Modules\Group\Models\Group;
use Illuminate\Validation\Validator;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Group\Models\Judgement;
use Illuminate\Validation\ValidationException;
    use Lorisleiva\Actions\Concerns\AsController;

class JudgementUpdate
{
    use AsController;

    public function handle(Judgement $judgement, string $decision, ?string $notes = null): Judgement
    {
        $judgement->update([
            'decision' => $decision,
            'notes' => $notes
        ]);

        event(new JudgementUpdated($judgement));

        return $judgement;
    }

    public function asController(ActionRequest $request, Group $group, Judgement $judgement)
    {
        return $this->handle(
            judgement: Judgement::find($request->id),
            decision: $request->decision,
            notes: $request->notes
        );
    }

    public function rules(ActionRequest $request): array
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
        $judgement = Judgement::find($request->id);
        return $request->user()->hasPermissionTo('ep-applications-manage')
            || $request->user()->person->id == $judgement->person_id;
    }

}
