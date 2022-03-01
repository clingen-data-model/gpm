<?php

namespace Tests\Feature\End2End\Person;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\Person\Models\Person;
use App\Modules\Person\Models\Institution;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MergeInstitutionsTest extends TestCase
{
    use RefreshDatabase;

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
    
    
    

    private function makeRequest($data = null)
    {
        $data = $data ?? [
            'authority_id' => $this->institution1->id,
            'obsolete_ids' => [$this->institution2->id, $this->institution3->id]
        ];

        return $this->json('PUT', '/api/institutions/merge', $data);
    }
}
