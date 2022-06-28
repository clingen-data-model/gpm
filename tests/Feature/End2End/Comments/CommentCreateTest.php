<?php

namespace Tests\Feature\End2End\Comments;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\Group\Models\Submission;
use Database\Seeders\CommentTypesSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\SubmissionTypeAndStatusSeeder;

class CommentCreateTest extends CommentTest
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function user_can_create_a_comment()
    {
        $expectedData = $this->getDefaultData();
        $this->makeRequest()
            ->assertStatus(201)
            ->assertJson($expectedData);

        $this->assertDatabaseHas('comments', $this->jsonifyArrays($expectedData));
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_create_a_comment()
    {
        $this->user->revokePermissionTo('ep-applications-comment');
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function validates_input()
    {
        $this->makeRequest([])
            ->assertValidationErrors([
                'content' => 'This is required.',
                'subject_type' => 'This is required.',
                'subject_id' => 'This is required.',
                'comment_type_id' => 'This is required.',
            ]);

        $this->makeRequest(['comment_type_id' => 999, 'metadata' => 'bob'])
            ->assertValidationErrors([
                'comment_type_id' => 'The selection is invalid.',
                'metadata' => 'This must be an array.'
            ]);
    }
    
    

    private function makeRequest($data = null)
    {
        $data = $data ?? $this->getDefaultData();
        return $this->json('POST', '/api/comments', $data);
    }

    private function getDefaultData($data = [])
    {
        return array_merge([
            'comment_type_id' => config('comments.types.suggestion.id'),
            'subject_id' => $this->submission->id,
            'subject_type' => get_class($this->submission),
            'content' => 'This is just a suggestion.',
            'metadata' => ['section' => 'basic-info']
        ], $data);
    }
    
    
    
    
    
}
