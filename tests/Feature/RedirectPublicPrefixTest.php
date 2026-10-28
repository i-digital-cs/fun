<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RedirectPublicPrefixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://funnity.com']);
        URL::forceRootUrl('https://funnity.com');
        URL::forceScheme('https');
    }

    public function test_public_prefix_home_redirects_to_root(): void
    {
        $this->get('/public/')
            ->assertRedirect('https://funnity.com/');
    }

    public function test_public_prefix_events_redirects_to_events(): void
    {
        $this->get('/public/events')
            ->assertRedirect('https://funnity.com/events');
    }

    public function test_clean_home_does_not_redirect(): void
    {
        $this->get('/')
            ->assertOk();
    }
}
