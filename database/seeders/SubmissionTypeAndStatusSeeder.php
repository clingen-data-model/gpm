<?php

namespace Database\Seeders;

use Database\Seeders\Seeder;
use App\Modules\Group\Models\SubmissionType;
use App\Modules\Group\Models\SubmissionStatus;

class SubmissionTypeAndStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seedFromConfig('submissions.statuses', SubmissionStatus::class);

        $types = collect(config('submissions.types'))->flatten(1)->toArray();
        $this->seedFromArray($types, SubmissionType::class);
    }
}
