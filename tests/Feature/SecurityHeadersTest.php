<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_pages_send_baseline_security_headers(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');

        $permissions = (string) $response->headers->get('Permissions-Policy');
        $this->assertStringContainsString('geolocation=(self)', $permissions);
        $this->assertStringContainsString('camera=()', $permissions);

        $csp = (string) $response->headers->get('Content-Security-Policy-Report-Only');
        $this->assertNotSame('', $csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString('challenges.cloudflare.com', $csp);
        $this->assertStringContainsString('object-src \'none\'', $csp);

        $this->assertNull($response->headers->get('Content-Security-Policy'));
        $this->assertNull($response->headers->get('Strict-Transport-Security'));
    }

    public function test_hsts_only_when_enabled_and_https(): void
    {
        config([
            'security.hsts.enabled' => true,
            'security.hsts.max_age' => 60,
            'security.hsts.include_subdomains' => false,
            'security.hsts.preload' => false,
        ]);

        $response = $this->get('https://localhost/');

        $response->assertHeader('Strict-Transport-Security', 'max-age=60');
    }

    public function test_api_json_is_not_wrapped_with_html_csp(): void
    {
        $response = $this->getJson('/api/events');

        $response->assertOk();
        $this->assertNull($response->headers->get('Content-Security-Policy-Report-Only'));
    }
}
