<?php

namespace Tests\Feature\End2End\Comments;

use App\Models\Comment;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Database\Seeders\CommentTypesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

abstract class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function setup(): void
    {
        parent::setup();
        $this->user = $this->setupUserWithPerson(null, ['ep-applications-comment']);
        $this->setupForGroupTest();
        $this->expertPanel = ExpertPanel::factory()->create();
        (new CommentTypesSeeder)->run();
        Sanctum::actingAs($this->user);
    }

    protected function createComment($data = null)
    {
        $data = $data ?? [
            'subject_type' => $this->expertPanel->group::class,
            'subject_id' => $this->expertPanel->group_id,
            'creator_id' => $this->user->id,
            'creator_type' => $this->user::class,
        ];

        return Comment::factory()->create($data);
    }
}
