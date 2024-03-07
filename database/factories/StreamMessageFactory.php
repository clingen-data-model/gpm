<?php

namespace Database\Factories;

use Carbon\Carbon;
use App\DataExchange\Models\StreamMessage;
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
     *
     * @return array
     */
    public function definition()
    {
        $success = (bool)rand(0, 1);
        return [
            'topic' => 'test',
            'message' => json_encode(['test' => 'test']),
            'sent_at' => $success ? Carbon::now() : null,
            'error' => $success ? null : 'error'
        ];
    }
}
