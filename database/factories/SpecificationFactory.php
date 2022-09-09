<?php

namespace Database\Factories;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\Specification;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Modules\ExpertPanel\Models\SpecificationStatus;

class SpecificationFactory extends Factory
{
    protected $model = Specification::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'cspec_id' => uniqid(),
            'name' => $this->faker->sentence(),
            'status' => 'Start',
            'expert_panel_id' => ExpertPanel::factory()
        ];
    }
}
