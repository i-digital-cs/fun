<?php

namespace App\Console\Commands;

use App\Services\Account\AccountDeletionService;
use Illuminate\Console\Command;

class PurgeAccountDeletionsCommand extends Command
{
    protected $signature = 'account:purge-deletions';

    protected $description = 'Anonymize user accounts whose deletion grace period has ended';

    public function handle(AccountDeletionService $service): int
    {
        $count = $service->purgeDueAccounts();
        $this->info("Purged {$count} account(s).");

        return self::SUCCESS;
    }
}
