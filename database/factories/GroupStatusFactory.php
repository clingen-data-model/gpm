<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use App\Modules\Group\Models\GroupStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupStatusFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GroupStatus::class;

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
