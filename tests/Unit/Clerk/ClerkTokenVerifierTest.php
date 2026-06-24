<?php

namespace Tests\Unit\Clerk;

use Tests\TestCase;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use App\Services\Clerk\ClerkTokenVerifier;

class ClerkTokenVerifierTest extends TestCase
{
    private const ISSUER = 'https://clerk.example.test';

    private const JWKS_URL = self::ISSUER.'/.well-known/jwks.json';

    private const ORIGIN = 'https://app.example.test';

    private const KID = 'test-key';

    // Keypair generation is slow; one pair serves the whole class.
    private static ?string $privateKey = null;

    private static ?string $otherPrivateKey = null;

    private static ?array $jwks = null;

    public function setUp(): void
    {
        parent::setUp();

        if (self::$privateKey === null) {
            [self::$privateKey, self::$jwks] = $this->generateKeypair(self::KID);
            [self::$otherPrivateKey] = $this->generateKeypair('other-key');
        }

        Cache::flush();
        Http::fake([self::JWKS_URL => Http::response(self::$jwks)]);
    }

    #[Test]
    public function it_accepts_a_valid_token()
    {
        $claims = $this->verifier()->verify($this->token());

        $this->assertSame('user_123', $claims['sub']);
    }

    #[Test]
    public function it_rejects_a_token_issued_by_another_clerk_instance()
    {
        // Signed by the same JWKS, but minted for a different instance.
        $token = $this->token(['iss' => 'https://clerk.attacker.test']);

        $this->assertNull($this->verifier()->verify($token));
    }

    #[Test]
    public function it_rejects_a_token_with_no_issuer()
    {
        $this->assertNull($this->verifier()->verify($this->token(['iss' => null])));
    }

    #[Test]
    public function it_ignores_a_trailing_slash_when_comparing_the_issuer()
    {
        $verifier = $this->verifier(frontendApiUrl: self::ISSUER.'/');

        $this->assertNotNull($verifier->verify($this->token()));
    }

    #[Test]
    public function it_rejects_every_token_when_no_frontend_api_url_is_configured()
    {
        $verifier = $this->verifier(frontendApiUrl: null);

        $this->assertNull($verifier->verify($this->token()));
    }

    #[Test]
    public function it_rejects_a_token_whose_azp_is_not_in_the_allow_list()
    {
        $token = $this->token(['azp' => 'https://evil.example.test']);

        $this->assertNull($this->verifier()->verify($token));
    }

    #[Test]
    public function it_rejects_a_token_with_no_azp_when_an_allow_list_is_configured()
    {
        $this->assertNull($this->verifier()->verify($this->token(['azp' => null])));
    }

    #[Test]
    public function it_accepts_a_token_with_no_azp_when_no_allow_list_is_configured()
    {
        $verifier = $this->verifier(authorizedParties: []);

        $this->assertNotNull($verifier->verify($this->token(['azp' => null])));
    }

    #[Test]
    public function it_rejects_a_token_signed_by_an_unknown_key()
    {
        $token = JWT::encode($this->claims(), self::$otherPrivateKey, 'RS256', 'other-key');

        $this->assertNull($this->verifier()->verify($token));
    }

    #[Test]
    public function it_rejects_a_token_that_expired_beyond_the_leeway()
    {
        $token = $this->token(['exp' => time() - 120]);

        $this->assertNull($this->verifier(leeway: 60)->verify($token));
    }

    #[Test]
    public function it_accepts_a_token_that_expired_within_the_leeway()
    {
        $token = $this->token(['exp' => time() - 30]);

        $this->assertNotNull($this->verifier(leeway: 60)->verify($token));
    }

    #[Test]
    public function it_accepts_a_token_not_yet_valid_within_the_leeway()
    {
        // Host clock running behind Clerk's.
        $token = $this->token(['nbf' => time() + 30, 'iat' => time() + 30]);

        $this->assertNotNull($this->verifier(leeway: 60)->verify($token));
    }

    #[Test]
    public function it_does_not_leak_its_clock_skew_leeway_to_other_tokens()
    {
        JWT::$leeway = 0;

        $this->verifier(leeway: 60)->verify($this->token());

        $this->assertSame(0, JWT::$leeway);
    }

    #[Test]
    public function it_caches_the_jwks_across_verifications()
    {
        $verifier = $this->verifier();

        $verifier->verify($this->token());
        $verifier->verify($this->token());

        Http::assertSentCount(1);
    }

    private function verifier(
        array $authorizedParties = [self::ORIGIN],
        ?string $frontendApiUrl = self::ISSUER,
        int $leeway = 60,
    ): ClerkTokenVerifier {
        return new ClerkTokenVerifier(
            frontendApiUrl: $frontendApiUrl,
            authorizedParties: $authorizedParties,
            jwksCacheTtl: 3600,
            clockSkewLeeway: $leeway,
        );
    }

    private function token(array $overrides = []): string
    {
        return JWT::encode($this->claims($overrides), self::$privateKey, 'RS256', self::KID);
    }

    /**
     * Default claims of a Clerk session token; a null override drops the claim.
     */
    private function claims(array $overrides = []): array
    {
        return array_filter(
            array_merge([
                'sub' => 'user_123',
                'iss' => self::ISSUER,
                'azp' => self::ORIGIN,
                'iat' => time(),
                'nbf' => time(),
                'exp' => time() + 3600,
            ], $overrides),
            fn ($value) => $value !== null
        );
    }

    /**
     * @return array{0: string, 1: array} the PEM private key and its JWKS document
     */
    private function generateKeypair(string $kid): array
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($resource, $privateKey);
        $details = openssl_pkey_get_details($resource);

        return [$privateKey, ['keys' => [[
            'kty' => 'RSA',
            'kid' => $kid,
            'use' => 'sig',
            'alg' => 'RS256',
            'n' => $this->base64url($details['rsa']['n']),
            'e' => $this->base64url($details['rsa']['e']),
        ]]]];
    }

    private function base64url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
