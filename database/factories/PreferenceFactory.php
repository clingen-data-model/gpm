<?php

namespace Database\Factories;

use App\Models\AppliesToPermission;
use App\Models\AppliesToRole;
use App\Modules\User\Models\Preference;
use Illuminate\Database\Eloquent\Factories\Factory;

class PreferenceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Preference::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'data_type' => $this->faker->randomElement(['boolean', 'integer', 'string', 'float', 'array', 'object']),
            'default' => $this->faker->text(),
            'applies_to_role' => AppliesToRole::factory(),
            'applies_to_permission' => AppliesToPermission::factory(),
        ];
    }
}
