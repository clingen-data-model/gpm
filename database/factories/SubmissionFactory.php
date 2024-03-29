<?php

namespace Database\Factories;

use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Models\Submission;
use App\Modules\Group\Models\SubmissionType;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubmissionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Submission::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'group_id' => Group::factory()->create()->id,
            'submission_type_id' => SubmissionType::factory(),
            'submission_status_id' => 1,
            'submitter_id' => Person::factory()
        ];
    }
}
