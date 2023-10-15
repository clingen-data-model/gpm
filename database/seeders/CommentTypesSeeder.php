<?php

namespace Database\Seeders;

use App\Models\CommentType;

class CommentTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedFromArray(config('comments.types'), CommentType::class);
    }
}
