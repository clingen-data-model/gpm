<?php

namespace Database\Factories;

use App\DataExchange\Models\StreamMessage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class StreamMessageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StreamMessage::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $success = (bool) rand(0, 1);

        return [
            'topic' => 'test',
            'message' => $this->faker->sentence(),
            'sent_at' => $success ? Carbon::now() : null,
            'error' => $success ? null : $this->faker->sentence(),
        ];
    }
}
