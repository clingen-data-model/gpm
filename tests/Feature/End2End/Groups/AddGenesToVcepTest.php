<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
    public function unprivileged_user_can_not_add_genes()
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
    public function validates_input()
    {
        Sanctum::actingAs($this->user);

        $rsp1 = $this->json('POST', $this->url, []);
        $rsp1->assertStatus(422);
        $rsp1->assertJsonFragment([
            'genes' => ['The genes field is required.']
        ]);

        $rsp2 = $this->json('POST', $this->url, ['genes'=>[['hgnc_id'=>null, 'mondo_id' => null], ['hgnc_id' => 'bob', 'mondo_id' => '920192']]])
            ->assertStatus(422)
            ->assertJsonFragment([
                    'genes.0.hgnc_id' => ['The genes.0.hgnc_id field is required.']
            ])
            ->assertJsonFragment([
                    'genes.0.mondo_id' => ['The genes.0.mondo_id field is required.']
            ])
            ->assertJsonFragment([
                    'genes.1.hgnc_id' => ['The genes.1.hgnc_id must be a number.']
            ])
            ->assertJsonFragment([
                'genes.1.mondo_id' => ['The genes.1.mondo_id format is invalid.'],
            ]);
    }

    /**
     * @test
     */
    public function validates_group_is_a_vcep()
    {
        $this->expertPanel->update(['expert_panel_type_id' => config('expert_panels.types.gcep.id')]);
        Sanctum::actingAs($this->user);

        $this->json('POST', $this->url, ['genes' => [['hgnc_id'=>1, 'mondo_id' => 'MONDO:1234567']]])
            ->assertStatus(422)
            ->assertJsonFragment([
                'group' => ['The group is not a VCEP.'],
            ]);
    }
    
    /**
     * @test
     */
    public function privileged_user_can_add_new_genes()
    {
        Sanctum::actingAs($this->user);
        $this->json('POST', $this->url, [
            'genes' => [
                ['hgnc_id'=>123456, 'mondo_id' => 'MONDO:1234567'],
                ['hgnc_id'=>789012, 'mondo_id' => 'MONDO:8901234']
            ]
        ])->assertStatus(200);

        $this->assertDatabaseHas('genes', ['hgnc_id' => 123456, 'mondo_id' => 'MONDO:1234567']);
        $this->assertDatabaseHas('genes', ['hgnc_id' => 789012, 'mondo_id' => 'MONDO:8901234']);
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
