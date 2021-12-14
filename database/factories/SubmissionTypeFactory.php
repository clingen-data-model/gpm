<?php

namespace Database\Factories;

use App\Models\SubmissionType;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubmissionTypeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SubmissionType::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => 'type '.uniqid(),
            'description' => null
        ];
    }
}
