<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class SeoHealthCommand extends Command
{
    protected $signature = 'seo:health
                            {--base= : Override APP_URL for live HTTP checks}
                            {--skip-http : Skip live HTTP probes (DB/filesystem only)}';

    protected $description = 'Run technical SEO health checks (robots, sitemap, covers, content gaps)';

    public function handle(): int
    {
        $base = rtrim((string) ($this->option('base') ?: config('app.url')), '/');
        $ok = true;

        $this->info('SEO health — '.$base);
        $this->newLine();

        $ok = $this->checkStockWebp() && $ok;
        $ok = $this->checkPublishedContent() && $ok;

        if (! $this->option('skip-http')) {
            $ok = $this->checkHttp($base.'/robots.txt', 'robots.txt', [
                'Disallow: /admin',
                'Sitemap:',
            ]) && $ok;

            $ok = $this->checkHttp($base.'/sitemap.xml', 'sitemap.xml', [
                '<?xml',
                '<urlset',
                route('home'),
            ]) && $ok;

            $sample = Event::query()
                ->where('status', EventStatus::Published)
                ->whereNotNull('slug')
                ->latest('id')
                ->first();

            if ($sample) {
                $ok = $this->checkHttp(event_show_url($sample), 'event show '.$sample->id, [
                    'og:title',
                    'application/ld+json',
                    'rel="canonical"',
                ]) && $ok;
            } else {
                $this->warn('• event show — skipped (no published events)');
            }
        } else {
            $this->line('• HTTP probes skipped (--skip-http)');
        }

        $this->newLine();
        if ($ok) {
            $this->info('All checks passed.');

            return self::SUCCESS;
        }

        $this->error('One or more checks failed.');

        return self::FAILURE;
    }

    private function checkStockWebp(): bool
    {
        $required = ['football', 'coffee', 'social'];
        $missing = [];

        foreach ($required as $base) {
            foreach ([320, 640, 1280] as $w) {
                $path = public_path("images/activities/{$base}-{$w}.webp");
                if (! is_file($path)) {
                    $missing[] = "{$base}-{$w}.webp";
                }
            }
        }

        if ($missing === []) {
            $this->line('✓ stock WebP variants present');

            return true;
        }

        $this->error('✗ missing stock WebP: '.implode(', ', $missing));

        return false;
    }

    private function checkPublishedContent(): bool
    {
        $published = Event::query()->where('status', EventStatus::Published)->count();
        $missingSlug = Event::query()
            ->where('status', EventStatus::Published)
            ->where(fn ($q) => $q->whereNull('slug')->orWhere('slug', ''))
            ->count();
        $missingDescription = Event::query()
            ->where('status', EventStatus::Published)
            ->where(fn ($q) => $q->whereNull('description')->orWhere('description', ''))
            ->count();

        $this->line("✓ published events: {$published}");

        $ok = true;
        if ($missingSlug > 0) {
            $this->error("✗ published events missing slug: {$missingSlug}");
            $ok = false;
        } else {
            $this->line('✓ all published events have slugs');
        }

        if ($missingDescription > 0) {
            $this->warn("• published events with empty description: {$missingDescription} (soft SEO gap)");
        } else {
            $this->line('✓ all published events have descriptions');
        }

        return $ok;
    }

    /**
     * @param  list<string>  $needles
     */
    private function checkHttp(string $url, string $label, array $needles): bool
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'FunnitySeoHealth/1.0'])
                ->get($url);
        } catch (Throwable $e) {
            $this->error("✗ {$label} — request failed: ".$e->getMessage());

            return false;
        }

        if (! $response->successful()) {
            $this->error("✗ {$label} — HTTP {$response->status()} ({$url})");

            return false;
        }

        $body = $response->body();
        foreach ($needles as $needle) {
            if (! str_contains($body, $needle)) {
                $this->error("✗ {$label} — missing “{$needle}”");

                return false;
            }
        }

        $this->line("✓ {$label}");

        return true;
    }
}
