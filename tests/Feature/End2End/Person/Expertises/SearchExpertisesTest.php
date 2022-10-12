<?php

namespace Tests\Feature\End2End\Person\Expertises;

use Tests\TestCase;
use App\Models\Expertise;
use Database\Seeders\ExpertiseSeeder;
use Illuminate\Testing\TestResponse;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group expertises
 */
class SearchExpertisesTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->expertises = $this->seedExpertises();
    }

    /**
     * @test
     */
    public function guests_cannot_search_expertises()
    {
        $this->makeRequest()
            ->assertStatus(401);
    }

    /**
     * @test
     */
    public function gets_all_expertises_if_no_keyword()
    {
        $this->login();

        $this->makeRequest()
            ->assertStatus(200)
            ->assertJsonCount($this->expertises->count());
    }

    /**
     * @test
     */
    public function gets_matching_expertises_if_keyword_given()
    {
        $this->login();

        $response = $this->makeRequest('genetic')
            ->assertStatus(200)
            ->assertJsonCount(3);

        $this->makeRequest('Basic research scientist')
            ->assertStatus(200)
            ->assertJsonCount(1);
    }

    /**
     * @test
     */
    public function ignores_case()
    {
        $this->login();
        $this->makeRequest('physician')
            ->assertStatus(200)
            ->assertJsonCount(1);
    }

    /**
     * @test
     */
    public function searches_against_synonyms()
    {
        $this->login();
        $this->makeRequest('lcgc')
            ->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'id' => 4,
                'name' => 'Genetic counselor'
            ]);
    }




    private function makeRequest(?string $keyword = null): TestResponse
    {
        $url = '/api/expertises';
        if ($keyword) {
            $url .= '?keyword='.$keyword;
        }
        return $this->json('GET', $url);
    }

    private function seedExpertises()
    {
        (new ExpertiseSeeder)->run();
        return Expertise::all();
    }

}
