<?php

namespace Tests\Feature;

use App\Modules\Group\Models\GroupMember;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use phpseclib3\Crypt\RSA;
use Tests\CreatesApplication;

class ClingenPersonEndpointTest extends TestCase
{
    use CreatesApplication;

    private const PERSON_UUID = 'e9aa0b35-39b0-4ca5-8656-3104b8b8eb41';
    private const GROUP_UUID = '8ae07de4-ed79-47e5-a8a1-7f90459f0694';

    protected function setUp(): void
    {
        parent::setUp();

        // Deliberately avoid RefreshDatabase: no application migrations are run.
        config([
            'database.default' => 'clingen_test',
            'database.connections.clingen_test' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ],
            'passport.connection' => 'clingen_test',
            'cache.default' => 'array',
        ]);

        $key = RSA::createKey(2048);
        config([
            'passport.private_key' => (string) $key,
            'passport.public_key' => (string) $key->getPublicKey(),
        ]);

        // Minimal relational fixtures, including the existing legacy OAuth schema.
        $tables = [
            'oauth_clients' => 'id varchar(36) primary key, user_id integer null, name text, secret varchar(100), provider text null, redirect text, personal_access_client boolean, password_client boolean, revoked boolean, created_at datetime, updated_at datetime',
            'oauth_access_tokens' => 'id varchar(100) primary key, user_id integer null, client_id varchar(36), name text null, scopes text, revoked boolean, created_at datetime, updated_at datetime, expires_at datetime',
            'people' => 'id integer primary key, uuid text, first_name text, last_name text, email text, institution_id integer null, deleted_at datetime null, biography text null, clerk_user_id text null',
            'institutions' => 'id integer primary key, name text, deleted_at datetime null',
            'credentials' => 'id integer primary key, name text',
            'credential_person' => 'person_id integer, credential_id integer, sort_order integer',
            'groups' => 'id integer primary key, uuid text, name text, group_type_id integer, group_status_id integer null, deleted_at datetime null',
            'group_types' => 'id integer primary key, name text',
            'group_statuses' => 'id integer primary key, name text',
            'expert_panels' => 'id integer primary key, group_id integer, affiliation_id text null, deleted_at datetime null',
            'group_members' => 'id integer primary key, person_id integer, group_id integer, start_date datetime null, end_date datetime null, deleted_at datetime null, notes text null',
            'roles' => 'id integer primary key, name text, guard_name text, scope text',
            'model_has_roles' => 'role_id integer, model_id integer, model_type text',
        ];

        foreach ($tables as $table => $columns) {
            DB::statement("create table $table ($columns)");
        }

