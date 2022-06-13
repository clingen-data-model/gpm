<?php

namespace Tests\Feature\End2End\Groups\Genes;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use Tests\Traits\SeedsHgncGenesAndDiseases;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateGeneTest extends TestCase
{
    use RefreshDatabase;
    use SeedsHgncGenesAndDiseases;

    public function setup():void
    {
        parent::setup();
        $this->seedGenes();
        $this->seedDiseases();
        $this->setupForGroupTest();

        $this->user = $this->setupUser(permissions: ['ep-applications-manage']);

        $this->expertPanel = ExpertPanel::factory()->vcep()->create();
        $this->gene1 = $this->expertPanel->genes()->create([
            'hgnc_id' => 12345,
            'mondo_id' => 'MONDO:9876543',
            'gene_symbol' => uniqid()
        ]);
        $this->url = '/api/groups/'.$this->expertPanel->group->uuid.'/expert-panel/genes/'.$this->gene1->id;
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function unprivileged_users_cannot_update_genes()
    {
        $this->user->revokePermissionTo('ep-applications-manage');

        $this->json('PUT', $this->url, ['hgnc_id' => 12345, 'mondo_id' => 'MONDO:9876543'])
            ->assertStatus(403);

        $this->assertDatabaseHas('genes', [
            'hgnc_id' => 12345,
            'expert_panel_id' => $this->expertPanel->id,
        ]);
    }

    /**
     * @test
     */
    public function checks_group_is_an_expert_panel()
    {
        $this->expertPanel->group->update(['group_type_id' => config('groups.types.wg.id')]);

        $this->json('PUT', $this->url, ['hgnc_id' => 12345])
            ->assertStatus(422)
            ->assertJsonFragment([
                'group' => ['Only expert panels have a gene list.  You can not update a gene on a '.$this->expertPanel->group->type->full_name]
            ]);

        $this->assertDatabaseHas('genes', [
            'hgnc_id' => 12345,
            'expert_panel_id' => $this->expertPanel->id,
        ]);
    }

    /**
     * @test
     */
    public function validates_input_for_vceps()
    {
        Sanctum::actingAs($this->user);

        $rsp1 = $this->json('PUT', $this->url, []);
        $rsp1->assertStatus(422);
        $rsp2 = $this->json('PUT', $this->url, ['hgnc_id'=>null, 'mondo_id' => null])
            ->assertStatus(422)
            ->assertJsonFragment([
                    'hgnc_id' => ['The gene is required.']
            ])
            ->assertJsonFragment([
                    'mondo_id' => ['The disease is required.']
            ]);
            
        $rsp2 = $this->json('PUT', $this->url, ['hgnc_id' => 'bob', 'mondo_id' => '920192'])
        ->assertStatus(422)
            ->assertJsonFragment([
                    'hgnc_id' => ['The selection is invalid.']
            ])
            ->assertJsonFragment([
                'mondo_id' => ['The selection is invalid.'],
            ]);

        $rsp3 = $this->json('PUT', $this->url, ['hgnc_id' => 91828, 'mondo_id' => 'MONDO:3383710'])
            ->assertStatus(422)
            ->assertJsonFragment([
                'hgnc_id' => ['The selection is invalid.']
            ])
            ->assertJsonFragment([
                'mondo_id' => ['The selection is invalid.']
            ]);
    }

    /**
     * @test
     */
    public function validates_input_for_gceps()
    {
        Sanctum::actingAs($this->user);

        $this->expertPanel->group->update(['group_type_id' => config('groups.types.gcep.id')]);
        $this->json('PUT', $this->url, ['hgnc_id'=>null, 'mondo_id' => null])
            ->assertStatus(422)
            ->assertJsonFragment([
                    'hgnc_id' => ['The gene is required.']
            ])
            ->assertJsonMissing([
                    'mondo_id' => ['This is required.']
            ]);

        $this->json('PUT', $this->url, ['hgnc_id' => 'bob', 'mondo_id' => '920192'])
            ->assertJsonFragment([
                    'hgnc_id' => ['The selection is invalid.']
            ])
            ->assertJsonMissing([
                'mondo_id' => ['The selection is invalid.'],
            ]);
    }

    /**
     * @test
     */
    public function privileged_user_can_update_gene()
    {
        $this->seedGenes(['hgnc_id'=>67890, 'gene_symbol' => 'BRD1']);
        $this->json('PUT', $this->url, ['hgnc_id' => 67890, 'mondo_id' => 'MONDO:9876543'])
            ->assertStatus(200);

        $this->assertDatabaseHas('genes', [
            'expert_panel_id' => $this->expertPanel->id,
            'hgnc_id' => 67890,
            'mondo_id' => 'MONDO:9876543'
        ]);
    }
}
