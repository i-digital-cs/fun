<?php

namespace App\Support\Factories;

use RuntimeException;
use UnitEnum;

use function Illuminate\Support\enum_value;

/**
 * Blocks Eloquent factory persistence against the production database.
 *
 * Intended only for Database\Factories\* (via AppFactory::store).
 * Does not hook model events and does not affect normal application writes.
 */
final class FactoryPersistenceGuard
{
    /**
     * Known production database names for this project.
     * Also merged with FACTORY_PROTECTED_DATABASES (comma-separated).
     *
     * @var list<string>
     */
    private const HARDCODED_PROTECTED_DATABASES = [
        'd14098_fun',
    ];

    /**
     * @param  UnitEnum|string|null  $connection  Factory connection name, or null for default.
     */
    public static function assertPersistenceAllowed(UnitEnum|string|null $connection = null): void
    {
        if (self::explicitlyAllowed()) {
            return;
        }

        $connectionName = enum_value($connection) ?? config('database.default');
        if (! is_string($connectionName) || $connectionName === '') {
            throw new RuntimeException(
                'Factory persistence blocked: database connection could not be established.'
            );
        }

        /** @var array<string, mixed>|null $config */
        $config = config("database.connections.{$connectionName}");
        if (! is_array($config)) {
            throw new RuntimeException(
                "Factory persistence blocked: connection [{$connectionName}] is not configured."
            );
        }

        $driver = isset($config['driver']) ? strtolower((string) $config['driver']) : '';

        // Isolated PHPUnit / local sqlite targets are never treated as production.
        if ($driver === 'sqlite') {
            return;
        }

        if ($driver === '') {
            throw new RuntimeException(
                "Factory persistence blocked: driver for [{$connectionName}] could not be established."
            );
        }

        $database = self::resolveDatabaseName($config);
        if ($database === null) {
            throw new RuntimeException(
                "Factory persistence blocked: database name for [{$connectionName}] could not be established."
            );
        }

        if (in_array($database, self::protectedDatabaseNames(), true)) {
            throw new RuntimeException(
                "Factory persistence is forbidden against protected database [{$database}]."
            );
        }

        // Fail closed for production-like app env on non-sqlite drivers even if
        // the database was renamed and is not yet on the protected list.
        if (app()->environment('production')) {
            throw new RuntimeException(
                'Factory persistence is forbidden in the production environment.'
            );
        }
    }

    private static function explicitlyAllowed(): bool
    {
        $raw = $_ENV['ALLOW_FACTORY_PERSISTENCE']
            ?? $_SERVER['ALLOW_FACTORY_PERSISTENCE']
            ?? getenv('ALLOW_FACTORY_PERSISTENCE')
            ?? false;

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN) === true;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function resolveDatabaseName(array $config): ?string
    {
        $database = $config['database'] ?? null;
        if (is_string($database) && trim($database) !== '') {
            return trim($database);
        }

        $url = $config['url'] ?? null;
        if (is_string($url) && $url !== '') {
            $path = parse_url($url, PHP_URL_PATH);
            if (is_string($path) && $path !== '' && $path !== '/') {
                return trim($path, '/');
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private static function protectedDatabaseNames(): array
    {
        $fromEnv = $_ENV['FACTORY_PROTECTED_DATABASES']
            ?? $_SERVER['FACTORY_PROTECTED_DATABASES']
            ?? getenv('FACTORY_PROTECTED_DATABASES')
            ?? '';

        $extra = [];
        if (is_string($fromEnv) && trim($fromEnv) !== '') {
            $extra = array_values(array_filter(array_map(
                static fn (string $name): string => trim($name),
                explode(',', $fromEnv)
            )));
        }

        return array_values(array_unique([...self::HARDCODED_PROTECTED_DATABASES, ...$extra]));
    }
}
