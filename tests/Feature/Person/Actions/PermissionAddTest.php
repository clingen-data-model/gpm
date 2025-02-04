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
use App\Modules\Person\Events\InviteRedeemed;
use Carbon\Carbon;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class PermissionAddTest extends TestCase
{
    use FastRefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->action = app()->make(PermissionAdd::class);
    }
    

    /**
     * @test
     */
    public function throws_Exception_if_permission_not_found()
    {
        $person = new Person();
        
        $this->expectException(InvalidArgumentException::class);
        $this->action->handle($person, 'skate-ramp');
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
    public function adds_permission_to_acivated_person()
    {
        $user = $this->setupUserWithPerson();
        $perms = $this->setupPermission(['test-perm'], 'system');

        $this->action->handle($user->person, 'test-perm');

        $this->assertDatabaseHas('model_has_permissions', [
            'model_type' => User::class,
            'model_id' => $user->id,
            'permission_id' => $perms[0]->id
        ]);
    }

    /**
     * @test
     */
    public function adds_follow_action_if_person_not_activated()
    {
        $person = Person::factory()->create();
        $perms = $this->setupPermission(['test-perm'], 'system');

        $this->action->handle($person, 'test-perm');

        $this->assertDatabaseHas('follow_actions', [
            'name' => 'Grant test-perm permission to '.$person->name.' when invite redeemed.',
            'event_class' => InviteRedeemed::class,
            'follower' => PermissionAdd::class,
            'args->personId' => $person->id,
            'args->permissionName' => 'test-perm',
        ]);
    }

    /**
     * @test
     */
    public function runs_as_follow_action()
    {
        // Setup
        Carbon::setTestNow('2022-07-18');
        $person = Person::factory()->create();
        $this->assertNull($person->user);
        $invite = Invite::factory()->create([
            'person_id' => $person->id
        ]);
        $perms = $this->setupPermission(['test-perm'], 'system');

        $this->action->handle($person, 'test-perm');

        $user = $this->setupUser();
        $person->update(['user_id' => $user->id]);

        // Set up and fire the event
        $irEvent = new InviteRedeemed($invite, $user);
        event($irEvent);

        $this->assertDatabaseHas('model_has_permissions', [
            'model_type' => User::class,
            'model_id' => $user->id,
            'permission_id' => $perms[0]->id
        ]);

        $this->assertDatabaseHas('follow_actions', [
            'name' => 'Grant test-perm permission to '.$person->name.' when invite redeemed.',
            'event_class' => InviteRedeemed::class,
            'follower' => PermissionAdd::class,
            'args->personId' => $person->id,
            'args->permissionName' => 'test-perm',
            'completed_at' => Carbon::now()
        ]);
    }
    
    
}
