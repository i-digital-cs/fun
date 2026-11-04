<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Lightweight registration / OTP / captcha security snapshot.
 * Avoids dumping emails, IPs, tokens, or secrets.
 */
class RegistrationSecurityReportCommand extends Command
{
    protected $signature = 'security:registration-report
                            {--hours=24 : How many hours of laravel.log lines to scan}
                            {--json : Emit JSON instead of a table}';

    protected $description = 'Summarize registration budgets, OTP counters, and recent security log events';

    public function handle(): int
    {
        $hourKey = now()->format('YmdH');
        $dayKey = now()->format('Ymd');

        $snapshot = [
            'generated_at' => now()->toIso8601String(),
            'env' => config('app.env'),
            'registration' => [
                'enabled' => (bool) config('registration.enabled', true),
                'global_per_hour_limit' => (int) config('registration.global_per_hour'),
                'global_per_day_limit' => (int) config('registration.global_per_day'),
                'ip_per_day_limit' => (int) config('registration.ip_per_day'),
                'email_per_hour_limit' => (int) config('registration.email_per_hour'),
                'require_otp_budget' => (bool) config('registration.require_otp_budget'),
                'global_hour_count' => (int) Cache::get('reg:budget:global:hour:'.$hourKey, 0),
                'global_day_count' => (int) Cache::get('reg:budget:global:day:'.$dayKey, 0),
            ],
            'turnstile' => [
                'enabled_web' => (bool) config('turnstile.enabled_web'),
                'enabled_api' => (bool) config('turnstile.enabled_api'),
                'site_key_configured' => filled(config('turnstile.site_key')),
                'secret_key_configured' => filled(config('turnstile.secret_key')),
                'expected_action' => (string) config('turnstile.expected_action'),
                'expected_hostnames' => config('turnstile.expected_hostnames', []),
            ],
            'otp_abuse_lane' => [
                'hour_limit' => (int) config('otp.outbound_per_hour'),
                'day_limit' => (int) config('otp.outbound_per_day'),
                'hour_count' => (int) Cache::get('otp:outbound:abuse:hour:'.$hourKey, 0),
                'day_count' => (int) Cache::get('otp:outbound:abuse:day:'.$dayKey, 0),
            ],
            'users' => [
                'total' => User::query()->count(),
                'unverified' => User::query()->whereNull('email_verified_at')->count(),
                'created_last_24h' => User::query()->where('created_at', '>=', now()->subDay())->count(),
            ],
            'log_events_last_hours' => $this->countLogEvents((int) $this->option('hours')),
        ];

        if ($this->option('json')) {
            $this->line(json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Registration security report');
        $this->line('Generated: '.$snapshot['generated_at']);
        $this->newLine();
        $this->table(['Metric', 'Value'], $this->flatten($snapshot));

        return self::SUCCESS;
    }

    /**
     * @return array<string, int>
     */
    private function countLogEvents(int $hours): array
    {
        $path = storage_path('logs/laravel.log');
        $counts = [
            'registration_captcha_rejected' => 0,
            'registration_signal_rejected' => 0,
            'registration_global_budget' => 0,
            'registration_ip_budget' => 0,
            'registration_ip_hour_budget' => 0,
            'registration_email_budget' => 0,
            'registration_otp_budget' => 0,
            'registration_disabled' => 0,
            'turnstile_verify_rejected' => 0,
            'turnstile_token_reused' => 0,
            'turnstile_misconfigured' => 0,
            'otp_outbound_budget_exceeded' => 0,
        ];

        if (! File::exists($path)) {
            return $counts;
        }

        $cutoff = now()->subHours(max(1, $hours));
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return $counts;
        }

        while (($line = fgets($handle)) !== false) {
            if (! preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $m)) {
                continue;
            }
            try {
                $ts = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $m[1], config('app.timezone'));
            } catch (\Throwable) {
                continue;
            }
            if ($ts->lt($cutoff)) {
                continue;
            }
            foreach (array_keys($counts) as $event) {
                if (str_contains($line, $event)) {
                    $counts[$event]++;
                }
            }
        }
        fclose($handle);

        return $counts;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  string  $prefix
     * @return list<array{0: string, 1: string}>
     */
    private function flatten(array $data, string $prefix = ''): array
    {
        $rows = [];
        foreach ($data as $key => $value) {
            $label = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            if (is_array($value) && array_is_list($value)) {
                $rows[] = [$label, implode(',', array_map('strval', $value))];
            } elseif (is_array($value)) {
                $rows = array_merge($rows, $this->flatten($value, $label));
            } elseif (is_bool($value)) {
                $rows[] = [$label, $value ? 'true' : 'false'];
            } else {
                $rows[] = [$label, (string) $value];
            }
        }

        return $rows;
    }
}
