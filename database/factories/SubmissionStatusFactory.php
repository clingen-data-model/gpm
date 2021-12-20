<?php

namespace Database\Factories;

use App\Modules\Group\Models\SubmissionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubmissionStatusFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SubmissionStatus::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => 'status '.uniqid()
        ];
    }
}
