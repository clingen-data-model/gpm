<?php

namespace Tests\Feature\End2End\Applications;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\WithFaker;
use App\Domain\Application\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        $this->applications = Application::factory(25)->randomStep()->create();
    }

    /**
     * @test
     */
    public function returns_20_applications_at_a_time()
    {
        $response = $this->actingAs($this->user, 'api')
            ->json('GET', self::URL);
        $response->assertStatus(200);
        $response->assertJsonCount(20, 'data');
        $response->assertJsonFragment(['current_page'=>1]);

        $response = $this->actingAs($this->user, 'api')
            ->json('GET', self::URL.'?page=2')
            ->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /**
     * @test
     */
    public function sorts_results_by_name()
    {
        $response = $this->actingAs($this->user, 'api')
                        ->json('GET', self::URL.'?sort[field]=name&sort[dir]=desc');
        $this->assertResultsSorted($this->applications->sortBy('working_name')->slice(0,20), $response);
    }

    /**
     * @test
     */
    public function sorts_results_by_current_step()
    {
        $response = $this->actingAs($this->user, 'api')
                    ->json('GET', self::URL.'?sort[field]=current_step&sort[dir]=asc');
        $this->assertResultsSorted($this->applications->sortBy('current_step')->slice(0,20), $response);
    }
    
    /**
     * @test
     */
    public function sorts_results_by_cdwg_name()
    {
        $this->applications->load('cdwg');
        $response = $this->actingAs($this->user, 'api')
                    ->json('GET', self::URL.'?sort[field]=cdwg.name&sort[dir]=asc');
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


        $response = $this->actingAs($this->user, 'api')
                    ->json('GET', self::URL.'?sort[field]=latestLogEntry.created_at&sort[dir]=asc');
        $this->assertResultsSorted($this->applications->sortBy('latestLogEntry.created_at')->slice(0,20), $response);
    }
    

    private function assertResultsSorted(Collection $expected, $response)
    {
        foreach ($expected->values() as $idx => $app) {
            $this->assertEquals($app->id, $response->original->toArray()['data'][$idx]['id']);        
        }
    }
    
    
}
