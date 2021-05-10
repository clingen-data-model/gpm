<?php

namespace Tests\Feature\Integration\Modules\User\Jobs;

use Tests\TestCase;
use Illuminate\Bus\Dispatcher;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Bus;
use App\Modules\User\Jobs\CreateUser;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateUserTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->commandBus = app()->make(Dispatcher::class);    
    }
    

    /**
     * @test
     */
    public function creates_user()
    {
        $job = new CreateUser(name: 'Lana Kane', email: 'lana@archer.com');
        $this->commandBus->dispatch($job);

        $this->assertDatabaseHas('users', ['name' => 'Lana Kane', 'email' => 'lana@archer.com']);
    }

    /**
     * @test
     */
    public function logs_user_created_event()
    {
        $job = new CreateUser(name: 'Lana Kane', email: 'lana@archer.com');
        $user  = $this->commandBus->dispatch($job);

        $this->assertDatabaseHas(
            'activity_log', 
            [
                'log_name' => 'users',
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'description' => 'User created: Lana Kane <lana@archer.com> ('.$user->id.')'
            ]
        );
    }
    
    
}
