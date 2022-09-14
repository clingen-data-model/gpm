<?php

namespace Database\Factories;

use App\Modules\Person\Models\Person;
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
        $person = Person::factory()->create();
        return [
            'comment_type_id' => $this->faker()->randomElement(config('comments.types'))['id'],
            'content' => $this->faker()->sentence,
            'resolved_at' => null,
            'creator_type' => Person::class,
            'creator_id' => $person->id
        ];
    }
}
