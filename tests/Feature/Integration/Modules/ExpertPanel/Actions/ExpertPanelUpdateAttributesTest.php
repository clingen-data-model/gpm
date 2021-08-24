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
        ExpertPanelUpdateAttributes::run($expertPanel->uuid, ['short_base_name' => 'test']);
    
        Event::assertDispatched(ExpertPanelAttributesUpdated::class);
    }

    /**
     * @test
     */
    public function logs_ExpertPanelAttributesUpdated()
    {
        $expertPanel = ExpertPanel::factory()->gcep()->create(['long_base_name'=>'aabb']);
    
        ExpertPanelUpdateAttributes::run($expertPanel->uuid, [
            'short_base_name' => 'test',
            'long_base_name' => 'aabb',
        ]);
        $this->assertLoggedActivity($expertPanel, 'Attributes updated: short_base_name = test');
    }

    /**
     * @test
     */
    public function updates_group_name_if_working_name_is_updated()
    {
        $expertPanel = ExpertPanel::factory()->gcep()->create(['long_base_name'=>'aabb']);
    
        ExpertPanelUpdateAttributes::run($expertPanel->uuid, [
            'working_name' => 'test',
        ]);

        $this->assertDatabaseHas('groups', [
            'id' => $expertPanel->group->id,
            'name' => 'test'
        ]);
    }

    /**
     * @test
     */
    public function updates_group_parent_id_if_cdwg_id_is_updated()
    {
        $expertPanel = ExpertPanel::factory()->gcep()->create(['long_base_name'=>'aabb']);
    
        ExpertPanelUpdateAttributes::run($expertPanel->uuid, [
            'cdwg_id' => 2
        ]);

        $this->assertDatabaseHas('groups', [
            'id' => $expertPanel->group->id,
            'parent_id' => 2
        ]);
    }
}
