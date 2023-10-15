<?php

namespace Database\Seeders;

use App\Models\DocumentType;

class DocumentTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->seedFromConfig('documents.types', DocumentType::class);
    }
}
