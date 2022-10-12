<?php

namespace App\Modules\Group\Actions;

use Illuminate\Validation\Rule;
use App\Modules\Group\Models\Group;
use Illuminate\Validation\Validator;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Group\Models\Judgement;
use App\Modules\Group\Events\JudgementDeleted;
use Illuminate\Validation\ValidationException;
    use Lorisleiva\Actions\Concerns\AsController;

class JudgementDelete
{
    use AsController;

    public function handle(ActionRequest $request, Group $group, $judgementId): void
    {
        $judgement = Judgement::findOrFail($judgementId);
        $judgement->delete();
        event(new JudgementDeleted($judgement));
    }

    public function authorize(ActionRequest $request):bool
    {
        $judgement = Judgement::findOrFail($request->id);
        return $request->user()->hasPermissionTo('ep-applications-manage')
            || $request->user()->person->id == $judgement->person_id;
    }

}
