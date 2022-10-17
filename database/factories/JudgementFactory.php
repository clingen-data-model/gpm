<?php

namespace Database\Factories;

use App\Modules\Person\Models\Person;
use App\Modules\Group\Models\Judgement;
use App\Modules\Group\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

class JudgementFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Judgement::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'decision' => $this->faker->randomElement(['request-revisions', 'approve-after-revisions', 'approve']),
            'notes' => $this->faker->paragraph(3),
            'submission_id' => Submission::factory(),
            'person_id' => Person::factory()
        ];
    }
}
