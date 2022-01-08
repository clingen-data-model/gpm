<?php

namespace Tests\Feature\End2End\Groups\Documents;

use Tests\TestCase;
use Ramsey\Uuid\Uuid;
use Laravel\Sanctum\Sanctum;
use Illuminate\Http\UploadedFile;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AddDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->user = $this->setupUserWithPerson(null, ['groups-manage']);
        $this->group = Group::factory()->create();

        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_add_document_for_group()
    {
        $this->user->revokePermissionTo('groups-manage');
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function validates_data()
    {
        $this->makeRequest([])
            ->assertStatus(422)
            ->assertJsonFragment([
                'file' => ['This is required.'],
                'document_type_id' => ['This is required.'],
            ]);
    }

    /**
     * @test
     */
    public function privilged_user_can_upload_a_file()
    {
        $this->makeRequest()
            ->assertStatus(201)
            ->assertJsonFragment([
                'filename' => 'Test Scope Document.docx',
                'notes' => null,
                'document_type_id' => 1,
            ])
            ->assertJsonFragment([
                'type' => [
                    'id' => 1,
                    'name' => 'scope',
                    'long_name' => 'scope and membership application',
                    'is_versioned' => true,
                    'application_document' => true,
                ]
            ]);

        $this->assertDatabaseHas('documents', [
            'owner_id' => $this->group->id,
            'owner_type' => Group::class,
            'filename' => 'Test Scope Document.docx',
            'notes' => null
        ]);
    }
    
    private function makeRequest($data = null)
    {
        Storage::fake();
        $filename = $filename ?? 'Test Scope Document.docx';
        $file = UploadedFile::fake()->create(name: $filename, mimeType: 'docx');

        $data = $data ?? [
            'uuid' => Uuid::uuid4()->toString(),
            'file' => $file,
            'document_type_id' => 1,
            'notes' => null
        ];

        return $this->json('POST', '/api/groups/'.$this->group->uuid.'/documents', $data);
    }
}
