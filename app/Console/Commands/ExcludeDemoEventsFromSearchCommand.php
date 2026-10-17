<?php

namespace App\Console\Commands;

use App\Services\Seo\ExcludeDemoEventsFromSearch;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

class ExcludeDemoEventsFromSearchCommand extends Command
{
    protected $signature = 'seo:exclude-demo-events';

    protected $description = 'Mark confirmed demo events 1–500 as not search-indexable, or abort with no changes';

    public function handle(ExcludeDemoEventsFromSearch $exclusion): int
    {
        try {
            $count = $exclusion();
        } catch (RuntimeException $exception) {
            $this->error('Aborted. No events were updated.');
            foreach (preg_split("/\r\n|\n|\r/", $exception->getMessage()) ?: [] as $line) {
                if ($line !== '') {
                    $this->line($line);
                }
            }

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->error('Aborted. No events were updated.');
            $this->line($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Marked {$count} demo events search_indexable=false and cleared the sitemap cache.");

        return self::SUCCESS;
    }
}
