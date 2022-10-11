<?php

namespace Database\Factories;

use App\Models\CommentType;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Database\Eloquent\Factories\Factory;

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
        $person = Person::factory()->create();
        return [
            'comment_type_id' => CommentType::factory(),
            'content' => $this->faker()->sentence,
            'resolved_at' => null,
            'creator_type' => Person::class,
            'creator_id' => $person->id
        ];
    }
}
