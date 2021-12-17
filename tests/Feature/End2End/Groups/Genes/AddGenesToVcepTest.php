<?php

namespace Tests\Feature\End2End\Groups\Genes;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Services\HgncLookupInterface;
use App\Services\MondoLookupInterface;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\SeedsHgncGenesAndDiseases;

/**
 * @group groups
 * @group applications
 * @group expert-panels
 * @group gene-list
 */
class AddGenesToVcepTest extends TestCase
{
    use RefreshDatabase;
    use SeedsHgncGenesAndDiseases;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->seedGenes();
        $this->seedDiseases();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('ep-applications-manage');

        $this->expertPanel = ExpertPanel::factory()->create(['expert_panel_type_id' => config('expert_panels.types.vcep.id')]);
        $this->url = '/api/groups/'.$this->expertPanel->group->uuid.'/expert-panel/genes';
        
        app()->bind(HgncLookupInterface::class, function ($app) {
            return new class implements HgncLookupInterface {
                public function findSymbolById($hgncId): string{
                    return 'ABC1';
                }
                public function findHgncIdBySymbol($geneSymbol): int {
                    return 12345;
                }
            };
        });

        app()->bind(MondoLookupInterface::class, function ($app) {
            return new class implements MondoLookupInterface{
                public function findNameByMondoId($hgncId): string {
                    return 'gladiola syndrome';
                }
            };
        });


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
                'hgnc_id' => 12345,
                'mondo_id' => 'MONDO:9876543',
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
            'genes' => ['This field is required.']
        ]);
    }

    /**
     * @test
     */
    public function validates_group_is_an_vcep()
    {
        $this->expertPanel->group->update(['group_type_id' => config('groups.types.wg.id')]);
        Sanctum::actingAs($this->user);

        $this->json('POST', $this->url, ['genes' => [['hgnc_id'=>12345, 'mondo_id' => 'MONDO:9876543']]])
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
        $this->seedGenes(['hgnc_id' => 789012, 'gene_symbol' => 'ABC12']);
        $this->seedDiseases(['mondo_id' => 'MONDO:8901234', 'name' => 'fartsalot']);
        Sanctum::actingAs($this->user);
        $this->json('POST', $this->url, [
            'genes' => [
                ['hgnc_id'=>12345, 'mondo_id' => 'MONDO:9876543'],
                ['hgnc_id'=>789012, 'mondo_id' => 'MONDO:8901234']
            ]
        ])->assertStatus(200);

        $this->assertDatabaseHas(
            'genes',
            [
                'hgnc_id' => 12345,
                'gene_symbol' => 'ABC1',
                'mondo_id' => 'MONDO:9876543',
                'disease_name' => 'gladiola syndrome',
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
        $this->seedGenes(['hgnc_id' => 789012, 'gene_symbol' => 'ABC12']);
        $this->seedDiseases(['mondo_id' => 'MONDO:8901234', 'name' => 'fartsalot']);

        $genesData = [
            ['hgnc_id'=>12345, 'mondo_id' => 'MONDO:9876543'],
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
