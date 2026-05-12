<?php

namespace Tests\Unit;

use App\Actions\FollowActionCreate;
use App\Modules\Group\Actions\MemberAddSystemPermission;
use App\Modules\Group\Events\MemberAdded;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CreateFollowActionTest extends TestCase
{
    #[Test]
    public function generates_name()
    {
        $action = app()->make(FollowActionCreate::class);

        $name = $action->generateName(MemberAdded::class, MemberAddSystemPermission::class);

        $this->assertEquals('Group\MemberAddSystemPermission_ON_Group\MemberAdded', $name);
    }

}
