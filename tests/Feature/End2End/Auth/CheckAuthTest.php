<?php

namespace Tests\Feature\End2End\Auth;

use Tests\TestCase;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class CheckAuthTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function returns_401_if_not_authed()
    {
        $this->json('GET', '/api/authenticated')
            ->assertStatus(401);
    }

    #[Test]
    public function returns_200_if_authenticated()
    {
        $this->actingAs($this->user, 'clerk');
        $this->json('GET', '/api/authenticated')
            ->assertStatus(200);
    }
}
