<?php

namespace Tests\Feature\Integration\Modules\Application\Jobs;

use Tests\TestCase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\CoiCompleted;
use App\Modules\ExpertPanel\Jobs\StoreCoiResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StoreCoiResponseTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->expertPanel = ExpertPanel::factory()->create();
        $this->coiData = [
            'email' => 'email@example.com',
            'first_name' => 'test',
            'last_name' => 'tester',
            'work_fee_lab' => 0,
            'contributions_to_gd_in_ep' => 1,
            'contributions_to_genes' => 'many',
            'independent_efforts' => 'lots',
            'coi' => 'many'
        ];
    }

    /**
     * @test
     */
    public function stores_response_data()
    {
        $job = new StoreCoiResponse($this->expertPanel->coi_code, $this->coiData);

        Bus::dispatch($job);

        $this->assertDatabaseHas('cois', [
            'application_id' => $this->expertPanel->id,
            'data' => json_encode($this->coiData)
        ]);
    }

    /**
     * @test
     */
    public function fires_CoiCompleted_event()
    {
        Event::fake();

        $job = new StoreCoiResponse($this->expertPanel->coi_code, $this->coiData);
        Bus::dispatch($job);

        Event::assertDispatched(CoiCompleted::class);
    }

    /**
     * @test
     */
    public function CoiCompleted_event_is_logged()
    {
        $job = new StoreCoiResponse($this->expertPanel->coi_code, $this->coiData);
        Bus::dispatch($job);

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $this->expertPanel->id,
            'subject_type' => get_class($this->expertPanel),
            'description' => 'COI form completed by '.$this->coiData['email']
        ]);
    }
}
