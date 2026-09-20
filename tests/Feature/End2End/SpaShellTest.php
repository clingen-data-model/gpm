<?php

namespace Tests\Feature\End2End;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SpaShellTest extends TestCase
{
    #[Test]
    public function injects_the_idp_driver_and_public_clerk_settings()
    {
        // Js::from() emits JSON.parse('...') with quotes escaped as \u0022.
        $html = $this->get('/login')->assertOk()->getContent();
        $this->assertStringContainsString('window.__GPM_IDP__ = JSON.parse(', $html);
        $this->assertStringContainsString('driver\u0022:\u0022fake\u0022', $html);

        config(['idp.driver' => 'clerk', 'idp.clerk.publishable_key' => 'pk_test_abc', 'idp.clerk.secret_key' => 'sk_test_never']);
        $html = $this->get('/dashboard')->assertOk()->getContent();
        $this->assertStringContainsString('driver\u0022:\u0022clerk\u0022', $html);
        $this->assertStringContainsString('publishableKey\u0022:\u0022pk_test_abc\u0022', $html);
        $this->assertStringNotContainsString('sk_test_never', $html);
    }
}
