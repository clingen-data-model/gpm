<?php

namespace Tests\Feature\Auth;

use App\Auth\CILogon\CILogonIdpDirectory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CILogonIdpListTest extends TestCase
{
    const LIST_URL = 'https://cilogon.test/idplist/';

    public function setUp(): void
    {
        parent::setUp();
        config([
            'services.cilogon.client_id' => 'cilogon:/client_id/test',
            'services.cilogon.base_url' => 'https://cilogon.org',
            'services.cilogon.redirect' => 'https://gpm.test/auth/cilogon/callback',
            'services.cilogon.idp_list_url' => self::LIST_URL,
        ]);
        Cache::forget(CILogonIdpDirectory::CACHE_KEY);
    }

    private function fakeList(): void
    {
        Http::fake([self::LIST_URL => Http::response([
            ['EntityID' => 'urn:mace:incommon:duke.edu', 'OrganizationName' => 'Duke University', 'DisplayName' => 'Duke University', 'RandS' => true],
            ['EntityID' => 'https://idp.unc.edu/idp', 'OrganizationName' => 'UNC', 'DisplayName' => 'University of North Carolina at Chapel Hill', 'RandS' => true],
            ['EntityID' => 'https://idp.example.org', 'OrganizationName' => 'Example', 'DisplayName' => 'Not Duke College', 'RandS' => false],
        ])]);
    }

    private function hintFrom(string $url): ?string
    {
        $location = $this->get($url)->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $q);

        return $q['idphint'] ?? null;
    }

    #[Test]
    public function returns_the_common_providers_without_a_query()
    {
        Http::fake();

        $this->getJson('/auth/cilogon/idps')
            ->assertOk()
            ->assertJsonPath('common.0.name', 'ORCID');
        Http::assertNothingSent();
    }

    #[Test]
    public function searches_the_idp_list_with_prefix_matches_first()
    {
        $this->fakeList();

        $this->getJson('/auth/cilogon/idps?q=duke')
            ->assertOk()
            ->assertExactJson(['results' => [
                ['id' => 'urn:mace:incommon:duke.edu', 'name' => 'Duke University'],
                ['id' => 'https://idp.example.org', 'name' => 'Not Duke College'],
            ]]);
    }

    #[Test]
    public function ignores_one_character_queries()
    {
        $this->fakeList();

        $this->getJson('/auth/cilogon/idps?q=d')->assertExactJson(['results' => []]);
    }

    #[Test]
    public function a_failed_fetch_returns_nothing_and_is_not_cached()
    {
        Http::fakeSequence(self::LIST_URL)->push('oops', 500)->push([
            ['EntityID' => 'urn:mace:incommon:duke.edu', 'DisplayName' => 'Duke University'],
        ]);

        $this->getJson('/auth/cilogon/idps?q=duke')->assertExactJson(['results' => []]);
        $this->getJson('/auth/cilogon/idps?q=duke')->assertJsonPath('results.0.name', 'Duke University');
    }

    #[Test]
    public function the_list_is_fetched_once_and_cached()
    {
        $this->fakeList();

        $this->getJson('/auth/cilogon/idps?q=duke');
        $this->getJson('/auth/cilogon/idps?q=north');

        Http::assertSentCount(1);
    }

    #[Test]
    public function returns_404_when_cilogon_is_not_configured()
    {
        config(['services.cilogon.client_id' => null]);

        $this->getJson('/auth/cilogon/idps')->assertNotFound();
    }

    #[Test]
    public function redirect_passes_a_common_provider_as_idphint()
    {
        Http::fake();

        $this->assertSame('http://orcid.org/oauth/authorize', $this->hintFrom('/auth/cilogon/redirect?idp='.urlencode('http://orcid.org/oauth/authorize')));
    }

    #[Test]
    public function redirect_passes_a_listed_institution_as_idphint()
    {
        $this->fakeList();

        $this->assertSame('urn:mace:incommon:duke.edu', $this->hintFrom('/auth/cilogon/redirect?idp='.urlencode('urn:mace:incommon:duke.edu')));
    }

    #[Test]
    public function redirect_drops_an_unknown_idp()
    {
        $this->fakeList();

        $this->assertNull($this->hintFrom('/auth/cilogon/redirect?idp=https://evil.example'));
    }
}
