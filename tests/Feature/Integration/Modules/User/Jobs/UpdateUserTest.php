<?php

namespace Tests\Feature\Integration\Modules\User\Jobs;

use Tests\TestCase;
use Illuminate\Bus\Dispatcher;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Bus;
use App\Modules\User\Jobs\UpdateUser;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateUserTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->user = User::factory()->create(['name' => 'Lana Kane', 'email' => 'lana@archer.com']);

        $this->commandBus = app()->make(Dispatcher::class);    
    }
    

    /**
     * @test
     */
    public function updates_user_details()
    {
        $job = new UpdateUser(id: $this->user->id, name: 'Lana Kain', email: 'lana.kain@archer.com');
        $this->commandBus->dispatch($job);

        $this->assertDatabaseHas('users', ['name' => 'Lana Kain', 'email' => 'lana.kain@archer.com']);
    }

    /**
     * @test
     */
    public function logs_user_created_event()
    {
        $job = new UpdateUser(id: $this->user->id, name: 'Lana Kain', email: 'lana.kain@archer.com');
        $user  = $this->commandBus->dispatch($job);

        $this->assertDatabaseHas(
            'activity_log', 
            [
                'log_name' => 'users',
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'description' => 'User updated'
            ]
        );
    }

    /**
     * @test
     */
    public function does_not_create_activity_log_entry_if_no_change()
    {
        $job = new UpdateUser(id: $this->user->id, name: 'Lana Kane', email: 'lana@archer.com');
        $this->commandBus->dispatch($job);

        $this->assertDatabaseMissing('activity_log', ['log_name' => 'users', 'description' => 'User updated']);
    }
}
