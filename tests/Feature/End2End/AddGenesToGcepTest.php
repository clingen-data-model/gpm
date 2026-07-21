<?php

namespace Tests\Feature\End2End;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\SeedsHgncGenesAndDiseases;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('groups')]
#[Group('applications')]
#[Group('expert-panels')]
#[Group('gene-list')]
class AddGenesToGcepTest extends TestCase
{
    use RefreshDatabase;
    use SeedsHgncGenesAndDiseases;

    public function setup():void
    {
        parent::setup();
        $this->setupForgroupTest();
        
        $this->seedGenes([['hgnc_id' => 678, 'gene_symbol'=>'BCD'], ['hgnc_id' => 12345, 'gene_symbol' => 'ABC1']]);

        $this->user = $this->setupUser(permissions: ['ep-applications-manage']);

        $this->expertPanel = ExpertPanel::factory()->gcep()->create();
        $this->url = '/api/groups/'.$this->expertPanel->group->uuid.'/expert-panel/genes';
        Sanctum::actingAs($this->user);
    }

    #[Test]
    public function unprivileged_user_can_not_add_genes_to_an_gcep()
    {
        $this->user->revokePermissionTo('ep-applications-manage');

        $this->json('POST', $this->url, [
            'genes' => [[
                'hgnc_id' => 12345,
                'gene_symbol' => 'ABC1',
            ]]
        ])
        ->assertStatus(403);
    }

    #[Test]
    public function validates_input_for_gceps()
    {
        $this->json('POST', $this->url, ['genes' => [['gene_symbol' => 'ABC1']]])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'genes.0.hgnc_id' => 'Gene must have an HGNC ID.',
            ])
            ->assertJsonMissingValidationErrors(['genes.0.gene_symbol']);
    }

    #[Test]
    public function privileged_user_can_add_new_genes_to_a_gcep()
    {
        $this->json('POST', $this->url, [
            'genes' => [
                ['hgnc_id' => 12345, 'gene_symbol' => 'ABC1'],
                ['hgnc_id' => 678, 'gene_symbol' => 'BCD'],
            ],
        ])->assertStatus(200);

        $this->assertDatabaseHas(
            'scope_genes',
            [
                'gene_symbol' => 'ABC1',
                'mondo_id' => null,
                'expert_panel_id' => $this->expertPanel->id
            ]
        );
        $this->assertDatabaseHas(
            'scope_genes',
            [
                'gene_symbol' => 'BCD',
                'mondo_id' => null,
                'expert_panel_id' => $this->expertPanel->id
            ]
        );
    }

    #[Test]
    public function logs_genes_added_activity()
    {
        $this->seedGenes([
            ['hgnc_id' => 888, 'gene_symbol' => 'DEF'],
            ['hgnc_id' => 999, 'gene_symbol' => 'EFG'],
        ]);

        Carbon::setTestNow();

        $this->json('post', $this->url, [
            'genes' => [
                ['hgnc_id' => 888, 'gene_symbol' => 'DEF'],
                ['hgnc_id' => 999, 'gene_symbol' => 'EFG'],
            ],
        ])->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $this->expertPanel->group->id,
            'activity_type' => 'genes-added',
        ]);
    }
}
