<?php

namespace Tests\Unit;

use App\Actions\CreateFollowAction;
use App\Modules\Group\Actions\MemberAddSystemPermission;
use App\Modules\Group\Events\MemberAdded;
use PHPUnit\Framework\TestCase;

class CreateFollowActionTest extends TestCase
{
    /**
     * @test
     */
    public function generates_name()
    {
        $action = app()->make(CreateFollowAction::class);
        
        $name = $action->generateName(MemberAdded::class, MemberAddSystemPermission::class);

        $this->assertEquals('Group\MemberAddSystemPermission_ON_Group\MemberAdded', $name);
    }
    
}
