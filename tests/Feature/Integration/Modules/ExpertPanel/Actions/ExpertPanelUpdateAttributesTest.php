<?php

namespace Tests\Feature\Integration\Modules\ExpertPanel\Actions;

use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\ExpertPanel\Actions\ExpertPanelUpdateAttributes;
use App\Modules\ExpertPanel\Events\ExpertPanelAttributesUpdated;

class ExpertPanelUpdateAttributesTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
    }
    

    /**
     * @test
     */
    public function raises_ExpertPanelAttributesUpdated_event()
    {
        $expertPanel = ExpertPanel::factory()->gcep()->create();
    
        Event::fake();
        ExpertPanelUpdateAttributes::run($expertPanel->uuid, ['working_name' => 'test']);
    
        Event::assertDispatched(ExpertPanelAttributesUpdated::class);
    }

    /**
     * @test
     */
    public function logs_ExpertPanelAttributesUpdated()
    {
        $expertPanel = ExpertPanel::factory()->gcep()->create(['long_base_name'=>'aabb']);
    
        ExpertPanelUpdateAttributes::run($expertPanel->uuid, [
            'working_name' => 'test',
            'long_base_name' => 'aabb',
        ]);
        $this->assertLoggedActivity($expertPanel, 'Attributes updated: working_name = test');
    }
}
