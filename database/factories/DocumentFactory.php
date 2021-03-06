<?php

namespace Database\Factories;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use App\Models\Document;
use App\Models\DocumentType;
use App\Modules\Group\Models\Group;
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
        $group = Group::all()->random() ?? Group::factory()->create();
        return [
            'uuid' => Uuid::uuid4(),
            'owner_id' => $group->id,
            'owner_type' => get_class($group),
            'document_type_id' => $this->faker->randomElement(config('documents.types'))['id'],
            'filename' => uniqid(),
            'storage_path' => $this->faker->file(base_path('tests/files')),
            'metadata' => [
                'step' => $this->faker->randomElement(range(1, 4)),
                'version' => 1,
                'date_received' => Carbon::now(),
            ],
        ];
    }
}
