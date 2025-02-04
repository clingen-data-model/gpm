<?php

namespace Tests\Feature\Integration\Modules\User\Actions;

use Tests\TestCase;
use Illuminate\Bus\Dispatcher;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Bus;
use App\Modules\User\Jobs\UpdateUser;
use App\Modules\User\Actions\UserUpdate;
use Illuminate\Foundation\Testing\WithFaker;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class UpdateUserTest extends TestCase
{
    use FastRefreshDatabase;

    public function setup():void
    {
        parent::setup();

        $this->user = User::factory()->create(['name' => 'Lana Kane', 'email' => 'lana@archer.com']);
    }
    

    /**
     * @test
     */
    public function updates_user_details()
    {
        UserUpdate::run(id: $this->user->id, name: 'Lana Kain', email: 'lana.kain@archer.com');

        $this->assertDatabaseHas('users', ['name' => 'Lana Kain', 'email' => 'lana.kain@archer.com']);
    }

    /**
     * @test
     */
    public function logs_user_created_event()
    {
        $user = UserUpdate::run(id: $this->user->id, name: 'Lana Kain', email: 'lana.kain@archer.com');

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
        UserUpdate::run(id: $this->user->id, name: 'Lana Kane', email: 'lana@archer.com');

        $this->assertDatabaseMissing('activity_log', ['log_name' => 'users', 'description' => 'User updated']);
    }
}
