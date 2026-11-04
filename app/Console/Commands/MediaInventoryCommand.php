<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Non-destructive media inventory / R2 migration dry-run.
 * Never deletes objects. Never rewrites production references unless --execute is passed
 * (execute path is intentionally unimplemented until owner approval).
 */
class MediaInventoryCommand extends Command
{
    protected $signature = 'media:inventory
                            {--dry-run : Report only (default behavior)}
                            {--json : Machine-readable JSON summary}
                            {--execute : FORBIDDEN without owner approval — currently refused}';

    protected $description = 'Inventory user media references and report R2 / local readiness (non-destructive)';

    public function handle(): int
    {
        if ($this->option('execute')) {
            $this->error('Refused: destructive / reference-rewriting migration requires explicit owner approval and a separate verified backup.');
            $this->line('Use --dry-run (default) to inventory only.');

            return self::FAILURE;
        }

        $report = [
            'generated_at' => now()->toIso8601String(),
            'media_disk' => config('media.disk'),
            'private_disk' => config('media.private_disk'),
            'r2_public_configured' => filled(config('filesystems.disks.r2.bucket')) && filled(config('filesystems.disks.r2.endpoint')),
            'r2_private_configured' => filled(config('filesystems.disks.r2_private.bucket')) && filled(config('filesystems.disks.r2_private.endpoint')),
            'categories' => [],
            'legacy_local_candidates' => [],
            'notes' => [
                'Secrets are never printed.',
                'Object existence checks against remote R2 are skipped in dry-run to avoid large billed listings; DB inventory is authoritative for references.',
                'Do not delete local originals without a separate owner authorization.',
            ],
        ];

        $report['categories']['profile_photos'] = $this->countWhereNotEmpty('users', 'profile_image_path');
        $report['categories']['events_with_image'] = $this->countWhereNotEmpty('events', 'image_path');
        $report['categories']['event_image_disks'] = $this->groupCount('events', 'image_disk', 'image_path');
        $report['categories']['identity_documents'] = Schema::hasTable('identity_documents')
            ? $this->groupCount('identity_documents', 'disk', 'path')
            : [];
        $report['categories']['dm_media'] = $this->groupCount('direct_messages', 'message_type', 'media_path');
        $report['categories']['event_chat_media'] = $this->groupCount('event_messages', 'message_type', 'media_path');

        $localEvents = DB::table('events')
            ->whereNotNull('image_path')
            ->where('image_path', '!=', '')
            ->where(function ($q) {
                $q->whereNull('image_disk')->orWhere('image_disk', 'media')->orWhere('image_disk', 'public');
            })
            ->count();
        $report['legacy_local_candidates']['event_covers_non_r2'] = $localEvents;

        $avatarsOnPublicDiskUrl = DB::table('users')
            ->whereNotNull('profile_image_path')
            ->where('profile_image_path', 'like', '%/storage/media/%')
            ->count();
        $report['legacy_local_candidates']['profile_photos_local_url'] = $avatarsOnPublicDiskUrl;

        $report['legacy_local_candidates']['local_media_disk_files'] = $this->safeLocalFileCount('media');
        $report['legacy_local_candidates']['local_private_disk_files'] = $this->safeLocalFileCount('media_private');

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Funnity media inventory (dry-run)');
        $this->table(['Setting', 'Value'], [
            ['public disk', $report['media_disk']],
            ['private disk', $report['private_disk']],
            ['R2 public configured', $report['r2_public_configured'] ? 'yes' : 'no'],
            ['R2 private configured', $report['r2_private_configured'] ? 'yes' : 'no'],
        ]);

        $this->line('Category counts:');
        foreach ($report['categories'] as $name => $value) {
            if (is_array($value)) {
                $this->line("  {$name}:");
                foreach ($value as $k => $c) {
                    $this->line('    '.($k === '' ? '(empty)' : $k).": {$c}");
                }
            } else {
                $this->line("  {$name}: {$value}");
            }
        }

        $this->line('Legacy / migration candidates:');
        foreach ($report['legacy_local_candidates'] as $k => $v) {
            $this->line("  {$k}: {$v}");
        }

        $this->newLine();
        $this->warn('No files were copied, deleted, or re-pointed. Owner approval required before Stage E migration.');

        return self::SUCCESS;
    }

    private function countWhereNotEmpty(string $table, string $column): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        return (int) DB::table($table)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->count();
    }

    /**
     * @return array<string, int>
     */
    private function groupCount(string $table, string $groupColumn, string $requiredPathColumn): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        $rows = DB::table($table)
            ->whereNotNull($requiredPathColumn)
            ->where($requiredPathColumn, '!=', '')
            ->selectRaw('COALESCE('.$groupColumn.', ?) as g, COUNT(*) as c', ['null'])
            ->groupBy('g')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row->g] = (int) $row->c;
        }

        return $out;
    }

    private function safeLocalFileCount(string $disk): int
    {
        try {
            if (! array_key_exists($disk, config('filesystems.disks', []))) {
                return 0;
            }
            if (config("filesystems.disks.{$disk}.driver") !== 'local') {
                return 0;
            }

            return count(Storage::disk($disk)->allFiles());
        } catch (\Throwable) {
            return 0;
        }
    }
}
