<?php

namespace Tests\Feature\End2End\ExpertPanels;

use Tests\TestCase;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

class IndexTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    const URL = '/api/applications';

    public function setup():void
    {
        parent::setup();
        $this->seed();
        \App\Models\Cdwg::factory(10)->create();
        $this->user = User::factory()->create();
        $this->expertPanels = ExpertPanel::factory(25)->randomStep()->create(['created_at'=>Carbon::now()->subDays(10), 'updated_at' => Carbon::now()->subDays(10)]);
    }

    /**
     * @test
     */
    public function sorts_results_by_name()
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $response = $this->json('GET', self::URL.'?sort[field]=name&sort[dir]=desc');

        $this->assertResultsSorted($this->expertPanels->sortByDesc('group.name')->slice(0, 20), $response);
    }

    /**
     * @test
     */
    public function sorts_results_by_current_step()
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $response = $this->json('GET', self::URL.'?sort[field]=current_step&sort[dir]=asc');
        $this->assertResultsSorted($this->expertPanels->sortBy('current_step')->slice(0, 20), $response);
    }
    
    /**
     * @test
     */
    public function sorts_results_by_cdwg_name()
    {
        $this->expertPanels->load('group.parent');
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $response = $this->json('GET', self::URL.'?sort[field]=cdwg.name&sort[dir]=asc');
        $this->assertResultsSorted($this->expertPanels->sortBy('cdwg.name')->slice(0, 20), $response);
    }
    
    /**
     * @test
     */
    public function sorts_results_by_last_activity()
    {
        $this->expertPanels->each(function ($app) {
            $app->logEntries()->create([
                'description' => 'test',
                'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'updated_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            ]);
        });

        $this->expertPanels->load('logEntries');


        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $response = $this->json('GET', self::URL.'?sort[field]=latestLogEntry.created_at&sort[dir]=asc');
        $this->assertResultsSorted($this->expertPanels->sortBy('latestLogEntry.created_at')->slice(0, 20), $response);
    }
    
    /**
     * @test
     */
    public function can_filter_applications_by_last_updated()
    {
        $this->expertPanels->take(5)->each(function ($app) {
            $app->updated_at = $this->faker->dateTimeBetween('-5 days', 'now');
            $app->save();
        });

        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $this->json('get', '/api/applications?where[since]='.Carbon::now()->subDays(6)->toJson())
            ->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }
    

    private function assertResultsSorted(Collection $expected, $response)
    {
        $response->assertOk();
        foreach ($expected->values() as $idx => $app) {
            $this->assertEquals($app->id, $response->original->toArray()['data'][$idx]['id']);
        }
    }
}
