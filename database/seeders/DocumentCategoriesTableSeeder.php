<?php

namespace Database\Seeders;

use Database\Seeders\Seeder;
use App\Models\DocumentCategory;

class DocumentCategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seedFromConfig('documents.categories', DocumentCategory::class);        
    }
}
