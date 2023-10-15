<?php

namespace Tests\Feature\End2End\Person;

use App\Modules\Person\Models\Institution;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InstitutionDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function setup(): void
    {
        parent::setup();
        $this->institution = Institution::factory()->create();
    }

    /**
     * @test
     */
    public function guest_cannot_delete_an_institution(): void
    {
        $this->makeRequest()->assertStatus(401);
    }

    /**
     * @test
     */
    public function user_without_permission_cannot_delete_an_institution(): void
    {
        $user = $this->setupUser();
        Sanctum::actingAs($user);

        $this->makeRequest()->assertStatus(403);
    }

    /**
     * @test
     */
    public function permissioned_user_can_delete_an_institution(): void
    {
        $user = $this->setupUser(permissions: ['people-manage']);
        Sanctum::actingAs($user);

        Carbon::setTestNow('2022-09-23');
        $this->makeRequest()->assertStatus(200);

        $this->assertDatabaseHas('institutions', [
            'id' => $this->institution->id,
            'deleted_at' => Carbon::now(),
        ]);
    }

    private function makeRequest(): TestResponse
    {
        return $this->json('delete', '/api/institutions/'.$this->institution->id);
    }
}
