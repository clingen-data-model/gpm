<?php

namespace Tests\Feature\End2End;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\ExpertPanel\Models\Gene;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group groups
 * @group applications
 * @group expert-panels
 * @group gene-list
 */
class SyncGcepGenesTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('ep-applications-manage');

        $this->expertPanel = ExpertPanel::factory()->gcep()->create();
        $this->url = '/api/groups/'.$this->expertPanel->group->uuid.'/application/genes';
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function unprivileged_user_can_not_add_genes_to_an_ep()
    {
        $this->user->revokePermissionTo('ep-applications-manage');

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
    public function validates_input_for_gceps()
    {
        $this->expertPanel->update(['expert_panel_type_id' => config('expert_panels.types.gcep.id')]);
        $this->json('POST', $this->url, ['genes'=>null])
            ->assertStatus(422)
            ->assertJsonFragment([
                    'genes' => ['The genes field is required.']
            ]);
    }

    /**
     * @test
     */
    public function privileged_user_can_add_new_genes_to_a_gcep()
    {
        $this->json('POST', $this->url, [
            'genes' => ['ABC', 'BCD'],
        ])->assertStatus(200);

        $this->assertDatabaseHas(
            'genes',
            [
                'gene_symbol' => 'ABC',
                'mondo_id' => null,
                'expert_panel_id' => $this->expertPanel->id
            ]
        );
        $this->assertDatabaseHas(
            'genes',
            [
                'gene_symbol' => 'BCD',
                'mondo_id' => null,
                'expert_panel_id' => $this->expertPanel->id
            ]
        );
    }

    /**
     * @test
     */
    public function privileged_user_can_remove_genes()
    {
        // Add some genes to the expert panel
        $this->expertPanel->genes()->saveMany([
            new Gene(['hgnc_id' => 12345, 'gene_symbol'=>'ABC']),
            new Gene(['hgnc_id' => 678, 'gene_symbol'=>'BCD']),
        ]);

        Carbon::setTestNow();

        $this->json('post', $this->url, ['genes' => ['BCD']])
            ->assertStatus(200);

        $this->assertDatabaseHas('genes', [
            'expert_panel_id' => $this->expertPanel->id,
            'gene_symbol' => 'ABC',
            'deleted_at' => Carbon::now()
        ]);

        $this->assertDatabaseHas('genes', [
            'expert_panel_id' => $this->expertPanel->id,
            'gene_symbol' => 'BCD',
            'deleted_at' => null
        ]);
    }

    /**
     * @test
     */
    public function logs_appropriate_activities()
    {
        $this->expertPanel->genes()->saveMany([
            new Gene(['hgnc_id' => 12345, 'gene_symbol'=>'ABC']),
            new Gene(['hgnc_id' => 678, 'gene_symbol'=>'BCD']),
        ]);

        Carbon::setTestNow();

        $this->json('post', $this->url, ['genes' => ['BCD', 'DEF', 'EFG']])
            ->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $this->expertPanel->group->id,
            'activity_type' => 'genes-added',
            // 'properties->genes' => ['DEF', 'EFG']
        ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $this->expertPanel->group->id,
            'activity_type' => 'gene-removed',
            // 'properties->genes' => ['ABC']
        ]);
    }
}
