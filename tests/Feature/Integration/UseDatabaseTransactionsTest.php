<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Contracts\Bus\Dispatcher;
use App\Jobs\Pipes\UseDatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Application\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UseDatabaseTransactionsTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->dispatcher = app()->make(Dispatcher::class);
        $this->dispatcher->pipeThrough([UseDatabaseTransactions::class]);
    }

    /**
     * @test
     */
    public function rollsback_transaction_on_error()
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
    public function commits_transaction_if_no_exceptions()
    {
        $user = User::factory()->make();

        $this->dispatcher->dispatch(function () use ($user) {
            
            $user->save();
            Application::factory()->create(['working_name' => 'test application']);
        });

        $this->assertDatabaseHas('users', $user->getAttributes());
        $this->assertDatabaseHas('applications', ['working_name' => 'test application']);
    }
    
    
    
}
