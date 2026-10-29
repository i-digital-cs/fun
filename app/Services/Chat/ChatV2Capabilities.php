<?php

namespace App\Services\Chat;

use Illuminate\Support\Facades\Schema;

/**
 * Feature detection for Chat V2 schema columns (safe before migration runs).
 */
class ChatV2Capabilities
{
    private static ?bool $eventReady = null;

    private static ?bool $dmReady = null;

    private static ?bool $dmReactionsReady = null;

    public static function eventMessageExtensions(): bool
    {
        return self::$eventReady ??= Schema::hasColumn('event_messages', 'client_uuid');
    }

    public static function directMessageExtensions(): bool
    {
        return self::$dmReady ??= Schema::hasColumn('direct_messages', 'client_uuid');
    }

    public static function directMessageReactions(): bool
    {
        return self::$dmReactionsReady ??= Schema::hasTable('direct_message_reactions');
    }

    /** @internal tests */
    public static function reset(): void
    {
        self::$eventReady = null;
        self::$dmReady = null;
        self::$dmReactionsReady = null;
    }
}
