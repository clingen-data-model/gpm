<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use Database\Factories\CoiFactory;
use App\Modules\ExpertPanel\Models\Coi;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\ExpertPanel\Actions\CoiReportMakePdf;

class GetCoiReportPdfTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->expertPanel = ExpertPanel::factory()->create(['long_base_name' => "Early's Barbara"]);
        $this->member1 = GroupMember::factory()->create(['group_id' => $this->expertPanel->group_id]);
        $this->member2 = GroupMember::factory()->create(['group_id' => $this->expertPanel->group_id]);

        $this->coi1 = Coi::factory()->create([
            'expert_panel_id' => $this->expertPanel->id,
            'group_member_id' => $this->member1->id,
        ]);
        $this->coi2 = Coi::factory()->create([
            'expert_panel_id' => $this->expertPanel->id,
            'group_member_id' => $this->member2->id,
        ]);

        $this->action = app()->make(CoiReportMakePdf::class);
    }

    /**
     * @test
     */
    // public function makes_a_pdf()
    // {
    //     // $this->action->handle($this->expertPanel->group);
    // }
}
