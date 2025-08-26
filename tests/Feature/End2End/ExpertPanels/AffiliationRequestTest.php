<?php

namespace Tests\Feature\End2End\ExpertPanels;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class AffiliationRequestTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        // Keep the same helpers you use elsewhere
        $this->setupPermission(['ep-applications-manage']);
        $this->setupForGroupTest();

        Http::preventStrayRequests();
        // 🔸 NOTE: Do NOT authenticate here. We'll do it per-test.
    }

    private function authAs(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        return $user;
    }

    private function endpoint(string $uuid): string
    {
        // Matches POST /api/applications/{app_uuid}/affiliation/request
        return "/api/applications/{$uuid}/affiliation";
    }

    /** @test */
    public function happy_path_requests_affiliation_id_and_persists()
    {
        $this->authAs();

        $ep = ExpertPanel::factory()->vcep()->create(['affiliation_id' => null]);

        Http::fake([
            '*' => Http::response([
                'affiliation_id'  => 10123,  // 5-digit
                'expert_panel_id' => 40123,
            ], 201),
        ]);

        $res = $this->postJson($this->endpoint($ep->uuid));

        $res->assertCreated()
            ->assertJsonPath('affiliation_id', 10123);

        $this->assertSame('10123', (string) $ep->fresh()->affiliation_id);

        $this->assertDatabaseHas('am_affiliation_requests', [
            'expert_panel_id' => $ep->id,
            'status'          => 'success',
        ]);

        Http::assertSentCount(1);
    }

    /** @test */
    public function returns_409_and_does_not_call_am_if_already_has_affiliation_id()
    {
        $this->authAs();

        $ep = ExpertPanel::factory()->gcep()->create(['affiliation_id' => '55555']);

        Http::fake(); // ensure nothing goes out

        $this->postJson($this->endpoint($ep->uuid))
            ->assertStatus(409)
            ->assertJsonPath('affiliation_id', 55555);

        Http::assertNothingSent();
    }

    /** @test */
    public function bubbles_up_am_4xx_as_502_and_audits_failure()
    {
        $this->authAs();

        $ep = ExpertPanel::factory()->vcep()->create(['affiliation_id' => null]);

        Http::fake([
            '*' => Http::response([
                'error'   => 'Validation Failed',
                'details' => ['full_name' => ['required']],
            ], 400),
        ]);

        $this->postJson($this->endpoint($ep->uuid))
            ->assertStatus(502)
            ->assertJsonStructure(['message', 'am_error']);

        $this->assertDatabaseHas('am_affiliation_requests', [
            'expert_panel_id' => $ep->id,
            'status'          => 'failed',
        ]);

        Http::assertSentCount(1);
    }

    /** @test */
    public function unauthenticated_requests_are_401()
    {
        // 🔸 Do NOT authenticate here.
        $ep = ExpertPanel::factory()->gcep()->create(['affiliation_id' => null]);
        Http::fake();

        $this->postJson($this->endpoint($ep->uuid))
            ->assertStatus(401);

        Http::assertNothingSent();
    }
}
