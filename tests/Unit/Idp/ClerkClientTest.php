<?php

namespace Tests\Unit\Idp;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use App\Services\Idp\Clerk\ClerkClient;
use App\Services\Idp\Contracts\IdpClient;
use App\Services\Idp\Exceptions\IdpException;
use App\Services\Idp\Contracts\TokenVerifier;
use App\Services\Idp\Clerk\ClerkTokenVerifier;

class ClerkClientTest extends TestCase
{
    private const API = 'https://api.clerk.test/v1';

    private function client(): ClerkClient
    {
        return new ClerkClient(secret: 'sk_test_secret', baseUrl: self::API);
    }

    private function clerkUser(array $overrides = []): array
    {
        return array_merge([
            'id' => 'user_2abc',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'external_id' => 'person-uuid',
            'primary_email_address_id' => 'idn_2',
            'email_addresses' => [
                ['id' => 'idn_1', 'email_address' => 'Old@Example.com', 'verification' => ['status' => 'verified']],
                ['id' => 'idn_2', 'email_address' => 'jane@example.com', 'verification' => ['status' => 'verified']],
                ['id' => 'idn_3', 'email_address' => 'unverified@example.com', 'verification' => ['status' => 'unverified']],
                ['id' => 'idn_4', 'email_address' => 'never@example.com', 'verification' => null],
            ],
        ], $overrides);
    }

    #[Test]
    public function gets_a_user_and_maps_the_primary_email()
    {
        Http::fake([self::API.'/users/user_2abc' => Http::response($this->clerkUser())]);

        $user = $this->client()->getUser('user_2abc');

        $this->assertSame('jane@example.com', $user->email);
        $this->assertSame('Jane Doe', $user->name());
        $this->assertSame('person-uuid', $user->externalId);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer sk_test_secret'));
    }

    #[Test]
    public function maps_every_verified_address_with_the_primary_first()
    {
        Http::fake([self::API.'/users/user_2abc' => Http::response($this->clerkUser())]);

        $user = $this->client()->getUser('user_2abc');

        $this->assertSame(['jane@example.com', 'old@example.com'], $user->emails);
        $this->assertTrue($user->hasEmail('OLD@example.com'));
        $this->assertFalse($user->hasEmail('unverified@example.com'));
        $this->assertFalse($user->hasEmail('never@example.com'));
    }

    #[Test]
    public function an_unverified_primary_address_is_still_usable()
    {
        Http::fake([self::API.'/users/user_2abc' => Http::response($this->clerkUser([
            'primary_email_address_id' => 'idn_3',
        ]))]);

        $user = $this->client()->getUser('user_2abc');

        $this->assertSame('unverified@example.com', $user->email);
        $this->assertSame(['unverified@example.com', 'old@example.com', 'jane@example.com'], $user->emails);
    }

    #[Test]
    public function returns_null_for_an_unknown_user()
    {
        Http::fake([self::API.'/users/user_nope' => Http::response(['errors' => [['message' => 'not found']]], 404)]);

        $this->assertNull($this->client()->getUser('user_nope'));
    }

    #[Test]
    public function finds_by_email_and_external_id_using_the_list_endpoint()
    {
        Http::fake([self::API.'/users*' => Http::response([$this->clerkUser()])]);

        $this->assertSame('user_2abc', $this->client()->findUserByEmail('jane@example.com')->id);
        $this->assertSame('user_2abc', $this->client()->findUserByExternalId('person-uuid')->id);

        // Repeated keys, not bracketed arrays: Clerk ignores the latter and returns every user.
        Http::assertSent(fn ($request) => str_contains($request->url(), 'email_address=jane%40example.com'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'external_id=person-uuid'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '%5B0%5D'));
    }

    #[Test]
    public function maps_neutral_attributes_to_the_create_user_body()
    {
        Http::fake([self::API.'/users' => Http::response($this->clerkUser())]);

        $this->client()->createUser([
            'email' => 'jane@example.com',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'external_id' => 'person-uuid',
            'password_digest' => '$2y$04$abc',
            'password_hasher' => 'bcrypt',
            'created_at' => '2020-01-01T00:00:00Z',
        ]);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->method() === 'POST'
                && $body['email_address'] === ['jane@example.com']
                && $body['external_id'] === 'person-uuid'
                && $body['password_digest'] === '$2y$04$abc'
                && $body['password_hasher'] === 'bcrypt'
                && $body['skip_password_checks'] === true
                && ! array_key_exists('skip_password_requirement', $body);
        });
    }

    #[Test]
    public function throws_an_idp_exception_with_status_and_error_codes()
    {
        Http::fake([self::API.'/users' => Http::response([
            'errors' => [['code' => 'form_identifier_exists', 'message' => 'taken', 'long_message' => 'That email address is taken.', 'meta' => ['param_name' => 'email_address']]],
        ], 422)]);

        try {
            $this->client()->createUser(['email' => 'jane@example.com']);
            $this->fail('expected IdpException');
        } catch (IdpException $e) {
            $this->assertSame(422, $e->status);
            $this->assertTrue($e->isConflict());
            $this->assertSame('That email address is taken.', $e->getMessage());
            $this->assertSame(['email_address' => 'form_identifier_exists'], $e->errors);
        }
    }

    #[Test]
    public function updates_a_password_without_strength_checks()
    {
        Http::fake([self::API.'/users/user_2abc' => Http::response($this->clerkUser())]);

        $this->client()->updatePassword('user_2abc', 'new-password');

        Http::assertSent(fn ($request) => $request->method() === 'PATCH'
            && $request->data()['password'] === 'new-password'
            && $request->data()['skip_password_checks'] === true);
    }

    #[Test]
    public function the_provider_binds_the_clerk_driver_when_configured()
    {
        config(['idp.driver' => 'clerk', 'idp.clerk.secret_key' => 'sk', 'idp.clerk.frontend_api_url' => 'https://x.clerk.accounts.dev']);
        $this->app->forgetInstance(IdpClient::class);
        $this->app->forgetInstance(TokenVerifier::class);

        $this->assertInstanceOf(ClerkClient::class, app(IdpClient::class));
        $this->assertInstanceOf(ClerkTokenVerifier::class, app(TokenVerifier::class));
    }
}
