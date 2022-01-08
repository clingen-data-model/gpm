<?php

namespace Tests\Feature\End2End\Groups\Documents;

use App\Models\Document;
use Tests\TestCase;
use Ramsey\Uuid\Uuid;
use Laravel\Sanctum\Sanctum;
use Illuminate\Http\UploadedFile;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();

        $this->user = $this->setupUserWithPerson(null, ['groups-manage']);
        $this->group = Group::factory()->create();
        $document = Document::factory()->make([
            'filename' => 'Test Scope Document.docx',
            'owner_id' => $this->group->id,
            'owner_type' => Group::class,
            'document_type_id' => 1
        ]);
        $this->document = $this->group->documents()->save($document);

        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_update_document_in_group()
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
                'document_type_id' => ['This is required.'],
            ]);
        
        $this->makeRequest(['document_type_id' => 9999])
            ->assertStatus(422)
            ->assertJsonFragment([
                'document_type_id' => ['The selected type is invalid.']
            ]);
    }

    /**
     * @test
     */
    public function privilged_user_can_update_file_info()
    {
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJsonFragment([
                'notes' => 'I am a note',
                'document_type_id' => 2,
            ])
            ->assertJsonFragment([
                'type' => [
                    'id' => 2,
                    'name' => 'draft-specs',
                    'long_name' => 'draft specifications',
                    'is_versioned' => true,
                    'application_document' => true,
                ]
            ]);

        $this->assertDatabaseHas('documents', [
            'owner_id' => $this->group->id,
            'owner_type' => Group::class,
            'filename' => 'Test Scope Document.docx',
            'notes' => 'I am a note',
            'document_type_id' => 2
        ]);
    }
    
    private function makeRequest($data = null)
    {
        Storage::fake();
        $filename = $filename ?? 'Test Scope Document.docx';
        $file = UploadedFile::fake()->create(name: $filename, mimeType: 'docx');

        $data = $data ?? [
            'uuid' => Uuid::uuid4()->toString(),
            'document_type_id' => 2,
            'notes' => 'I am a note'
        ];

        return $this->json('PUT', '/api/groups/'.$this->group->uuid.'/documents/'.$this->document->uuid, $data);
    }
}
