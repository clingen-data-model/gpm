<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use App\Modules\Group\Models\GroupType;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupTypeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GroupType::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->faker->name;
        return [
            'name' => $name,
            'fullName' => $name,
            'description' => 'this is a test',
        ];
    }
}
