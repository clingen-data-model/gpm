<?php

namespace Tests\Unit\Idp;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Services\Idp\Fake\FakeIdpStore;
use App\Services\Idp\Fake\FakeIdpClient;
use App\Services\Idp\Contracts\IdpClient;
use App\Services\Idp\Exceptions\IdpException;

class FakeIdpClientTest extends TestCase
{
    private FakeIdpClient $client;

    public function setup(): void
    {
        parent::setup();
        $this->client = new FakeIdpClient(new FakeIdpStore(null));
    }

    #[Test]
    public function creates_and_finds_users()
    {
        $user = $this->client->createUser([
            'email' => 'Jane@Example.com',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'external_id' => 'person-uuid-1',
            'password' => 'secret-password',
        ]);

        $this->assertStringStartsWith('user_fake_', $user->id);
        $this->assertSame('Jane Doe', $user->name());
        $this->assertSame($user->id, $this->client->getUser($user->id)->id);
        $this->assertSame($user->id, $this->client->findUserByEmail('jane@example.com')->id);
        $this->assertSame($user->id, $this->client->findUserByExternalId('person-uuid-1')->id);
        $this->assertNull($this->client->findUserByEmail('nobody@example.com'));
        $this->assertTrue($this->client->passwordMatches($user->id, 'secret-password'));
        $this->assertFalse($this->client->passwordMatches($user->id, 'wrong'));
    }

    #[Test]
    public function finds_users_by_a_secondary_address_and_lists_every_address()
    {
        $this->client->store()->put([
            'id' => 'user_fake_multi',
            'email' => 'Primary@Example.com',
            'emails' => ['second@example.com', 'primary@example.com'],
            'first_name' => 'Multi',
            'last_name' => 'Mail',
        ]);

        $user = $this->client->findUserByEmail('SECOND@example.com');

        $this->assertSame('user_fake_multi', $user->id);
        $this->assertSame('Primary@Example.com', $user->email);
        $this->assertSame(['primary@example.com', 'second@example.com'], $user->emails);
        $this->assertTrue($user->hasEmail('second@example.com'));
        $this->assertFalse($user->hasEmail('third@example.com'));

        $this->expectException(IdpException::class);
        $this->client->createUser(['email' => 'second@example.com']);
    }

    #[Test]
    public function searches_names_addresses_and_ids_by_substring()
    {
        $store = $this->client->store();
        $store->put(['id' => 'user_fake_jane', 'email' => 'jane@example.com', 'emails' => ['jd@other.org'], 'first_name' => 'Jane', 'last_name' => 'Doe']);
        $store->put(['id' => 'user_fake_john', 'email' => 'john@example.com', 'first_name' => 'John', 'last_name' => 'Roe']);
        $store->put(['id' => 'user_fake_zed', 'email' => 'zed@example.com', 'first_name' => 'Zed', 'last_name' => 'Zee']);

        $ids = fn (array $users) => array_map(fn ($u) => $u->id, $users);

        $this->assertSame(['user_fake_jane', 'user_fake_john'], $ids($this->client->searchUsers('J')));
        $this->assertSame(['user_fake_jane'], $ids($this->client->searchUsers('ane do')));
        $this->assertSame(['user_fake_jane'], $ids($this->client->searchUsers('OTHER.org')));
        $this->assertSame(['user_fake_zed'], $ids($this->client->searchUsers('fake_zed')));
        $this->assertSame(['user_fake_jane'], $ids($this->client->searchUsers('example', 1)));
        $this->assertSame([], $this->client->searchUsers('   '));
        $calls = $this->client->calls();
        $this->assertSame('searchUsers', end($calls)['method']);

        $this->client->failNext();
        $this->expectException(IdpException::class);
        $this->client->searchUsers('jane');
    }

    #[Test]
    public function accepts_a_bcrypt_digest_instead_of_a_password()
    {
        $digest = password_hash('imported', PASSWORD_BCRYPT, ['cost' => 4]);
        $user = $this->client->createUser(['email' => 'a@example.com', 'password_digest' => $digest, 'password_hasher' => 'bcrypt']);

        $this->assertTrue($this->client->passwordMatches($user->id, 'imported'));
    }

    #[Test]
    public function rejects_duplicate_email_and_external_id()
    {
        $this->client->createUser(['email' => 'a@example.com', 'external_id' => 'x']);

        try {
            $this->client->createUser(['email' => 'A@example.com']);
            $this->fail('expected duplicate email to be rejected');
        } catch (IdpException $e) {
            $this->assertTrue($e->isConflict());
        }

        $this->expectException(IdpException::class);
        $this->client->createUser(['email' => 'b@example.com', 'external_id' => 'x']);
    }

    #[Test]
    public function updates_attributes_and_password()
    {
        $user = $this->client->createUser(['email' => 'a@example.com', 'password' => 'one']);

        $updated = $this->client->updateUser($user->id, ['first_name' => 'Ann', 'external_id' => 'ext-1']);
        $this->client->updatePassword($user->id, 'two');

        $this->assertSame('Ann', $updated->firstName);
        $this->assertSame('ext-1', $this->client->getUser($user->id)->externalId);
        $this->assertTrue($this->client->passwordMatches($user->id, 'two'));
    }

    #[Test]
    public function can_be_told_to_fail_the_next_call()
    {
        $this->client->failNext();

        $this->expectException(IdpException::class);
        $this->client->findUserByEmail('a@example.com');
    }

    #[Test]
    public function persists_to_a_json_file_when_a_path_is_configured()
    {
        $path = sys_get_temp_dir().'/fake-idp-'.uniqid().'/users.json';
        $client = new FakeIdpClient(new FakeIdpStore($path));
        $user = $client->createUser(['email' => 'a@example.com']);

        $reloaded = new FakeIdpClient(new FakeIdpStore($path));
        $this->assertSame($user->id, $reloaded->findUserByEmail('a@example.com')->id);

        unlink($path);
        rmdir(dirname($path));
    }

    #[Test]
    public function the_container_resolves_the_fake_client_in_tests()
    {
        $this->assertInstanceOf(FakeIdpClient::class, app(IdpClient::class));
        $this->assertSame(app(IdpClient::class), app(FakeIdpClient::class));
    }
}
