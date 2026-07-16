<?php

namespace Tests\Unit\Clerk;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Services\Clerk\ImpersonationTokenService;

class ImpersonationTokenServiceTest extends TestCase
{
    #[Test]
    public function it_round_trips_an_impersonation_token()
    {
        $service = new ImpersonationTokenService($this->secret('a'), 1800);

        $payload = $service->verify($service->issue(10, 20));

        $this->assertSame(10, $payload['imp_by']);
        $this->assertSame(20, $payload['sub']);
    }

    #[Test]
    public function it_rejects_a_token_signed_with_a_different_secret()
    {
        $issuer = new ImpersonationTokenService($this->secret('a'));
        $verifier = new ImpersonationTokenService($this->secret('b'));

        $this->assertNull($verifier->verify($issuer->issue(1, 2)));
    }

    #[Test]
    public function it_rejects_a_tampered_token()
    {
        $service = new ImpersonationTokenService($this->secret('a'));

        $this->assertNull($service->verify($service->issue(1, 2).'tampered'));
    }

    #[Test]
    public function it_rejects_an_expired_token()
    {
        // Negative TTL produces an already-expired token.
        $service = new ImpersonationTokenService($this->secret('a'), -10);

        $this->assertNull($service->verify($service->issue(1, 2)));
    }

    /**
     * A secret shaped like the one fromConfig() derives: HS256 requires at
     * least 256 bits of key material.
     */
    private function secret(string $seed): string
    {
        return hash('sha256', $seed);
    }
}
