<?php

namespace App\Services\Seo;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Display fields such as cost_per_person are not charges.
 * Only a real ledger table with rows for these events blocks SEO data commands.
 */
class EventFinancialLinks
{
    /**
     * @param  list<int>  $eventIds
     * @return list<string>
     */
    public static function unexpected(array $eventIds): array
    {
        if ($eventIds === []) {
            return [];
        }

        $names = [
            'payments',
            'payment_intents',
            'orders',
            'order_items',
            'charges',
            'invoices',
            'invoice_items',
            'refunds',
            'transactions',
            'payouts',
        ];

        $hits = [];

        foreach ($names as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'event_id')) {
                continue;
            }

            $count = DB::table($table)->whereIn('event_id', $eventIds)->count();
            if ($count > 0) {
                $hits[] = "{$table} has {$count} row(s) for the selected events";
            }
        }

        return $hits;
    }
}
