<?php

namespace Tests\Feature\End2End\Person\Credentials;

use Tests\TestCase;
use App\Models\Credential;
use Database\Seeders\CredentialSeeder;
use Illuminate\Testing\TestResponse;
use Illuminate\Foundation\Testing\WithFaker;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

/**
 * @group credentials
 */
class SearchCredentialsTest extends TestCase
{
    use FastRefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->credentials = $this->seedCredentials();
    }

    /**
     * @test
     */
    public function guests_cannot_search_credentials()
    {
        $this->makeRequest()
            ->assertStatus(401);
    }

    /**
     * @test
     */
    public function gets_all_credentials_if_no_keyword()
    {
        $this->login();

        $this->makeRequest()
            ->assertStatus(200)
            ->assertJsonCount($this->credentials->count());
    }

    /**
     * @test
     */
    public function gets_matching_credentials_if_keyword_given()
    {
        $this->login();

        $response = $this->makeRequest('m')
            ->assertStatus(200)
            ->assertJsonCount(8);

        $this->makeRequest('ms')
            ->assertStatus(200)
            ->assertJsonCount(1);
    }

    /**
     * @test
     */
    public function ignores_case_and_punctuation()
    {
        $this->login();
        $this->makeRequest('p.h.d')
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
                'id' => 1,
                'name' => 'CGC'
            ]);
    }




    private function makeRequest(?string $keyword = null): TestResponse
    {
        $url = '/api/credentials';
        if ($keyword) {
            $url .= '?keyword='.$keyword;
        }
        return $this->json('GET', $url);
    }

    private function seedCredentials()
    {
        (new CredentialSeeder)->run();
        return Credential::all();
    }

}
