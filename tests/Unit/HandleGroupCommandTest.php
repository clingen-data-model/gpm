<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Actions\HandleGroupCommand;
use Tests\Dummies\SetGroupName;

class HandleGroupCommandTest extends TestCase
{
    /**
     * @test
     */
    public function runs_group_command()
    {
        $cmd = new SetGroupName;

        $action = new HandleGroupCommand;

        $group = new Group();

        $group = $action->handle(
                    group: $group, 
                    command: 'tests.dummies.setGroupName', 
                    args: ['name' => 'test name']
                );

        $this->assertEquals('test name', $group->name);
    }
}
