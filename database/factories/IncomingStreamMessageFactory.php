<?php

namespace Database\Factories;

use Carbon\Carbon;
use Faker\Generator as Faker;
use App\DataExchange\Models\IncomingStreamMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncomingStreamMessageFactory extends Factory
{

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = IncomingStreamMessage::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'topic' => 'cspec-general-events',
            'key' => $this->faker->uuid,
            'partition' => 0,
            'offset' => $this->faker->numberBetween(1000, 10000),
            'timestamp' => time(),
            'error_code' => 0,
            'payload' => []
        ];
    }

    public function draftApproved()
    {
        return $this->state(function (array $attributes) {
            return [
                'key' => null,
                'payload' => [
                    'uuid' => $this->faker->uuid,
                    'event' => 'classified-rules-approved',
                    'affiliationId' => '50666',
                ]
            ];
        });
    }

    public function pilotApproved()
    {
        return $this->state(function (array $attributes) {
            return [
                'key' => null,
                'payload' => [
                    'uuid' => $this->faker->uuid,
                    'event' => 'pilot-rules-approved',
                    'affiliationId' => '50666',
                ]
            ];
        });
    }
}
