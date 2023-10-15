<?php

namespace Tests\Feature\Integration;

use App\Jobs\Pipes\UseDatabaseTransactions;
use App\Modules\Group\Models\Group;
use App\Modules\User\Models\User;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UseDatabaseTransactionsTest extends TestCase
{
    use RefreshDatabase;

    public function setup(): void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->dispatcher = app()->make(Dispatcher::class);
        $this->dispatcher->pipeThrough([UseDatabaseTransactions::class]);
    }

    /**
     * @test
     */
    public function rollsback_transaction_on_error(): void
    {
        $user = User::factory()->make();

        $this->expectException(\Exception::class);
        $this->dispatcher->dispatch(function () use ($user) {
            $user->save();
            throw new \Exception('blarchgh!');
        });

        $this->assertDatabaseMissing('users', $user->getAttributes());
    }

    /**
     * @test
     */
    public function commits_transaction_if_no_exceptions(): void
    {
        $user = User::factory()->make();

        $this->dispatcher->dispatch(function () use ($user) {
            $user->save();
            Group::factory()->create(['name' => 'test application']);
        });

        $this->assertDatabaseHas('users', $user->getAttributes());
        $this->assertDatabaseHas('groups', ['name' => 'test application']);
    }
}
