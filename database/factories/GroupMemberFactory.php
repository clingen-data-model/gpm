<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Models\GroupMember;
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
     *
     * @return array
     */
    public function definition()
    {
        return [
            'group_id' => Group::factory(),
            'person_id' => Person::factory(),
            'start_date' => $this->faker->dateTime(),
            'end_date' => null,
            'is_contact' => 0
        ];
    }
}
