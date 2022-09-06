<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Modules\ExpertPanel\Models\SpecificationStatus;

class SpecificationStatusFactory extends Factory
{

    protected $model = SpecificationStatus::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => uniqid(),
            'event' => Str::kebab($this->faker->sentence()),
            'color' => 'blue',
            'description' => $this->faker->sentence()
        ];
    }
}
