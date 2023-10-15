<?php

namespace Database\Factories;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\GroupMember;
use App\Modules\Person\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupMemberFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GroupMember::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'person_id' => Person::factory(),
            'start_date' => $this->faker->dateTime(),
            'end_date' => null,
            'is_contact' => 0,
        ];
    }
}
