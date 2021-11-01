<?php

namespace Tests\Feature\End2End\Groups\Genes;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group groups
 * @group applications
 * @group expert-panels
 * @group gene-list
 */
class AddGenesToVcepTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('ep-applications-manage');

        $this->expertPanel = ExpertPanel::factory()->create(['expert_panel_type_id' => config('expert_panels.types.vcep.id')]);
        $this->url = '/api/groups/'.$this->expertPanel->group->uuid.'/application/genes';
    }

    /**
     * @test
     */
    public function unprivileged_user_can_not_add_genes_to_an_ep()
    {
        $this->user->revokePermissionTo('ep-applications-manage');
        Sanctum::actingAs($this->user);

        $this->json('POST', $this->url, [
            'genes' => [[
                'hgnc_id' => 1234567,
                'mondo_id' => 'MONDO:1234567',
            ]]
        ])
        ->assertStatus(403);
    }

    /**
     * @test
     */
    public function validates_input_for_vceps()
    {
        Sanctum::actingAs($this->user);

        $rsp1 = $this->json('POST', $this->url, []);
        $rsp1->assertStatus(422);
        $rsp1->assertJsonFragment([
            'genes' => ['The genes field is required.']
        ]);
    }

    /**
     * @test
     */
    public function validates_group_is_an_vcep()
    {
        $this->expertPanel->group->update(['group_type_id' => config('groups.types.wg.id')]);
        Sanctum::actingAs($this->user);

        $this->json('POST', $this->url, ['genes' => [['hgnc_id'=>1, 'mondo_id' => 'MONDO:1234567']]])
            ->assertStatus(422)
            ->assertJsonFragment([
                'group' => ['Genes can only be added to an Expert Panel.'],
            ]);
    }
    
    /**
     * @test
     */
    public function privileged_user_can_add_new_genes_to_a_vcep()
    {
        Sanctum::actingAs($this->user);
        $this->json('POST', $this->url, [
            'genes' => [
                ['hgnc_id'=>123456, 'mondo_id' => 'MONDO:1234567'],
                ['hgnc_id'=>789012, 'mondo_id' => 'MONDO:8901234']
            ]
        ])->assertStatus(200);

        $this->assertDatabaseHas(
            'genes',
            [
                'hgnc_id' => 123456,
                'mondo_id' => 'MONDO:1234567',
                'expert_panel_id' => $this->expertPanel->id
            ]
        );
        $this->assertDatabaseHas(
            'genes',
            [
                'hgnc_id' => 789012,
                'mondo_id' => 'MONDO:8901234',
                'expert_panel_id' => $this->expertPanel->id
            ]
        );
    }

    /**
     * @test
     */
    public function activity_logged()
    {
        $genesData = [
            ['hgnc_id'=>123456, 'mondo_id' => 'MONDO:1234567'],
            ['hgnc_id'=>789012, 'mondo_id' => 'MONDO:8901234']
        ];

        Sanctum::actingAs($this->user);
        $this->json('POST', $this->url, [
            'genes' => $genesData
        ])->assertStatus(200);

        $this->assertDatabaseHas(
            'activity_log',
            [
                'subject_id' => $this->expertPanel->group_id,
                'properties' => json_encode(['genes' => $genesData, 'activity_type' => 'genes-added'])
            ]
        );
    }
}
