<?php

namespace Database\Seeders;

use Database\Seeders\Seeder;
use App\Models\DocumentType;

class DocumentTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seedFromConfig('documents.types', DocumentType::class);
    }
}
