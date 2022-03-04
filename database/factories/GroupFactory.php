<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\GroupType;
use App\Modules\Group\Models\GroupStatus;
use Database\Factories\Traits\GetsRandomConfigValue;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    use GetsRandomConfigValue;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Group::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'uuid' => $this->faker->uuid(),
            'name' => $this->faker->name(),
            'group_type_id' => 1,
            'group_status_id' => $this->getRandomConfigValue('groups.statuses')['id'],
        ];
    }

    public function cdwg()
    {
        return $this->state(function ($attributes) {
            return [
                'group_type_id' => config('groups.types.cdwg.id')
            ];
        });
    }
}
