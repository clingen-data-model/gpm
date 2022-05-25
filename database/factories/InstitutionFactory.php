<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use App\Modules\Person\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstitutionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Institution::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'uuid' => $this->faker->uuid,
            'name' => $this->faker->name,
            'abbreviation' => $this->faker->word,
            'reportable' => true
        ];
    }
}
