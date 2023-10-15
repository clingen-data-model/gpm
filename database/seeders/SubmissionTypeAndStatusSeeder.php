<?php

namespace Database\Seeders;

use App\Modules\Group\Models\SubmissionStatus;
use App\Modules\Group\Models\SubmissionType;

class SubmissionTypeAndStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedFromConfig('submissions.statuses', SubmissionStatus::class);

        $types = collect(config('submissions.types'))->flatten(1)->toArray();
        $this->seedFromArray($types, SubmissionType::class);
    }
}
