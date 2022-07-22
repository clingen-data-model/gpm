<?php

namespace App\Console\Commands\Dev;

use Illuminate\Console\Command;
use App\Actions\CreateFollowAction;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Events\MemberAdded;
use App\Modules\Group\Events\MemberRemoved;
use App\Modules\Group\Events\MemberRetired;
use App\Modules\Person\Actions\PermissionAdd;
use App\Exceptions\FollowActionDuplicateException;
use App\Modules\Group\Actions\MemberAddSystemPermission;
use App\Modules\Group\Actions\MemberRemoveSystemPermission;

class AssignCommentPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:assign-comment-perms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        
        $groups = Group::find([151, 133]);

        foreach ($groups as $group) {
            $this->assignCommentPermissionToGroup($group);
            $this->addFollowActions($group);
        }

        foreach ($groups[1]->chairs as $chair) {
            $chair->person->user->givePermissionTo('ep-applications-approve');
        }

        return 0;
    }

    private function assignCommentPermissionToGroup(Group $group): void
    {
        $group->members->each(fn ($m) => app()->make(PermissionAdd::class)->handle($m->person, 'ep-applications-comment'));
    }

    private function addFollowActions(Group $group): void
    {
        $createFollowAction = app()->make(CreateFollowAction::class);
        try {
            $createFollowAction->handle(
                eventClass: MemberAdded::class, 
                follower: MemberAddSystemPermission::class, 
                args: ['permissionName' => 'ep-applications-comment', 'groupId'=>$group->id],
                name: $group->display_name.'members get ep-applications-comment when added.',
                description: 'Automatically grants ep-applications-comment system permission when a person is added to a member of '.$group->display_name
            );
        } catch (FollowActionDuplicateException $e) {
            $this->error($e->getMessage());
        }

        try {
            $createFollowAction->handle(
                eventClass: MemberRemoved::class, 
                follower: MemberRemoveSystemPermission::class, 
                args: ['permissionName' => 'ep-applications-comment', 'groupId'=>$group->id],
                name: $group->display_name.': remove ep-applications-comment when member removed.',
                description: 'Automatically revokes ep-applications-comment system permission when a member is removed from '.$group->display_name
            );
        } catch (FollowActionDuplicateException $e) {
            $this->error($e->getMessage());
        }
        try {
            $createFollowAction->handle(
                eventClass: MemberRetired::class, 
                follower: MemberRemoveSystemPermission::class, 
                args: ['permissionName' => 'ep-applications-comment', 'groupId'=>$group->id],
                name: $group->display_name.': remove ep-applications-comment when member retired.',
                description: 'Automatically revokes ep-applications-comment system permission when a member is retired from '.$group->display_name
            );
        } catch (FollowActionDuplicateException $e) {
            $this->error($e->getMessage());
        }
    }
    
    
}
