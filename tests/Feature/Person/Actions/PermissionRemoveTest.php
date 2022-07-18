<?php

namespace Tests\Feature\Person\Actions;

use Tests\TestCase;
use InvalidArgumentException;
use App\Modules\User\Models\User;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Models\Person;
use Database\Factories\InviteFactory;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Person\Actions\PermissionAdd;
use App\Modules\Person\Actions\PermissionRemove;
use App\Modules\Person\Events\InviteRedeemed;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PermissionRemoveTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->action = app()->make(PermissionRemove::class);
    }
    

    /**
     * @test
     */
    public function throws_Exception_if_permission_not_found()
    {
        $user = $this->setupUserWithPerson();
        
        $this->expectException(InvalidArgumentException::class);
        $this->action->handle($user->person, 'skate-ramp');
    }

    /**
     * @test
     */
    public function throws_exception_if_perm_scoped_for_group()
    {
        $person = new Person();
        $this->setupPermission(['test-perm'], 'group');

        $this->expectException(InvalidArgumentException::class);
        $this->action->handle($person, 'test-perm');
    }

    /**
     * @test
     */
    public function removes_permission_to_acivated_person()
    {
        $user = $this->setupUserWithPerson();
        $perms = $this->setupPermission(['test-perm'], 'system');
        $user->givePermissionTo('test-perm');

        $this->action->handle($user->person, 'test-perm');

        $this->assertDatabaseMissing('model_has_permissions', [
            'model_type' => User::class,
            'model_id' => $user->id,
            'permission_id' => $perms[0]->id
        ]);
    }
}
