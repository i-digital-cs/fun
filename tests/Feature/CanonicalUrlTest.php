<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceCanonicalUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CanonicalUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://funnity.com']);
        URL::forceRootUrl('https://funnity.com');
        URL::forceScheme('https');
    }

    public function test_public_prefix_redirects_to_canonical_origin(): void
    {
        $this->get('/public/')
            ->assertRedirect('https://funnity.com/');

        $this->get('/public/events?q=cairo')
            ->assertRedirect('https://funnity.com/events?q=cairo');
    }

    public function test_http_and_www_redirect_to_apex_https_in_one_hop(): void
    {
        $this->get('http://funnity.com/events?q=cairo')
            ->assertStatus(301)
            ->assertRedirect('https://funnity.com/events?q=cairo');

        $this->get('http://www.funnity.com/safety')
            ->assertStatus(301)
            ->assertRedirect('https://funnity.com/safety');

        $this->get('https://www.funnity.com/privacy-policy')
            ->assertStatus(301)
            ->assertRedirect('https://funnity.com/privacy-policy');
    }

    public function test_trailing_slash_and_www_collapse_to_one_canonical_url(): void
    {
        $request = Request::create('http://www.funnity.com/events/?q=cairo', 'GET');

        $response = app(EnforceCanonicalUrl::class)->handle(
            $request,
            fn () => response('should-not-run', 500),
        );

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('https://funnity.com/events?q=cairo', $response->headers->get('Location'));
    }

    public function test_canonical_https_apex_trailing_slash_drops_in_one_hop(): void
    {
        $request = Request::create('https://funnity.com/events/', 'GET');

        $response = app(EnforceCanonicalUrl::class)->handle(
            $request,
            fn () => response('should-not-run', 500),
        );

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('https://funnity.com/events', $response->headers->get('Location'));
    }

    public function test_post_on_a_non_canonical_host_is_not_redirected(): void
    {
        $request = Request::create('http://www.funnity.com/locale', 'POST');

        $response = app(EnforceCanonicalUrl::class)->handle(
            $request,
            fn () => response('next', 204),
        );

        $this->assertSame(204, $response->getStatusCode());
    }

    public function test_canonical_home_is_not_redirected(): void
    {
        $this->get('https://funnity.com/')
            ->assertOk();
    }
}
