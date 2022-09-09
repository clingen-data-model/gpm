<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Testing\TestResponse;
use App\Modules\ExpertPanel\Models\Ruleset;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\Specification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GetSpecificationsTest extends TestCase
{
    use RefreshDatabase;

    private ExpertPanel $expertPanel;
    private Collection $specifications;

    public function setup(): void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->expertPanel = ExpertPanel::factory()->vcep()->create();
        $this->specifications = Specification::factory()
                                ->count(2)
                                ->has(Ruleset::factory()->count(3))
                                ->create(['expert_panel_id' => $this->expertPanel->id]);

        Sanctum::actingAs($this->setupUser());
    }

    /**
     * @test
     */
    public function gets_specifications_with_rulesets_for_vcep()
    {
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJsonCount(2);
    }

    /**
     * @test
     */
    public function throws_404_if_group_is_not_a_vcep()
    {
        $gcep = ExpertPanel::factory()->gcep()->create();

        $this->makeRequest($gcep->group)
            ->assertStatus(404)
            ->assertJson(['message' => 'Only VCEPs have specifications.']);
    }


    private function makeRequest($group = null): TestResponse
    {
        $group = $group ?? $this->expertPanel->group;
        return $this->json('GET', '/api/groups/'.$group->uuid.'/specifications');
    }

}
