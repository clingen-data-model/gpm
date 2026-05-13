<?php

namespace Tests\Feature\End2End\Comments;

use Tests\TestCase;
use App\Models\Comment;
use App\Modules\Group\Models\Group;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class CommentListTest extends CommentTestAbstract
{
    public function setup():void
    {
        parent::setup();
        Comment::factory()->create([
            'subject_id' => $this->expertPanel->id,
            'subject_type' => get_class($this->expertPanel),
            'creator_type' => get_class($this->user),
            'creator_id' => $this->user->id
        ]);
        Comment::factory()->create([
            'subject_id' => $this->expertPanel->id,
            'subject_type' => get_class($this->expertPanel),
            'creator_type' => get_class($this->user),
            'creator_id' => $this->user->id
        ]);
        Comment::factory()->create([
            'subject_id' => $this->expertPanel->group_id,
            'subject_type' => Group::class,
            'creator_type' => get_class($this->user),
            'creator_id' => $this->user->id
        ]);
    }

    #[Test]
    public function can_retrieve_all_comments()
    {
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJsonCount(3);
    }

    #[Test]
    public function can_retrieve_all_comments_for_a_subject()
    {
        $expectedComments = Comment::where([
                                'subject_type' => get_class($this->expertPanel),
                                'subject_id' => $this->expertPanel->id
                            ])->get();

        $this->makeRequest([
            'where' => [
                'subject_type' => get_class($this->expertPanel),
                'subject_id' => $this->expertPanel->id,
            ],
        ])
            ->assertStatus(200)
            ->assertJsonCount(2);
    }




    private function makeRequest($queryParams = [])
    {
        return $this->json('get', '/api/comments', $queryParams);
    }

}
