<?php

namespace Database\Factories;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use App\Modules\Group\Models\Group;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\ExpertPanelType;
use Database\Factories\Traits\GetsRandomConfigValue;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpertPanelFactory extends Factory
{
    use GetsRandomConfigValue;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ExpertPanel::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'uuid' => Uuid::uuid4()->toString(),
            'group_id' => Group::factory()
                            ->create([
                                'name' => 'group '.uniqid(),
                                'group_type_id' => config('groups.types.gcep.id')
                            ])->id,
            'expert_panel_type_id' => config('groups.types.gcep.id') - 2,
            'date_initiated' => Carbon::now(),
            'current_step' => 1,
        ];
    }

    public function gcep()
    {
        return $this->state(function (array $attributes) {
            return [
                'expert_panel_type_id' => config('expert_panels.types.gcep.id'),
                'group_id' => Group::factory()
                                ->create([
                                    'name' => 'group '.uniqid(),
                                    'group_type_id' => config('groups.types.gcep.id')
                                ])->id
            ];
        });
    }

    public function vcep()
    {
        return $this->state(function (array $attributes) {
            return [
                'expert_panel_type_id' => config('expert_panels.types.vcep.id'),
                'group_id' => Group::factory()
                                ->create([
                                    'name' => 'group '.uniqid(),
                                    'group_type_id' => config('groups.types.vcep.id')
                                ])->id
            ];
        });
    }

    public function randomStep()
    {
        return $this->state(function (array $attributes) {
            return [
                'current_step' => $this->faker->randomElement(range(1, 4))
            ];
        });
    }
}
