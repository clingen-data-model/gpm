<?php

namespace Tests\Feature\Integration\Modules\User\Actions;

use Tests\TestCase;
use Illuminate\Bus\Dispatcher;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Bus;
use App\Modules\User\Jobs\CreateUser;
use App\Modules\User\Actions\UserCreate;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateUserTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
    }
    

    /**
     * @test
     */
    public function creates_user()
    {
        UserCreate::run(name: 'Lana Kane', email: 'lana@archer.com');

        $this->assertDatabaseHas('users', ['name' => 'Lana Kane', 'email' => 'lana@archer.com']);
    }

    /**
     * @test
     */
    public function logs_user_created_event()
    {
        $user = UserCreate::run(name: 'Lana Kane', email: 'lana@archer.com');

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
