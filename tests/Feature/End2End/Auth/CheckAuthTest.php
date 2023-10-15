<?php

namespace Tests\Feature\End2End\Auth;

use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckAuthTest extends TestCase
{
    use RefreshDatabase;

    public function setup(): void
    {
        parent::setup();
        $this->user = User::factory()->create();
    }

    /**
     * @test
     */
    public function returns_401_if_not_authed(): void
    {
        $this->json('GET', '/api/authenticated')
            ->assertStatus(401);
    }

    /**
     * @test
     */
    public function returns_200_if_authenticated(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->user);
        $this->json('GET', '/api/authenticated')
            ->assertStatus(200);
    }
}
