<?php

namespace Tests\Unit;

use App\Modules\Group\Actions\HandleGroupCommand;
use App\Modules\Group\Models\Group;
use PHPUnit\Framework\TestCase;
use Tests\Dummies\SetGroupName;

class HandleGroupCommandTest extends TestCase
{
    /**
     * @test
     */
    public function runs_group_command(): void
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
