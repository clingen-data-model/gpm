<?php

namespace Tests\Feature\End2End\Groups;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Tasks\Actions\TaskComplete;
use App\Tasks\Actions\TaskCreate;
use Database\Seeders\TaskTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetTasksTest extends TestCase
{
    use RefreshDatabase;

    public function setup(): void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->runSeeder(TaskTypeSeeder::class);

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
    public function retrieves_all_tasks(): void
    {
        $this->json('get', '/api/groups/'.$this->expertPanel->group->uuid.'/tasks')
            ->assertStatus(200)
            ->assertJsonCount(2);
    }

    /**
     * @test
     */
    public function retrieves_pending_tasks(): void
    {
        $this->json('get', '/api/groups/'.$this->expertPanel->group->uuid.'/tasks?pending')
            ->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'id' => $this->pendingTask->id,
            ]);
    }

    /**
     * @test
     */
    public function retrieves_complete_tasks(): void
    {
        $this->json('get', '/api/groups/'.$this->expertPanel->group->uuid.'/tasks?completed')
            ->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'id' => $this->completedTask->id,
            ]);
    }
}
