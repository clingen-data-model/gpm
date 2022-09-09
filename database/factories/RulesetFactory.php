<?php

namespace Database\Factories;

use App\Modules\ExpertPanel\Models\Ruleset;
use App\Modules\ExpertPanel\Models\Specification;
use Illuminate\Database\Eloquent\Factories\Factory;

class RulesetFactory extends Factory
{
    protected $model = Ruleset::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'cspec_ruleset_id' => uniqid(),
            'specification_id' => Specification::factory(),
            'status' => 'START'
        ];
    }
}