        DB::table('people')->insert([
            'id' => 1, 'uuid' => self::PERSON_UUID,
            'first_name' => 'Alex', 'last_name' => 'Smith', 'email' => 'alex@example.test',
            'biography' => 'Private biography', 'clerk_user_id' => 'private-clerk-id',
        ]);
    }

    public function test_request_without_client_token_is_rejected(): void
    {
        $this->getJson($this->endpoint())->assertUnauthorized();
        // Authentication precedes UUID binding, even for a missing person.
        $this->getJson('/api/clingen/people/unknown')->assertUnauthorized();
    }

    public function test_invalid_client_token_is_rejected(): void
    {
        $this->withToken('invalid-token')->getJson($this->endpoint())->assertUnauthorized();
    }

    public function test_client_token_without_required_scope_is_rejected(): void
    {
        $this->withToken($this->token(''))->getJson($this->endpoint())->assertForbidden();
    }

    public function test_client_token_with_required_scope_succeeds(): void
    {
        $this->withToken($this->token())->getJson($this->endpoint())->assertOk()->assertExactJson([
            'data' => [
                'uuid' => self::PERSON_UUID, 'first_name' => 'Alex', 'last_name' => 'Smith',
                'email' => 'alex@example.test', 'institution' => null,
                'credentials' => [], 'memberships' => [],
            ],
        ]);
    }

    public function test_unknown_or_deleted_person_returns_404(): void
    {
        $this->withToken($this->token())->getJson('/api/clingen/people/00000000-0000-4000-8000-000000000000')
            ->assertNotFound();

        DB::table('people')->where('id', 1)->update(['deleted_at' => '2026-01-01']);
        $this->getJson($this->endpoint())->assertNotFound();
    }

    public function test_memberships_and_roles_reflect_current_gpm_records(): void
    {
        DB::table('institutions')->insert(['id' => 1, 'name' => 'Example University']);
        DB::table('people')->where('id', 1)->update(['institution_id' => 1]);
        DB::table('credentials')->insert([['id' => 1, 'name' => 'MD'], ['id' => 2, 'name' => 'PhD']]);
        DB::table('credential_person')->insert([
            ['person_id' => 1, 'credential_id' => 1, 'sort_order' => 2],
            ['person_id' => 1, 'credential_id' => 2, 'sort_order' => 1],
        ]);
        DB::table('group_types')->insert(['id' => 1, 'name' => 'vcep']);
        DB::table('groups')->insert([
            ['id' => 1, 'uuid' => self::GROUP_UUID, 'name' => 'Example panel', 'group_type_id' => 1, 'deleted_at' => null],
            ['id' => 2, 'uuid' => 'removed-group', 'name' => 'Removed group', 'group_type_id' => 1, 'deleted_at' => '2026-01-01'],
        ]);
        DB::table('expert_panels')->insert(['id' => 1, 'group_id' => 1, 'affiliation_id' => '50001']);
        foreach ([
            [1, 1, 1, null, null],
            [2, 1, 1, '2025-12-31', null],
            [3, 1, 1, null, '2026-01-01'],
            [4, 2, 1, null, null],
            [5, 1, 2, null, null],
        ] as [$id, $personId, $groupId, $endDate, $deletedAt]) {
            DB::table('group_members')->insert([
                'id' => $id, 'person_id' => $personId, 'group_id' => $groupId,
                'start_date' => '2025-01-01', 'end_date' => $endDate, 'deleted_at' => $deletedAt,
                'notes' => 'Private membership notes',
            ]);
        }
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'coordinator', 'guard_name' => 'web', 'scope' => 'group'],
            ['id' => 2, 'name' => 'chair', 'guard_name' => 'web', 'scope' => 'group'],
        ]);
        DB::table('model_has_roles')->insert([
            ['role_id' => 1, 'model_id' => 1, 'model_type' => (new GroupMember)->getMorphClass()],
            ['role_id' => 2, 'model_id' => 1, 'model_type' => (new GroupMember)->getMorphClass()],
        ]);

        $group = ['uuid' => self::GROUP_UUID, 'name' => 'Example panel', 'type' => 'vcep', 'affiliation_id' => '50001'];
        $this->withToken($this->token())->getJson($this->endpoint())->assertOk()->assertExactJson([
            'data' => [
                'uuid' => self::PERSON_UUID, 'first_name' => 'Alex', 'last_name' => 'Smith',
                'email' => 'alex@example.test', 'institution' => 'Example University',
                'credentials' => ['PhD', 'MD'],
                'memberships' => [
                    ['status' => 'active', 'start_date' => '2025-01-01', 'end_date' => null, 'group' => $group, 'roles' => ['chair', 'coordinator']],
                    ['status' => 'retired', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31', 'group' => $group, 'roles' => []],
                ],
            ],
        ]);

        DB::table('model_has_roles')->where('role_id', 2)->delete();
        DB::table('group_members')->where('id', 1)->update(['end_date' => '2026-02-01']);
        $this->getJson($this->endpoint())->assertOk()
            ->assertJsonPath('data.memberships.0.roles', ['coordinator'])
            ->assertJsonPath('data.memberships.0.status', 'retired')
            ->assertJsonPath('data.memberships.0.end_date', '2026-02-01');
    }

    public function test_revoked_token_is_rejected(): void
    {
        $token = $this->token();
        DB::table('oauth_access_tokens')->update(['revoked' => true]);
        $this->withToken($token)->getJson($this->endpoint())->assertUnauthorized();
    }

    private function token(string $scope = 'clingen-people-read'): string
    {
        $this->assertTrue(Passport::hasScope('clingen-people-read'));
        $client = app(ClientRepository::class)->createClientCredentialsGrantClient('Test client');

        // Exercise real issuance, signature validation, stored revocation, and scopes.
        return $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->id,
            'client_secret' => $client->plainSecret,
            'scope' => $scope,
        ])->assertOk()->json('access_token');
    }

    private function endpoint(): string
    {
        return '/api/clingen/people/'.self::PERSON_UUID;
    }
}
