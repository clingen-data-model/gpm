<?php

namespace Tests\Feature\End2End\Comments;

use Tests\TestCase;
use App\Models\Comment;
use Laravel\Sanctum\Sanctum;
use App\Modules\Group\Models\Submission;
use Database\Seeders\CommentTypesSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\SubmissionTypeAndStatusSeeder;

abstract class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->user = $this->setupUserWithPerson(null, ['ep-applications-comment']);
        $this->setupForGroupTest();
        $this->expertPanel = ExpertPanel::factory()->create();
        (new CommentTypesSeeder)->run();
        Sanctum::actingAs($this->user);
    }

    protected function createComment ($data = null) {
        $data = $data ?? [
            'subject_type' => get_class($this->expertPanel->group),
            'subject_id' => $this->expertPanel->group_id,
            'creator_id' => $this->user->id,
            'creator_type' => get_class($this->user)
        ];
        return Comment::factory()->create($data);
    }
}
