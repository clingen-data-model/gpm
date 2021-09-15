<?php

namespace Database\Factories;

use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

class InviteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Invite::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'code' => bin2hex(random_bytes(8)),
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->email,
            'inviter_id' => Group::factory()->create()->id,
            'inviter_type' => get_class(Group::factory()),
            'person_id' => Person::factory()->create()->id,
            'redeemed_at' => $this->faker->date(),
        ];
    }
}
