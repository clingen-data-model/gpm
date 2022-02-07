<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use App\Tasks\Models\Task;
use Laravel\Sanctum\Sanctum;
use App\Tasks\Actions\TaskCreate;
use App\Tasks\Actions\TaskComplete;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GetTasksTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        
        $this->expertPanel = ExpertPanel::factory()->vcep()->create();
        $this->pendingTask = (new TaskCreate)->handle($this->expertPanel->group, config('tasks.types.sustained-curation-review.id'));
        $this->completedTask = (new TaskCreate)->handle($this->expertPanel->group, config('tasks.types.sustained-curation-review.id'));
        (new TaskComplete)->handle($this->completedTask);

        $this->user = $this->setupUser();
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function retrieves_all_tasks()
    {
        $this->json('get', '/api/groups/'.$this->expertPanel->group->uuid.'/tasks')
            ->assertStatus(200)
            ->assertJsonCount(2);
    }

    /**
     * @test
     */
    public function retrieves_pending_tasks()
    {
        $this->json('get', '/api/groups/'.$this->expertPanel->group->uuid.'/tasks?pending')
            ->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'id' => $this->pendingTask->id
            ]);
    }

    /**
     * @test
     */
    public function retrieves_complete_tasks()
    {
        $this->json('get', '/api/groups/'.$this->expertPanel->group->uuid.'/tasks?completed')
            ->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'id' => $this->completedTask->id
            ]);
    }
}
