<?php

namespace Tests\Feature\Integration\Modules\Application\Jobs;

use run;
use Tests\TestCase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\CoiCompleted;
use App\Modules\ExpertPanel\Jobs\StoreCoiResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\ExpertPanel\Actions\CoiResponseStore;

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
        $this->markTestSkipped();
    }

    /**
     * @test
     */
    public function stores_response_data()
    {
        CoiResponseStore::run($this->expertPanel->coi_code, $this->coiData);


        $this->assertDatabaseHas('cois', [
            'expert_panel_id' => $this->expertPanel->id,
            'data' => json_encode($this->coiData)
        ]);
    }

    /**
     * @test
     */
    public function fires_CoiCompleted_event()
    {
        Event::fake();

        CoiResponseStore::run($this->expertPanel->coi_code, $this->coiData);

        Event::assertDispatched(CoiCompleted::class);
    }

    /**
     * @test
     */
    public function CoiCompleted_event_is_logged()
    {
        CoiResponseStore::run($this->expertPanel->coi_code, $this->coiData);

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $this->expertPanel->id,
            'subject_type' => get_class($this->expertPanel),
            'description' => 'COI form completed by '.$this->coiData['email']
        ]);
    }
}
