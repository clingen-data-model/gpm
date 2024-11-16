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
                    'cspecDoc' => [
                        'cspecId' => 'ABC1',
                        'uuid' => $this->faker->uuid,
                        'status' => [
                            'event' => 'classified-rules-approved',
                            'current' => 'Classified Rules Approved',
                            'modifiedAt' => Carbon::now(),
                        ],
                        'affiliationId' => '50666',
                        'name' => $this->faker->sentence,
                        'ruleSets' => []
                    ]
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
                    'cspecDoc' => [
                        'cspecId' => 'ABC1',
                        'uuid' => $this->faker->uuid,
                        'status' => [
                            'event' => 'pilot-rules-approved',
                            'current' => 'Pilot Rules Approved',
                            'modifiedAt' => Carbon::now(),
                        ],
                        'affiliationId' => '50666',
                        'name' => $this->faker->sentence,
                        'ruleSets' => [],
                    ]
                ]
            ];
        });
    }
}
