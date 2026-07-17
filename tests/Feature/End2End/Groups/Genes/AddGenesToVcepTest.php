<?php

namespace Tests\Feature\End2End\Groups\Genes;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Activity;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Modules\Group\Mail\GeneAddedMail;
use Tests\Traits\SeedsHgncGenesAndDiseases;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Mail\GeneAddedToVcepMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('groups')]
#[Group('applications')]
#[Group('expert-panels')]
#[Group('gene-list')]
class AddGenesToVcepTest extends TestCase
{
    use RefreshDatabase;
    use SeedsHgncGenesAndDiseases;

    private $user, $expertPanel, $url;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->seedGenes();
        $this->seedDiseases();

        $this->user = $this->setupUser(permissions: ['ep-applications-manage']);

        $this->expertPanel = ExpertPanel::factory()->vcep()->create();
        $this->url = '/api/groups/'.$this->expertPanel->group->uuid.'/expert-panel/genes';
    }

    #[Test]
    public function unprivileged_user_can_not_add_genes_to_an_ep()
    {
        $this->user->revokePermissionTo('ep-applications-manage');
        $this->actingAs($this->user, 'clerk');

        $this->json('POST', $this->url, [
            'genes' => [[
                'hgnc_id' => 12345,
                'gene_symbol' => 'ABC1',
                'mondo_id' => 'MONDO:9876543',
            ]]
        ])
        ->assertStatus(403);
    }

    #[Test]
    public function validates_input_for_vceps()
    {
        $this->actingAs($this->user, 'clerk');

        $rsp1 = $this->json('POST', $this->url, []);
        $rsp1->assertStatus(422);
        $rsp1->assertJsonFragment([
            'genes' => ['Please provide a gene.']
        ]);
    }

    #[Test]
    public function requires_an_hgnc_id_and_gene_symbol_for_each_gene()
    {
        $this->actingAs($this->user, 'clerk');

        $this->json('POST', $this->url, ['genes' => [['mondo_id' => 'MONDO:9876543']]])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'genes.0.hgnc_id' => 'Gene must have an HGNC ID.',
                'genes.0.gene_symbol' => 'Gene must have a gene symbol.',
            ]);
    }

    #[Test]
    public function validates_group_is_an_vcep()
    {
        $this->expertPanel->group->update(['group_type_id' => config('groups.types.wg.id')]);
        $this->actingAs($this->user, 'clerk');

        $this->json('POST', $this->url, [
            'genes' => [['hgnc_id'=>12345, 'gene_symbol' => 'ABC1', 'mondo_id' => 'MONDO:9876543']]
        ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'group' => ['Gene can only be added to an Expert Panel.'],
            ]);
    }
    
    #[Test]
    public function privileged_user_can_add_new_genes_to_a_vcep()
    {
        $this->seedGenes(['hgnc_id' => 789012, 'gene_symbol' => 'ABC12']);
        $this->seedDiseases(['mondo_id' => 'MONDO:8901234', 'name' => 'fartsalot']);
        $this->actingAs($this->user, 'clerk');
        $this->json('POST', $this->url, [
            'genes' => [
                [
                    'hgnc_id' => 12345,
                    'gene_symbol' => 'ABC1',
                    'mondo_id' => 'MONDO:9876543',
                    'disease_name' => 'gladiola syndrome',
                ],
                [
                    'hgnc_id' => 789012,
                    'gene_symbol' => 'ABC12',
                    'mondo_id' => 'MONDO:8901234',
                ],
            ]
        ])->assertStatus(200);

        $this->assertDatabaseHas(
            'scope_genes',
            [
                'hgnc_id' => 12345,
                'gene_symbol' => 'ABC1',
                'mondo_id' => 'MONDO:9876543',
                'disease_name' => 'gladiola syndrome',
                'expert_panel_id' => $this->expertPanel->id
            ]
        );
        $this->assertDatabaseHas(
            'scope_genes',
            [
                'hgnc_id' => 789012,
                'gene_symbol' => 'ABC12',
                'mondo_id' => 'MONDO:8901234',
                'expert_panel_id' => $this->expertPanel->id
            ]
        );
    }

    #[Test]
    public function activity_logged()
    {
        Carbon::setTestNow('2022-01-01');
        $this->seedGenes(['hgnc_id' => 789012, 'gene_symbol' => 'ABC12']);
        $this->seedDiseases(['mondo_id' => 'MONDO:8901234', 'name' => 'fartsalot']);

        $genesData = [
            [
                'hgnc_id' => 12345,
                'gene_symbol' => 'ABC1',
                'mondo_id' => 'MONDO:9876543',
                'disease_name' => 'gladiola syndrome',
            ],
        ];

        $this->actingAs($this->user, 'clerk');
        $this->json('POST', $this->url, [
            'genes' => $genesData
        ])->assertStatus(200);

        $logEntry = Activity::where([
            'activity_type' => 'genes-added',
            'subject_id' => $this->expertPanel->group_id,
        ])->first();
        $this->assertNotNull($logEntry);
        $this->assertEquals('ABC1', $logEntry->properties['genes'][0]['gene_symbol']);
        $this->assertEquals($genesData[0]['mondo_id'], $logEntry->properties['genes'][0]['mondo_id']);
        $this->assertEquals('gladiola syndrome', $logEntry->properties['genes'][0]['disease_name']);
    }
}
