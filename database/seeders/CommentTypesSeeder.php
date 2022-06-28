<?php

namespace Database\Seeders;

use App\Models\CommentType;
use Database\Seeders\Seeder;

class CommentTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seedFromArray(config('comments.types'), CommentType::class);
    }
}
