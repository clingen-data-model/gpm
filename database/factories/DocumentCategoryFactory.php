<?php

namespace Database\Factories;

use App\Models\DocumentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentCategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DocumentCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => uniqid()
        ];
    }
}
