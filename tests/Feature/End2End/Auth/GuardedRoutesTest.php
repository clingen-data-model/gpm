<?php

namespace Tests\Feature\End2End\Auth;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Routes that used to be reachable without authentication must now reject
 * anonymous requests. The exact responses of these endpoints are covered
 * elsewhere; this only pins the guard.
 */
class GuardedRoutesTest extends TestCase
{
    public static function guardedRoutes(): array
    {
        return [
            ['GET', '/api/users'],
            ['GET', '/api/users/1'],
            ['PUT', '/api/users/1/roles-and-permissions'],
            ['GET', '/api/report/basic-summary'],
            ['GET', '/api/report/people'],
            ['GET', '/api/next-actions/assignees'],
            ['GET', '/api/applications'],
            ['GET', '/api/people/institutions'],
            ['GET', '/api/people/timezones'],
            ['GET', '/api/people/coc'],
            ['POST', '/api/people/coc/attest'],
            ['PUT', '/api/people/existing-user/invites/abc'],
            ['GET', '/api/people/lookups/countries'],
            ['GET', '/api/countries'],
            ['GET', '/api/cdwgs'],
            ['GET', '/api/document-types'],
            ['GET', '/api/genes/search'],
            ['GET', '/api/diseases/search'],
            ['GET', '/api/mois'],
            ['GET', '/api/curations'],
        ];
    }

    #[Test]
    #[DataProvider('guardedRoutes')]
    public function anonymous_requests_are_rejected(string $method, string $uri)
    {
        $this->json($method, $uri)->assertStatus(401);
    }

    #[Test]
    public function invite_redemption_and_docs_stay_public()
    {
        $this->json('GET', '/api/people/invites/not-a-code')->assertStatus(404);
        $this->json('GET', '/api/docs')->assertStatus(200);
    }
}
