<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use Database\Seeders\GroupTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class CdwgIndextTest extends TestCase
{
    use RefreshDatabase;
    
    public function setup():void
    {
        parent::setup();
        $this->runSeeder(GroupTypeSeeder::class);
        
        Group::cdwg()->get()->each->delete();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'clerk');
    
        $this->cdwgs = Group::factory()->cdwg()->count(10)->create();
    }
        
    #[Test]
    public function lists_all_cdwgs_sorted_by_name()
    {
        $expected = $this->cdwgs
            ->sortBy('name')
            ->map(fn ($cdwg) => ['id' => $cdwg->id, 'name' => $cdwg->name])
            ->values()
            ->all();

        $response = $this->json('GET', '/api/cdwgs', ['*'])
            ->assertStatus(200);

        // The endpoint selects only id and name; the model's appended accessors
        // still serialize, so compare on the fields it actually promises.
        $actual = collect($response->json())
            ->map(fn ($cdwg) => ['id' => $cdwg['id'], 'name' => $cdwg['name']])
            ->all();

        $this->assertEquals($expected, $actual);
    }
}
