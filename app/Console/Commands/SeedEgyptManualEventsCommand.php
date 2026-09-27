<?php

namespace App\Console\Commands;

use Database\Seeders\EgyptManualEventsSeeder;
use Illuminate\Console\Command;

class SeedEgyptManualEventsCommand extends Command
{
    protected $signature = 'events:seed-egypt-catalog
                            {--fresh : Delete previous catalog events owned by egypt.catalog@funnity.com first}';

    protected $description = 'Seed 50 Egypt catalog events (manual images → R2, members, join requests, chat)';

    public function handle(): int
    {
        $seeder = new EgyptManualEventsSeeder;
        $seeder->setCommand($this);
        $seeder->run((bool) $this->option('fresh'));

        return self::SUCCESS;
    }
}
