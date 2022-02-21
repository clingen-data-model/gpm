<?php

namespace Database\Factories;

use App\Models\Email;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Email::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'to' => [['name' => $this->faker->name, 'address' => $this->faker->email]],
            'from' => [['name' => $this->faker->name, 'address' => $this->faker->email]],
            'cc' => [['name' => $this->faker->name, 'address' => $this->faker->email]],
            'bcc' => [['name' => $this->faker->name, 'address' => $this->faker->email]],
            'reply_to' => null,
            'subject' => $this->faker->sentence,
            'body' => $this->faker->paragraph(),
        ];
    }

    public function oldAddressStructure()
    {
        return $this->state(function (array $attributes) {
            return [
                'to' => [$this->faker->email => $this->faker->name],
                'from' => [$this->faker->email => $this->faker->name],
                'cc' => [$this->faker->email => $this->faker->name],
                'bcc' => [$this->faker->email => $this->faker->name],
            ];
        });
    }
}
