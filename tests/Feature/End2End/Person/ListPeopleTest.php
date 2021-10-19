<?php

namespace Tests\Feature\End2End\Person;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group people
 */
class ListPeopleTest extends TestCase
{
    use RefreshDatabase;

    const URL = '/api/people';

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->user = User::factory()->create();
        $this->people = Person::factory(5)->create();
    }

    /**
     * @test
     */
    public function guests_cannot_get_list_of_people()
    {
        $this->json('GET', static::URL)
            ->assertStatus(401);
    }
    
    /**
     * @test
     */
    public function authed_user_can_get_paginated_list_of_people()
    {
        Sanctum::actingAs($this->user);
        $response = $this->json('GET', static::URL);
        $response->assertStatus(200);
        $this->assertEquals(5, $response->original->count());
    }
}
