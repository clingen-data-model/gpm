<?php

namespace Tests\Feature\End2End\Person;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\Person\Models\Person;
use App\Modules\Person\Models\Institution;
use Illuminate\Foundation\Testing\WithFaker;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class MergeInstitutionsTest extends TestCase
{
    use FastRefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupPermission('people-manage');
        $this->user = $this->setupUser(permissions: ['people-manage']);
        Sanctum::actingAs($this->user);
        
        $this->institution1 = Institution::factory()->create();
        $this->institution2 = Institution::factory()->create();
        $this->institution3 = Institution::factory()->create();

        $this->person1 = Person::factory()->create(['institution_id' => $this->institution1->id]);
        $this->person2 = Person::factory()->create(['institution_id' => $this->institution2->id]);
        $this->person3 = Person::factory()->create(['institution_id' => $this->institution3->id]);
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_merge_institutions()
    {
        $this->user->revokePermissionTo('people-manage');
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function privileged_user_can_merge_institutions()
    {
        $this->makeRequest()
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function obsolete_institutions_are_deleted()
    {
        $this->makeRequest()
            ->assertStatus(200);

        $this->assertDatabaseHas('institutions', ['id' => $this->institution1->id, 'deleted_at' => null]);
        $this->assertSoftDeleted($this->institution2);
        $this->assertSoftDeleted($this->institution3);
    }

    /**
     * @test
     */
    public function people_belonging_to_obsolete_are_moved_to_authority()
    {
        $this->makeRequest()
            ->assertStatus(200);

        $this->assertDatabaseHas('people', [
            'id' => $this->person2->id,
            'institution_id' => $this->institution1->id
        ]);
        $this->assertDatabaseHas('people', [
            'id' => $this->person3->id,
            'institution_id' => $this->institution1->id
        ]);
    }
    
    /**
     * @test
     */
    public function validates_required_data()
    {
        $this->makeRequest([])
            ->assertStatus(422)
            ->assertJsonFragment(['authority_id' => ['This is required.']])
            ->assertJsonFragment(['obsolete_ids' => ['This is required.']]);
    }

    /**
     * @test
     */
    public function validates_existing_institutions()
    {
        $this->makeRequest(['authority_id' => 777, 'obsolete_ids' => [555, 666]])
            ->assertStatus(422)
            ->assertJsonFragment(['authority_id' => ['The selection is invalid.']])
            ->assertJsonFragment(['obsolete_ids.0' => ['The selection is invalid.']])
            ->assertJsonFragment(['obsolete_ids.1' => ['The selection is invalid.']]);
    }

    /**
     * @test
     */
    public function validates_obsolete_cannot_match_authority()
    {
        $this->makeRequest([
                'authority_id' => $this->institution1->id,
                'obsolete_ids' => [$this->institution1->id, $this->institution2->id]
            ])
            ->assertStatus(422)
            ->assertJsonFragment(['obsolete_ids.0' => ['All obsolete institutions may not include the merge-to institution.']]);
    }
    
    
    
    

    private function makeRequest($data = null)
    {
        $data = $data ?? [
            'authority_id' => $this->institution1->id,
            'obsolete_ids' => [$this->institution2->id, $this->institution3->id]
        ];

        return $this->json('PUT', '/api/institutions/merge', $data);
    }
}
