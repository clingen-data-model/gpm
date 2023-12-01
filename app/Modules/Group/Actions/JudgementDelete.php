<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Events\JudgementDeleted;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Judgement;
use Lorisleiva\Actions\ActionRequest;
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

    public function authorize(ActionRequest $request): bool
    {
        $judgement = Judgement::findOrFail($request->id);

        return $request->user()->hasPermissionTo('ep-applications-manage')
            || $request->user()->person->id == $judgement->person_id;
    }
}
