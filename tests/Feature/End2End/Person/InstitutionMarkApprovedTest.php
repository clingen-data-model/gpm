<?php

namespace Tests\Feature\End2End\Person;

use App\Modules\Person\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InstitutionMarkApprovedTest extends TestCase
{
    use RefreshDatabase;

    public function setup(): void
    {
        parent::setup();
        $this->institution = Institution::factory()->create(['approved' => false]);
        $this->user = $this->setupUser(permissions: ['people-manage']);
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function unprivileged_user_cannot_approve_institution()
    {
        $this->user->revokePermissionTo('people-manage');

        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function privileged_use_can_approve_institution()
    {
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJson([
                'id' => $this->institution->id,
                'name' => $this->institution->name,
                'approved' => true,
            ]);

        $this->assertDatabaseHas('institutions', [
            'id' => $this->institution->id,
            'approved' => 1,
        ]);
    }

    private function makeRequest()
    {
        return $this->json('put', '/api/institutions/'.$this->institution->id.'/approved');
    }
}
