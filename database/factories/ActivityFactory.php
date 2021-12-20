<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Activity::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'log_name' => 'users',
            'subject_type' => User::class,
            'subject_id' => User::all()->random()->id,
            'description' => $this->faker->sentence()
        ];
    }
}
