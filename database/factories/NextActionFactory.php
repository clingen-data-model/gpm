<?php

namespace Database\Factories;

use App\Domain\Application\Models\Application;
use App\Models\NextAction;
use Illuminate\Database\Eloquent\Factories\Factory;

class NextActionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = NextAction::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {

        $applicationId = Application::all()->random()->id;
        return [
            'uuid' => $this->faker->uuid,
            'entry' => $this->faker->paragraph,
            'date_created' => $this->faker->date,
            'target_date' => $this->faker->date,
            'date_completed' => $this->faker->date,
            'step' => $this->faker->randomElement([1,2,3,4,null]),
            'application_id' => $applicationId
        ];
    }
}
