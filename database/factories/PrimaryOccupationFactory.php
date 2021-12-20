<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use App\Modules\Person\Models\PrimaryOccupation;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrimaryOccupationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PrimaryOccupation::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
        ];
    }
}
