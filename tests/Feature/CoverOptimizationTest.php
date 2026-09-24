<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\User;
use App\Services\Media\PublicMediaUploader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CoverOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_cover_url_prefers_webp_variant(): void
    {
        $url = event_cover_url(null, 1, 'football', 'football match', 'card');

        $this->assertStringContainsString('football-640.webp', $url);
    }

    public function test_event_cover_attrs_include_srcset_for_stock(): void
    {
        $attrs = event_cover_attrs(null, null, 1, 'football', 'football', 'card');

        $this->assertStringContainsString('football-640.webp', $attrs['src']);
        $this->assertNotNull($attrs['srcset']);
        $this->assertStringContainsString('320w', $attrs['srcset']);
        $this->assertStringContainsString('1280w', $attrs['srcset']);
        $this->assertSame(640, $attrs['width']);
    }

    public function test_event_show_cover_uses_fetchpriority_and_dimensions(): void
    {
        $event = $this->makePublishedEvent(['title' => 'Cover Priority Event']);

        $this->get(event_show_url($event))
            ->assertOk()
            ->assertSee('fetchpriority="high"', false)
            ->assertSee('decoding="async"', false)
            ->assertSee('width="1200"', false)
            ->assertSee('Cover Priority Event', false);
    }

    public function test_uploader_stores_webp_detail_and_card_variants(): void
    {
        Storage::fake(config('media.disk', 'media'));

        $png = imagecreatetruecolor(800, 600);
        $white = imagecolorallocate($png, 255, 255, 255);
        imagefill($png, 0, 0, $white);
        ob_start();
        imagepng($png);
        $blob = ob_get_clean();
        imagedestroy($png);

        $tmp = tempnam(sys_get_temp_dir(), 'cover');
        file_put_contents($tmp, $blob);

        $file = new UploadedFile($tmp, 'cover.png', 'image/png', null, true);
        $stored = app(PublicMediaUploader::class)->storeEventImage($file, 99);

        $this->assertStringEndsWith('.webp', $stored['path']);
        Storage::disk($stored['disk'])->assertExists($stored['path']);

        $cardPath = event_image_card_path($stored['path']);
        $this->assertNotNull($cardPath);
        Storage::disk($stored['disk'])->assertExists($cardPath);

        @unlink($tmp);
    }

    public function test_guest_home_does_not_inline_echo_in_main_bundle_marker(): void
    {
        // Smoke: guest home still renders; Echo is deferred via dynamic import in app.js.
        Category::factory()->create(['is_active' => true]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('data-realtime="1"', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makePublishedEvent(array $overrides = []): Event
    {
        $host = User::factory()->active()->create();
        $city = City::factory()->create();
        $category = Category::factory()->create(['slug' => 'football', 'name_en' => 'Football']);

        return Event::factory()->published()->create(array_merge([
            'created_by' => $host->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'status' => EventStatus::Published,
        ], $overrides));
    }
}
