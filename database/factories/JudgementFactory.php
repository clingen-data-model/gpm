<?php

namespace Database\Factories;

use App\Modules\Group\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

class JudgementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'judgement' => $this->faker->randomElement(['request-revisions', 'approve-after-revisions', 'approve']),
            'notes' => $this->faker->paragraph(3),
            'submission_id' => Submission::factory()
        ];
    }
}
