<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\EventMemberRole;
use App\Enums\EventStatus;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Enums\ReportableType;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Renders authenticated dashboard HTML into docs/admin/dashboard-v2-1-qa for visual QA.
 * Uses RefreshDatabase + actingAs (not a production auth bypass).
 * Injects built Vite assets because TestCase disables Vite.
 */
class AdminDashboardV21QaRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_dump_dashboard_html_for_visual_qa(): void
    {
        $admin = User::factory()->superAdmin()->create(['name' => 'QA Admin']);
        $city = City::factory()->create(['name_en' => 'Cairo', 'name_ar' => 'القاهرة']);
        $category = Category::factory()->create([
            'is_active' => true,
            'name_en' => 'Sports',
            'name_ar' => 'رياضة',
        ]);
        $host = User::factory()->active()->create(['name' => 'Host User']);
        $event = Event::factory()->published()->create([
            'created_by' => $host->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'title' => 'V21 QA Event',
            'created_at' => now()->subDay(),
        ]);
        Event::factory()->create([
            'created_by' => $host->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'status' => EventStatus::Draft,
            'title' => 'Draft Sample',
            'created_at' => now()->subDay(),
        ]);
        $joiner = User::factory()->active()->create();
        EventMember::query()->create([
            'event_id' => $event->id,
            'user_id' => $joiner->id,
            'role' => EventMemberRole::Member,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
        User::factory()->create(['status' => AccountStatus::PendingDocuments]);
        Report::query()->create([
            'reporter_id' => $joiner->id,
            'reportable_type' => ReportableType::User->value,
            'reportable_id' => $host->id,
            'reason' => ReportReason::Spam,
            'description' => 'qa',
            'status' => ReportStatus::Pending,
        ]);

        Cache::flush();
        $dir = base_path('docs/admin/dashboard-v2-1-qa');
        File::ensureDirectoryExists($dir);

        $en = $this->actingAs($admin)->get(route('admin.dashboard', ['range' => '7d']));
        $en->assertOk();
        $enHtml = $this->withBuiltAssets($en->getContent());
        File::put($dir.'/render-en.html', $enHtml);

        $admin->forceFill(['locale' => 'ar'])->save();
        $ar = $this->actingAs($admin->fresh())
            ->withSession(['locale' => 'ar'])
            ->get(route('admin.dashboard', ['range' => '7d']));
        $ar->assertOk();
        $arHtml = $this->withBuiltAssets($ar->getContent());
        $this->assertStringContainsString('dir="rtl"', $arHtml);
        $this->assertStringContainsString('مركز القيادة', $arHtml);
        File::put($dir.'/render-ar.html', $arHtml);

        $this->assertStringContainsString('admin-dash-toolbar', $enHtml);
        $this->assertStringContainsString('action-center', $enHtml);
        $this->assertStringContainsString('/build/assets/', $enHtml);
        $this->assertFileExists($dir.'/render-en.html');
        $this->assertFileExists($dir.'/render-ar.html');
    }

    private function withBuiltAssets(string $html): string
    {
        $manifestPath = public_path('build/manifest.json');
        if (! is_file($manifestPath)) {
            return $html;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        $css = $manifest['resources/css/app.css']['file'] ?? null;
        $js = $manifest['resources/js/app.js']['file'] ?? null;
        if (! $css) {
            return $html;
        }

        $inject = '    <link rel="stylesheet" href="/build/'.$css.'">'."\n";
        if ($js) {
            $inject .= '    <script type="module" src="/build/'.$js.'"></script>'."\n";
        }

        return str_replace('</head>', $inject.'</head>', $html);
    }
}
