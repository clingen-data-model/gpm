<?php

namespace Database\Factories;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\NextAction;
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

        $expertPanelId = ExpertPanel::all()->random()->id;
        return [
            'uuid' => $this->faker->uuid,
            'entry' => $this->faker->paragraph,
            'date_created' => $this->faker->date,
            'target_date' => $this->faker->date,
            'date_completed' => null,
            'step' => $this->faker->randomElement([1,2,3,4,null]),
            'expert_panel_id' => $expertPanelId
        ];
    }
}
