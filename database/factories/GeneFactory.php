<?php

namespace Database\Factories;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\Gene;
use Illuminate\Database\Eloquent\Factories\Factory;

class GeneFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Gene::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'hgnc_id' => $this->faker->numberBetween(1),
            'gene_symbol' => uniqid(),
            'mondo_id' => null,
            'disease_name' => null,
            'expert_panel_id' => ExpertPanel::factory()->create()->id
        ];
    }
}
