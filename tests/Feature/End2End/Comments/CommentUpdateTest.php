<?php

namespace Tests\Feature\End2End\comments;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\End2End\Comments\CommentTest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CommentUpdateTest extends CommentTest
{
    public function setup():void
    {
        parent::setup();
        $this->comment = $this->createComment();
    }

    /**
     * @test
     */
    public function creator_can_update_own_comment()
    {
        $expectedData = $this->getDefaultData();
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJson($expectedData)
            ;

        $this->assertDatabaseHas('comments', $this->jsonifyArrays($expectedData));
    }

    /**
     * @test
     */
    public function user_with_comments_manage_perm_can_update_others_comment()
    {
        $otherUser = $this->setupUser(null, ['comments-manage']);
        Sanctum::actingAs($otherUser);
        $expectedData = $this->getDefaultData();
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJson($expectedData)
            ;

        $this->assertDatabaseHas('comments', $this->jsonifyArrays($expectedData));
    }
    
    /**
     * @test
     */
    public function user_cannot_update_others_comment()
    {
        $otherUser = $this->setupUser();
        Sanctum::actingAs($otherUser);
        $expectedData = $this->getDefaultData();
        $this->makeRequest()
            ->assertStatus(403)
            ;

        $this->assertDatabaseMissing('comments', $this->jsonifyArrays($expectedData));
    }
    

    private function makeRequest($data = null)
    {
        $data = $data ?? $this->getDefaultData();
        return $this->json('PUT', '/api/comments/'.$this->comment->id, $data);
    }

    private function getDefaultData($data = [])
    {
        return array_merge([
            'content' => 'updated content',
            'comment_type_id' => config('comments.types.required-revision.id')
        ], $data);
    }
        
    
    
    
}
