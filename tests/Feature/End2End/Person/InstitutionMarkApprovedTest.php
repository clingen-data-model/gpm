<?php

namespace Tests\Feature\End2End\Person;

use Tests\TestCase;
use App\Models\Permission;
use App\Modules\Person\Models\Institution;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class InstitutionMarkApprovedTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->institution = Institution::factory()->create(['approved' => false]);
        $this->user = $this->setupUser(permissions: ['people-manage']);
        $this->actingAs($this->user, 'clerk');
    }
    
    #[Test]
    public function unprivileged_user_cannot_approve_institution()
    {
        $this->user->revokePermissionTo('people-manage');
        
        $this->makeRequest()
            ->assertStatus(403);
    }

    #[Test]
    public function privileged_use_can_approve_institution()
    {
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJson([
                'id' => $this->institution->id,
                'name' => $this->institution->name,
                'approved' => true
            ]);

        $this->assertDatabaseHas('institutions', [
            'id' => $this->institution->id,
            'approved' => 1
        ]);
    }
    
    private function makeRequest()
    {
        return $this->json('put', '/api/institutions/'.$this->institution->id.'/approved');
    }
}
