<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoOpensPublicHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_dashboard_sidebar_logo_links_to_public_home(): void
    {
        $city = City::factory()->create();
        $user = User::factory()->create([
            'status' => AccountStatus::Active,
            'city_id' => $city->id,
        ]);

        $html = $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $homeUrl = route('home');

        // Brand lockup in the sidebar must target `/`, not `/dashboard`.
        $this->assertMatchesRegularExpression(
            '#dash-sidebar[\s\S]{0,800}<a href="'.preg_quote($homeUrl, '#').'"#',
            $html,
            'Sidebar brand logo must link to the public home route `/`.'
        );
    }

    public function test_authenticated_user_can_open_public_home_without_logout(): void
    {
        $user = User::factory()->create([
            'status' => AccountStatus::Active,
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk();

        $this->assertAuthenticatedAs($user);
    }
}
