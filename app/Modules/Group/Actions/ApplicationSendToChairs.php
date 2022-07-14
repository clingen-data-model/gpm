<?php

namespace App\Modules\Group\Actions;

use Carbon\Carbon;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\ExpertPanel\Actions\NextActionCreate;
use App\Modules\Group\Events\ApplicationSentToChairs;

class ApplicationSendToChairs
{
    public function __construct(private NextActionCreate $createNextAction)
    {
    }
    

    public function handle(Group $group, ?string $additionalComments)
    {
        $this->notifyChairs($group, $additionalComments);
        $this->createNextActionForChairs($group);

        // Create Tasks for each chair (?)
        // NOT YET
        
        event(new ApplicationSentToChairs(
            group: $group, 
            comments: $group->pendingComments, 
            additionalComments: $additionalComments)
        );

        return $group;
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->hasPermissionTo('ep-applications-manage');
    }

    private function notifyChairs(Group $group, $additionalComments): void
    {
        // TODO: Send notification(s) to the chairs
        Log::warning(__METHOD__.' is not implemented');

    }
    

    private function createNextActionForChairs(Group $group): void
    {
        $this->createNextAction->handle(
            expertPanel: $group->expertPanel,
            entry: 'Review Step '.$group->expertPanel->current_step.' application.',
            dateCreated: Carbon::now()->format('Y-m-d H:i:s'),
            assignedTo: config('next_actions.assignees.chairs.id'),
            typeId: config('next_actions.types.chair-review.id'),
        );
    }
    
    
}
