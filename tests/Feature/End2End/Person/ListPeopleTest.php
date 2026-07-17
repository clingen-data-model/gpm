<?php

namespace Tests\Feature\End2End\Person;

use Tests\TestCase;
use App\Modules\User\Models\User;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('people')]
class ListPeopleTest extends TestCase
{
    use RefreshDatabase;

    const URL = '/api/people';

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->user = $this->setupUser();
        $this->people = Person::factory(5)->create();
    }

    #[Test]
    public function guests_cannot_get_list_of_people()
    {
        $this->json('GET', static::URL)
            ->assertStatus(401);
    }
    
    #[Test]
    public function authed_user_can_get_paginated_list_of_people()
    {
        $this->actingAs($this->user, 'clerk');
        $response = $this->json('GET', static::URL);
        $response->assertStatus(200);
        $this->assertEquals(5, $response->original->count());
    }
}
