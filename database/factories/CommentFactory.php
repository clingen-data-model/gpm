<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\WithFaker;

class CommentFactory extends Factory
{
    use WithFaker;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'comment_type_id' => $this->faker()->randomElement(config('comments.types'))['id'],
            'content' => $this->faker()->sentence,
        ];
    }
}
