<?php

namespace App\Console\Commands;

use App\Services\Seo\ValidatePlaceholderCancellations;
use Illuminate\Console\Command;

class ValidatePlaceholderCancellationsCommand extends Command
{
    protected $signature = 'seo:validate-placeholder-cancellations';

    protected $description = 'Revalidate events 501, 502, and 602 for a future cancel. Changes nothing.';

    public function handle(ValidatePlaceholderCancellations $validator): int
    {
        $result = $validator();

        foreach ($result['lines'] as $line) {
            $this->line($line);
        }

        if ($result['ok']) {
            $this->info('Validation passed. No event was cancelled.');

            return self::SUCCESS;
        }

        $this->error('Validation failed. No event was cancelled.');

        return self::FAILURE;
    }
}
