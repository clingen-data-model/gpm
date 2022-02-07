<?php

namespace Tests\Feature\End2End\Tasks;

use Tests\TestCase;
use App\Tasks\Actions\TaskCreate;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class TaskIndexTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->user = $this->setupUser();
        $this->vcep1 = ExpertPanel::factory()->vcep()->create();
        $this->task1 = (new TaskCreate)->handle($this->vcep1->group, 'sustained-curation-review');
        $this->task2 = (new TaskCreate)->handle($this->vcep1->group, 'sustained-curation-review');

        $this->vcep2 = ExpertPanel::factory()->vcep()->create();
        $this->task3 = (new TaskCreate)->handle($this->vcep2->group, 'sustained-curation-review');
        $this->task4 = (new TaskCreate)->handle($this->vcep2->group, 'sustained-curation-review');

        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function can_get_all_tasks_for_a_single_assignee()
    {
        $this->makeRequest([
            'where' => [
                'assignee_id' => $this->vcep1->group->id,
                'assignee_type' => get_class($this->vcep1->group)
            ]
        ])
        ->assertStatus(200)
        ->assertJsonCount(2);
    }

    /**
     * @test
     */
    public function can_get_all_tasks_for_multiple_assignees_of_the_same_type()
    {
        $vcep3 = ExpertPanel::factory()->vcep()->create();
        $task1 = (new TaskCreate)->handle($vcep3->group, 'sustained-curation-review');
        $task2 = (new TaskCreate)->handle($vcep3->group, 'sustained-curation-review');

        $this->makeRequest([
            'where' => [
                'assignee_id' => [$this->vcep1->group->id, $vcep3->group->id],
                'assignee_type' => get_class($this->vcep1->group)
            ]
        ])
        ->assertStatus(200)
        ->assertJsonCount(4)
        ->assertJsonFragment([
            'assignee_id' => $this->vcep1->group_id,
        ])
        ->assertJsonFragment([
            'assignee_id' => $vcep3->group_id,
        ])
        ->assertJsonMissing([
            'assignee_id' => $this->vcep2->id
        ]);
    }
    

    private function makeRequest($data = [])
    {
        $url = '/api/tasks';
        $queryString = http_build_query($data);
        if ($queryString) {
            $url .= '?'.$queryString;
        }
        dump($url);
        return $this->json('get', $url);
    }
}
