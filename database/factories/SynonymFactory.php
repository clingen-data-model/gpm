<?php

namespace Database\Factories;

use App\Models\Credential;
use Illuminate\Database\Eloquent\Factories\Factory;

class SynonymFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word(),
            'synonym_of_id' => Credential::factory(),
            'synonym_of_type' => function (array $attributes) {
                return Credential::find($attributes['synonym_of_id'])->type;
            }
        ];
    }
}
