<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Admin\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SiteBrandingApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(SiteSettingsService::CACHE_KEY);
        Cache::forget(SiteSettingsService::LOGO_CACHE_KEY);
        Cache::forget(SiteSettingsService::APP_CACHE_KEY);
    }

    public function test_public_site_endpoint_includes_app_version_payload(): void
    {
        $settings = app(SiteSettingsService::class)->current();
        $settings->forceFill([
            'display_name' => 'Fun Test',
            'android_latest_version' => '2.1.0',
            'android_min_version' => '2.0.0',
            'ios_latest_version' => '2.1.0',
            'ios_min_version' => '1.9.0',
            'force_update' => true,
            'android_store_url' => 'https://play.google.com/store/apps/details?id=com.example.fun',
            'ios_store_url' => 'https://apps.apple.com/app/id123',
            'update_message_en' => 'Please update',
            'update_message_ar' => 'يرجى التحديث',
        ])->save();

        Cache::forget(SiteSettingsService::CACHE_KEY);
        Cache::forget(SiteSettingsService::APP_CACHE_KEY);

        $this->getJson('/api/site?locale=en')
            ->assertOk()
            ->assertJsonPath('display_name', 'Fun Test')
            ->assertJsonStructure(['logo_url', 'app', 'broadcast'])
            ->assertJsonStructure([
                'broadcast' => ['enabled', 'driver', 'key', 'cluster', 'host', 'port', 'scheme'],
            ])
            ->assertJsonPath('app.force_update', true)
            ->assertJsonPath('app.message', 'Please update')
            ->assertJsonPath('app.android.latest', '2.1.0')
            ->assertJsonPath('app.android.min', '2.0.0')
            ->assertJsonPath('app.android.store_url', 'https://play.google.com/store/apps/details?id=com.example.fun')
            ->assertJsonPath('app.ios.latest', '2.1.0')
            ->assertJsonPath('app.ios.min', '1.9.0');

        Cache::forget(SiteSettingsService::APP_CACHE_KEY);

        $this->getJson('/api/site?locale=ar')
            ->assertOk()
            ->assertJsonPath('app.message', 'يرجى التحديث');
    }

    public function test_broadcast_payload_reports_pusher_when_configured(): void
    {
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => 'test-pusher-key',
            'broadcasting.connections.pusher.options.cluster' => 'eu',
        ]);

        $this->getJson('/api/site')
            ->assertOk()
            ->assertJsonPath('broadcast.enabled', true)
            ->assertJsonPath('broadcast.driver', 'pusher')
            ->assertJsonPath('broadcast.key', 'test-pusher-key')
            ->assertJsonPath('broadcast.cluster', 'eu')
            ->assertJsonPath('broadcast.host', null);
    }

    public function test_broadcast_payload_reports_reverb_when_configured(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-reverb-key',
            'broadcasting.connections.reverb.options.host' => 'ws.example.test',
            'broadcasting.connections.reverb.options.port' => 443,
            'broadcasting.connections.reverb.options.scheme' => 'https',
        ]);

        $this->getJson('/api/site')
            ->assertOk()
            ->assertJsonPath('broadcast.enabled', true)
            ->assertJsonPath('broadcast.driver', 'reverb')
            ->assertJsonPath('broadcast.key', 'test-reverb-key')
            ->assertJsonPath('broadcast.cluster', null)
            ->assertJsonPath('broadcast.host', 'ws.example.test')
            ->assertJsonPath('broadcast.port', 443)
            ->assertJsonPath('broadcast.scheme', 'https');
    }

    public function test_super_admin_can_update_app_version_settings(): void
    {
        $admin = User::factory()->superAdmin()->create();
        Sanctum::actingAs($admin);

        $this->putJson('/api/admin/settings', [
            'display_name' => 'Fun',
            'android_latest_version' => '3.0.0',
            'android_min_version' => '2.5.0',
            'ios_latest_version' => '3.0.0',
            'ios_min_version' => '2.5.0',
            'force_update' => true,
            'android_store_url' => 'https://play.google.com/store/apps/details?id=com.example.fun',
            'update_message_en' => 'Update required',
        ])
            ->assertOk()
            ->assertJsonPath('data.android_latest_version', '3.0.0')
            ->assertJsonPath('data.android_min_version', '2.5.0')
            ->assertJsonPath('data.force_update', true)
            ->assertJsonPath('data.update_message_en', 'Update required');

        Cache::forget(SiteSettingsService::APP_CACHE_KEY);

        $this->getJson('/api/site')
            ->assertOk()
            ->assertJsonPath('app.force_update', true)
            ->assertJsonPath('app.android.latest', '3.0.0')
            ->assertJsonPath('app.android.min', '2.5.0');
    }

    public function test_web_admin_settings_form_shows_version_fields(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('name="android_latest_version"', false)
            ->assertSee('name="force_update"', false)
            ->assertSee(__('admin.app_version_section'), false);
    }
}
