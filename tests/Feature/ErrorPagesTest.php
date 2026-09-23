<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_404_page_shows_runner_and_message(): void
    {
        $this->get('/this-page-definitely-does-not-exist-xyz')
            ->assertNotFound()
            ->assertSee('error-runner', false)
            ->assertSee(__('errors.404_title'), false)
            ->assertSee(__('errors.go_home'), false);
    }

    public function test_403_page_uses_shared_error_shell(): void
    {
        $user = User::factory()->active()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden()
            ->assertSee('error-runner', false)
            ->assertSee(__('errors.403_title'), false);
    }
}
