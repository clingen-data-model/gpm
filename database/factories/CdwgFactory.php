<?php

namespace Database\Factories;

use App\Models\Cdwg;
use Illuminate\Database\Eloquent\Factories\Factory;

class CdwgFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Cdwg::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'uuid' => $this->faker->uuid,
            'name' => uniqid().' CDWG',
            'group_type_id' => config('groups.types.cdwg.id'),
            'group_status_id' => config('groups.statuses.active.id')
        ];
    }
}
