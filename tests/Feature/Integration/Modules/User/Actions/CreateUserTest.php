<?php

namespace Tests\Feature\Integration\Modules\User\Actions;

use App\Modules\User\Actions\UserCreate;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateUserTest extends TestCase
{
    use RefreshDatabase;

    public function setup(): void
    {
        parent::setup();
    }

    /**
     * @test
     */
    public function creates_user(): void
    {
        UserCreate::run(name: 'Lana Kane', email: 'lana@archer.com');

        $this->assertDatabaseHas('users', ['name' => 'Lana Kane', 'email' => 'lana@archer.com']);
    }

    /**
     * @test
     */
    public function logs_user_created_event(): void
    {
        $user = UserCreate::run(name: 'Lana Kane', email: 'lana@archer.com');

        $this->assertDatabaseHas(
            'activity_log',
            [
                'log_name' => 'users',
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'description' => 'User created: Lana Kane <lana@archer.com> ('.$user->id.')',
            ]
        );
    }
}
