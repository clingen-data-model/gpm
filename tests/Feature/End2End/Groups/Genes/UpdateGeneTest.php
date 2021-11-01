<?php

namespace Tests\Feature\End2End\Groups\Genes;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateGeneTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('ep-applications-manage');

        $this->expertPanel = ExpertPanel::factory()->create(['expert_panel_type_id' => config('expert_panels.types.vcep.id')]);
        $this->gene1 = $this->expertPanel->genes()->create([
            'hgnc_id' => 123345,
            'mondo_id' => 'MONDO:1234567',
            'gene_symbol' => uniqid()
        ]);
        $this->url = '/api/groups/'.$this->expertPanel->group->uuid.'/application/genes/'.$this->gene1->id;
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function unprivileged_users_cannot_update_genes()
    {
        $this->user->revokePermissionTo('ep-applications-manage');

        // Sanctum::actingAs($this->user);
        $this->json('PUT', $this->url, ['hgnc_id' => 109876, 'mondo_id' => 'MONDO:1234567'])
            ->assertStatus(403);

        $this->assertDatabaseHas('genes', [
            'hgnc_id' => 123345,
            'expert_panel_id' => $this->expertPanel->id,
        ]);
    }

    /**
     * @test
     */
    public function checks_group_is_an_expert_panel()
    {
        $this->expertPanel->group->update(['group_type_id' => config('groups.types.wg.id')]);

        $this->json('PUT', $this->url, ['hgnc_id' => 109876])
            ->assertStatus(422)
            ->assertJsonFragment([
                'group' => ['Only expert panels have a gene list.  You can not update a gene on a '.$this->expertPanel->group->type->full_name]
            ]);

        $this->assertDatabaseHas('genes', [
            'hgnc_id' => 123345,
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
                    'hgnc_id' => ['The hgnc id field is required.']
            ])
            ->assertJsonFragment([
                    'mondo_id' => ['The mondo id field is required.']
            ]);
            
        $rsp2 = $this->json('PUT', $this->url, ['hgnc_id' => 'bob', 'mondo_id' => '920192'])
        ->assertStatus(422)
            ->assertJsonFragment([
                    'hgnc_id' => ['The hgnc id must be a number.']
            ])
            ->assertJsonFragment([
                'mondo_id' => ['The mondo id format is invalid.'],
            ]);
    }

    /**
     * @test
     */
    public function validates_input_for_gceps()
    {
        Sanctum::actingAs($this->user);

        $this->expertPanel->update(['expert_panel_type_id' => config('expert_panels.types.gcep.id')]);
        $this->json('PUT', $this->url, ['hgnc_id'=>null, 'mondo_id' => null])
            ->assertStatus(422)
            ->assertJsonFragment([
                    'hgnc_id' => ['The hgnc id field is required.']
            ])
            ->assertJsonMissing([
                    'mondo_id' => ['The mondo id field is required.']
            ]);

        $this->json('PUT', $this->url, ['hgnc_id' => 'bob', 'mondo_id' => '920192'])
            ->assertJsonFragment([
                    'hgnc_id' => ['The hgnc id must be a number.']
            ])
            ->assertJsonMissing([
                'mondo_id' => ['The mondo id format is invalid.'],
            ]);
    }

    /**
     * @test
     */
    public function privileged_user_can_update_gene()
    {
        $this->json('PUT', $this->url, ['hgnc_id' => 109876, 'mondo_id' => 'MONDO:9876543'])
            ->assertStatus(200);

        $this->assertDatabaseHas('genes', [
            'expert_panel_id' => $this->expertPanel->id,
            'hgnc_id' => 109876,
            'mondo_id' => 'MONDO:9876543'
        ]);
    }
}
