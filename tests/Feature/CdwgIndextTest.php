<?php

namespace Tests\Feature;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use Database\Seeders\GroupTypeSeeder;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class CdwgIndextTest extends TestCase
{
    use FastRefreshDatabase;
    
    public function setup():void
    {
        parent::setup();
        $this->runSeeder(GroupTypeSeeder::class);
        
        Group::cdwg()->get()->each->delete();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    
        $this->cdwgs = Group::factory()->cdwg()->count(10)->create();
    }
        
    /**
     * @test
     */
    public function lists_all_cdwgs_sorted_by_name()
    {
        $this->json('GET', '/api/cdwgs', ['*'])
            ->assertStatus(200)
            ->assertJson($this->cdwgs->sortBy('name')->toArray());
    }
}
