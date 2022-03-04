<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Cdwg;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use Database\Seeders\GroupTypeSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CdwgIndextTest extends TestCase
{
    use RefreshDatabase;
    
    public function setup():void
    {
        parent::setup();
        $this->runSeeder(GroupTypeSeeder::class);
        
        // Cdwg::all()->each->delete();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    
        $this->cdwgs = Cdwg::factory()->count(13)->create();
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
