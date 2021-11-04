<?php

namespace Database\Factories;

use App\Modules\ExpertPanel\Models\EvidenceSummary;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\Gene;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvidenceSummaryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EvidenceSummary::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $ep = ExpertPanel::factory()->vcep()->create();
        return [
            'expert_panel_id' => $ep->id,
            'gene_id' => Gene::factory()->create(['expert_panel_id' => $ep->id]),
            'summary' => $this->faker->paragraph(4),
            'variant' => $this->faker->word(),
            'vci_url' => $this->faker->url
        ];
    }
}
