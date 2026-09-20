<?php

namespace Tests\Unit\Idp;

use Tests\TestCase;
use Firebase\JWT\JWT;
use PHPUnit\Framework\Attributes\Test;
use App\Services\Idp\Fake\FakeTokenIssuer;
use App\Services\Idp\Fake\FakeTokenVerifier;

class FakeTokenVerifierTest extends TestCase
{
    private FakeTokenIssuer $issuer;
    private FakeTokenVerifier $verifier;

    public function setup(): void
    {
        parent::setup();
        $this->issuer = new FakeTokenIssuer(key: 'secret-key', authorizedParty: 'http://gpm.test', ttl: 60);
        $this->verifier = new FakeTokenVerifier(key: 'secret-key', authorizedParty: 'http://gpm.test', provider: 'clerk');
    }

    #[Test]
    public function round_trips_a_token()
    {
        $token = $this->issuer->issue('user_fake_abc', ['email' => 'jane@example.com']);

        $identity = $this->verifier->verify($token);

        $this->assertNotNull($identity);
        $this->assertSame('clerk', $identity->provider);
        $this->assertSame('user_fake_abc', $identity->subject);
        $this->assertSame('jane@example.com', $identity->email());
        $this->assertSame(FakeTokenIssuer::ISSUER, $identity->claim('iss'));
    }

    #[Test]
    public function rejects_garbage_and_wrong_key()
    {
        $this->assertNull($this->verifier->verify('not-a-jwt'));

        $other = new FakeTokenIssuer(key: 'other-key', authorizedParty: 'http://gpm.test');
        $this->assertNull($this->verifier->verify($other->issue('user_fake_abc')));
    }

    #[Test]
    public function rejects_expired_tokens()
    {
        $token = $this->issuer->issue('user_fake_abc', ttl: -120);

        $this->assertNull($this->verifier->verify($token));
    }

    #[Test]
    public function rejects_wrong_issuer_or_authorized_party()
    {
        $wrongIssuer = JWT::encode([
            'iss' => 'someone-else', 'azp' => 'http://gpm.test', 'sub' => 'user_fake_abc',
            'iat' => time(), 'nbf' => time(), 'exp' => time() + 60,
        ], FakeTokenIssuer::normaliseKey('secret-key'), 'HS256');
        $this->assertNull($this->verifier->verify($wrongIssuer));

        $wrongParty = new FakeTokenIssuer(key: 'secret-key', authorizedParty: 'http://evil.test');
        $this->assertNull($this->verifier->verify($wrongParty->issue('user_fake_abc')));
    }

    #[Test]
    public function rejects_tokens_without_a_subject()
    {
        $token = JWT::encode([
            'iss' => FakeTokenIssuer::ISSUER, 'azp' => 'http://gpm.test',
            'iat' => time(), 'nbf' => time(), 'exp' => time() + 60,
        ], FakeTokenIssuer::normaliseKey('secret-key'), 'HS256');

        $this->assertNull($this->verifier->verify($token));
    }

    #[Test]
    public function container_bindings_use_the_fake_driver_in_tests()
    {
        $issuer = app(FakeTokenIssuer::class);
        $verifier = app(\App\Services\Idp\Contracts\TokenVerifier::class);

        $this->assertInstanceOf(FakeTokenVerifier::class, $verifier);
        $this->assertSame('user_fake_1', $verifier->verify($issuer->issue('user_fake_1'))->subject);
    }
}
