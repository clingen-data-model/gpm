<?php

namespace Database\Factories;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Document::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'uuid' => Uuid::uuid4(),
            'filename' => uniqid(),
            'storage_path' => $this->faker->file(base_path('tests/files')),
            'step' => $this->faker->randomElement(range(1,4)),
            'metadata' => null,
            'document_category_id' => $this->faker->randomElement(config('documents.categories'))['id'],
            'version' => 1,
            'date_received' => Carbon::now(),
            'date_reviewed' => null
        ];
    }
}
