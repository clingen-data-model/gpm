<?php

namespace Tests\Feature\End2End\Applications;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\WithFaker;
use App\Domain\Application\Models\Application;
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
        $this->applications = Application::factory(25)->randomStep()->create(['created_at'=>Carbon::now()->subDays(10), 'updated_at' => Carbon::now()->subDays(10)]);
    }

    /**
     * @test
     */
    public function sorts_results_by_name()
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $response = $this->json('GET', self::URL.'?sort[field]=name&sort[dir]=desc');
        
        $this->assertResultsSorted($this->applications->sortBy('working_name')->slice(0,20), $response);
    }

    /**
     * @test
     */
    public function sorts_results_by_current_step()
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $response = $this->json('GET', self::URL.'?sort[field]=current_step&sort[dir]=asc');
        $this->assertResultsSorted($this->applications->sortBy('current_step')->slice(0,20), $response);
    }
    
    /**
     * @test
     */
    public function sorts_results_by_cdwg_name()
    {
        $this->applications->load('cdwg');
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $response = $this->json('GET', self::URL.'?sort[field]=cdwg.name&sort[dir]=asc');
        $this->assertResultsSorted($this->applications->sortBy('cdwg.name')->slice(0,20), $response);
    }
    
    /**
     * @test
     */
    public function sorts_results_by_last_activity()
    {
        $this->applications->each(function ($app) {
            $app->logEntries()->create([
                'description' => 'test',
                'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'updated_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            ]);
        });

        $this->applications->load('logEntries');


        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $response = $this->json('GET', self::URL.'?sort[field]=latestLogEntry.created_at&sort[dir]=asc');
        $this->assertResultsSorted($this->applications->sortBy('latestLogEntry.created_at')->slice(0,20), $response);
    }
    
    /**
     * @test
     */
    public function can_filter_applications_by_last_updated()
    {
        $this->applications->take(5)->each(function ($app) {
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
        foreach ($expected->values() as $idx => $app) {
            $this->assertEquals($app->id, $response->original->toArray()['data'][$idx]['id']);        
        }
    }
    
    
}
