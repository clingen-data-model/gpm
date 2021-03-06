<?php

namespace Database\Factories;

use App\Modules\ExpertPanel\Models\Coi;
use App\Modules\Group\Models\GroupMember;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Database\Eloquent\Factories\Factory;

class CoiFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Coi::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $expertPanel = ExpertPanel::count() > 0 ? ExpertPanel::all()->random() : ExpertPanel::factory()->create();
        $groupMember = GroupMember::count() > 0 ? GroupMember::all()->random() : GroupMember::factory()->create();
        return [
            'uuid' => $this->faker->uuid(),
            'expert_panel_id' => $expertPanel->id,
            'group_member_id' => $groupMember->id,
            'data' => (object)[
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
                'email' => $this->faker->email,
                'work_fee_lab' => $this->faker->randomElement([0,1]),
                'contributions_to_gd_in_ep' => $this->faker->randomElement([0,1]),
                'contributions_to_genes' => $this->faker->sentence,
                'independent_efforts' => $this->faker->randomElement([0,1,2]),
                'independent_efforts_details' => $this->faker->sentence,
                'coi' => $this->faker->randomElement([0,1,2]),
                'coi_details' => $this->faker->sentence
            ]
        ];
    }
}
