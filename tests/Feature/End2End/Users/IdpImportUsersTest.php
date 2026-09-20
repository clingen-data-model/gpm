<?php

namespace Tests\Feature\End2End\Users;

use Tests\TestCase;
use App\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use App\Services\Idp\Fake\FakeIdpClient;
use App\Services\Idp\Exceptions\IdpException;

class IdpImportUsersTest extends TestCase
{
    private FakeIdpClient $client;

    public function setup(): void
    {
        parent::setup();
        $this->client = app(FakeIdpClient::class);
        // Users created through the factory are not mirrored; only UserCreate does that.
    }

    #[Test]
    public function requires_a_selection()
    {
        $this->artisan('idp:import-users')->assertFailed();
        $this->artisan('idp:import-users', ['users' => ['1'], '--all' => true])->assertFailed();
    }

    #[Test]
    public function dry_run_writes_nothing()
    {
        $user = $this->setupUserWithPerson();

        $this->artisan('idp:import-users', ['--all' => true, '--dry-run' => true, '--throttle' => 0])
            ->expectsOutputToContain('creating identity with password digest')
            ->assertSuccessful();

        $this->assertNull($user->fresh()->idp_id);
        $this->assertCount(0, $this->client->store()->all());
    }

    #[Test]
    public function creates_identities_with_the_password_digest_and_person_uuid()
    {
        $user = $this->setupUserWithPerson();

        $this->artisan('idp:import-users', ['--all' => true, '--throttle' => 0])
            ->expectsOutputToContain('Created: 1, Linked: 0, Skipped: 0, Errors: 0')
            ->assertSuccessful();

        $user->refresh();
        $this->assertSame('clerk', $user->idp_provider);
        $idpUser = $this->client->getUser($user->idp_id);
        $this->assertSame($user->person->uuid, $idpUser->externalId);
        $this->assertSame($user->email, $idpUser->email);
        $this->assertSame($user->person->first_name, $idpUser->firstName);
        // Factory users have the password "password"; the digest was imported, not re-hashed.
        $this->assertTrue($this->client->passwordMatches($user->idp_id, 'password'));
    }

    #[Test]
    public function links_existing_identities_by_external_id_then_email_and_backfills_external_id()
    {
        $byUuid = $this->setupUserWithPerson();
        $byEmail = $this->setupUserWithPerson();
        $existingUuid = $this->client->createUser(['email' => 'other@example.com', 'external_id' => $byUuid->person->uuid]);
        $existingEmail = $this->client->createUser(['email' => $byEmail->email]);

        $this->artisan('idp:import-users', ['--all' => true, '--throttle' => 0])
            ->expectsOutputToContain('Created: 0, Linked: 2, Skipped: 0, Errors: 0')
            ->assertSuccessful();

        $this->assertSame($existingUuid->id, $byUuid->fresh()->idp_id);
        $this->assertSame($existingEmail->id, $byEmail->fresh()->idp_id);
        $this->assertSame($byEmail->person->uuid, $this->client->getUser($existingEmail->id)->externalId);
    }

    #[Test]
    public function is_idempotent_and_accepts_ids_or_emails()
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $c = User::factory()->create();

        $this->artisan('idp:import-users', ['users' => [(string) $a->id, $b->email], '--throttle' => 0])->assertSuccessful();
        $this->assertNotNull($a->fresh()->idp_id);
        $this->assertNotNull($b->fresh()->idp_id);
        $this->assertNull($c->fresh()->idp_id);

        $this->artisan('idp:import-users', ['users' => [(string) $a->id], '--throttle' => 0])
            ->expectsOutputToContain('Skipped: 1')
            ->assertSuccessful();
        $this->assertCount(2, $this->client->store()->all());
    }

    #[Test]
    public function reports_errors_and_continues()
    {
        $first = User::factory()->create(['email' => 'first@example.com']);
        $second = User::factory()->create(['email' => 'second@example.com']);
        $this->client->failNext(new IdpException('boom', 500));

        $this->artisan('idp:import-users', ['--all' => true, '--throttle' => 0])
            ->expectsOutputToContain('Created: 1, Linked: 0, Skipped: 0, Errors: 1')
            ->assertFailed();

        $this->assertNull($first->fresh()->idp_id);
        $this->assertNotNull($second->fresh()->idp_id);
    }

    #[Test]
    public function refuses_to_run_without_an_idp()
    {
        config(['idp.driver' => 'null']);

        $this->artisan('idp:import-users', ['--all' => true])->assertFailed();
    }
}
