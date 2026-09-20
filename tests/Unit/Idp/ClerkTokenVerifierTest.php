<?php

namespace Tests\Unit\Idp;

use Tests\TestCase;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use App\Services\Idp\Clerk\ClerkTokenVerifier;

class ClerkTokenVerifierTest extends TestCase
{
    private const FRONTEND = 'https://example.clerk.accounts.dev';
    private const KID = 'ins_test_key';

    private string $privateKey;

    public function setup(): void
    {
        parent::setup();

        $resource = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($resource, $pem);
        $this->privateKey = $pem;
        $details = openssl_pkey_get_details($resource);

        $jwks = ['keys' => [[
            'kty' => 'RSA', 'use' => 'sig', 'alg' => 'RS256', 'kid' => self::KID,
            'n' => rtrim(strtr(base64_encode($details['rsa']['n']), '+/', '-_'), '='),
            'e' => rtrim(strtr(base64_encode($details['rsa']['e']), '+/', '-_'), '='),
        ]]];

        Http::fake([self::FRONTEND.'/.well-known/jwks.json' => Http::response($jwks, 200)]);
        Cache::forget(ClerkTokenVerifier::CACHE_KEY);
    }

    private function verifier(array $parties = ['http://gpm.test']): ClerkTokenVerifier
    {
        return new ClerkTokenVerifier(frontendApiUrl: self::FRONTEND, authorizedParties: $parties, clockSkewLeeway: 60);
    }

    private function token(array $overrides = [], ?string $key = null): string
    {
        $claims = array_merge([
            'iss' => self::FRONTEND, 'azp' => 'http://gpm.test', 'sub' => 'user_2abc', 'sid' => 'sess_1',
            'iat' => time(), 'nbf' => time(), 'exp' => time() + 60,
        ], $overrides);

        return JWT::encode($claims, $key ?? $this->privateKey, 'RS256', self::KID);
    }

    #[Test]
    public function verifies_a_token_signed_by_the_instance_key()
    {
        $identity = $this->verifier()->verify($this->token());

        $this->assertNotNull($identity);
        $this->assertSame('user_2abc', $identity->subject);
        $this->assertSame('clerk', $identity->provider);
        $this->assertSame('sess_1', $identity->claim('sid'));
    }

    #[Test]
    public function rejects_a_token_signed_by_another_key()
    {
        $other = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($other, $otherKey);

        $this->assertNull($this->verifier()->verify($this->token(key: $otherKey)));
    }

    #[Test]
    public function rejects_wrong_issuer()
    {
        $this->assertNull($this->verifier()->verify($this->token(['iss' => 'https://other.clerk.accounts.dev'])));
    }

    #[Test]
    public function rejects_missing_or_unlisted_authorized_party_when_a_list_is_configured()
    {
        $this->assertNull($this->verifier()->verify($this->token(['azp' => 'http://evil.test'])));

        $noAzp = $this->token();
        // Re-encode without azp
        $claims = ['iss' => self::FRONTEND, 'sub' => 'user_2abc', 'iat' => time(), 'nbf' => time(), 'exp' => time() + 60];
        $noAzp = JWT::encode($claims, $this->privateKey, 'RS256', self::KID);
        $this->assertNull($this->verifier()->verify($noAzp));

        // Trailing slashes are normalised
        $this->assertNotNull($this->verifier(['http://gpm.test/'])->verify($this->token(['azp' => 'http://gpm.test'])));
    }

    #[Test]
    public function allows_any_party_when_no_list_is_configured_but_still_checks_issuer()
    {
        $this->assertNotNull($this->verifier([])->verify($this->token(['azp' => 'http://anything.test'])));
        $this->assertNull($this->verifier([])->verify($this->token(['iss' => 'https://other'])));
    }

    #[Test]
    public function honours_clock_skew_leeway_but_rejects_clearly_expired_tokens()
    {
        $this->assertNotNull($this->verifier()->verify($this->token(['exp' => time() - 30])));
        $this->assertNull($this->verifier()->verify($this->token(['exp' => time() - 300])));
    }

    #[Test]
    public function caches_the_jwks_document()
    {
        $verifier = $this->verifier();
        $verifier->verify($this->token());
        $verifier->verify($this->token());

        Http::assertSentCount(1);
    }

    #[Test]
    public function leaves_the_global_leeway_untouched()
    {
        $before = JWT::$leeway;
        $this->verifier()->verify($this->token());

        $this->assertSame($before, JWT::$leeway);
    }
}
