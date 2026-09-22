<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectPublicPrefixTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_prefix_home_redirects_to_root(): void
    {
        $this->get('/public/')
            ->assertRedirect('https://fun.idigital-cs.com');
    }

    public function test_public_prefix_events_redirects_to_events(): void
    {
        $this->get('/public/events')
            ->assertRedirect('https://fun.idigital-cs.com/events');
    }

    public function test_clean_home_does_not_redirect(): void
    {
        $this->get('/')
            ->assertOk();
    }
}
