<?php

namespace Tests\Feature\End2End\Groups\Genes;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Carbon;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('groups')]
#[Group('applications')]
#[Group('expert-panels')]
#[Group('gene-list')]
class RemoveGeneFromExpertPanelTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->user = $this->setupUser(permissions: ['ep-applications-manage']);

        $this->expertPanel = ExpertPanel::factory()->create(['expert_panel_type_id' => config('expert_panels.types.vcep.id')]);
        $this->gene1 = $this->expertPanel->genes()->create([
            'hgnc_id' => 123345,
            'mondo_id' => 'MONDO:1234567',
            'gene_symbol' => uniqid()
        ]);
        $this->url = '/api/groups/'.$this->expertPanel->group->uuid.'/expert-panel/genes';
    }

    #[Test]
    public function unprivileged_users_cannot_remove_genes()
    {
        $this->user->revokePermissionTo('ep-applications-manage');

        Sanctum::actingAs($this->user);
        $this->json('DELETE', $this->url, ['ids' => [$this->gene1->id]])
            ->assertStatus(403);

        $this->assertDatabaseHas('scope_genes', [
            'hgnc_id' => 123345,
            'expert_panel_id' => $this->expertPanel->id,
            'deleted_at' => null
        ]);
    }

    #[Test]
    public function does_not_try_to_delete_if_group_is_not_an_expert_panel()
    {
        $this->expertPanel->group->update(['group_type_id' => config('groups.types.wg.id')]);
        Sanctum::actingAs($this->user);
        $this->json('DELETE', $this->url, ['ids' => [$this->gene1->id]])
            ->assertStatus(422);
    }


    #[Test]
    public function privileged_user_can_remove_genes()
    {
        Carbon::setTestNow('2021-11-01');
        Sanctum::actingAs($this->user);
        $this->json('DELETE', $this->url, ['ids' => [$this->gene1->id]])
            ->assertStatus(200);

        $this->assertDatabaseHas('scope_genes', [
            'hgnc_id' => 123345,
            'expert_panel_id' => $this->expertPanel->id,
            'deleted_at' => '2021-11-01 00:00:00'
        ]);
    }

    #[Test]
    public function activity_logged()
    {
        Sanctum::actingAs($this->user);
        $this->json('DELETE', $this->url, ['ids' => [$this->gene1->id]])
            ->assertStatus(200);

        $this->assertDatabaseHas(
            'activity_log',
            [
                'activity_type' => 'gene-removed',
                'subject_id' => $this->expertPanel->group_id,
                'properties->genes[0]->hgnc_id' => $this->gene1->hgnc_id
            ]
        );
    }
}
