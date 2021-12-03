<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsController;

class GroupDelete
{
    use AsController;
    use AsCommand;

    public string $commandSignature = 'group:delete {groupId : id or uuid of group} {--force= : force the delete without confirmation}';
    public string $commandDescription = 'Deletes group identified by id, uuid, or name';

    public function handle(Group $group)
    {
        $group->delete();
    }

    public function asController(ActionRequest $request, Group $group)
    {
        $this->handle($group);
        
        return response('group deleted', 200);
    }

    public function asCommand(Command $command)
    {
        $identifier = $command->argument('groupId');
        $group = null;
        if (is_numeric($identifier)) {
            $group = Group::find($identifier);
        } else {
            $group = Group::findByUuid($identifier);
        }

        if (!$group) {
            $command->error('Group with identifier '.$identifier.' not found');
            return;
        }

        if (!$command->option('force')) {
            $confMessage = 'You are about to delete the "'.$group->name.'" group. Do you want to continue?';
            if (!$command->confirm($confMessage, false)) {
                $command->info('Canceling group delete.');
                // return 1;
            }
        }
        
        $this->handle($group);
        
        $command->info('Grup "'.$group->name.'" has been deleted.');
    }
    

    public function authorize(ActionRequest $request, Group $group): bool
    {
        return Auth::user()->can('delete', $group);
    }
}
