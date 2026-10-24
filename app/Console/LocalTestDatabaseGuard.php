<?php

namespace App\Console;

final class LocalTestDatabaseGuard
{
    public const DATABASE = 'proedge_new_hrms_test';

    public const DEVICE_LOG_FIXTURE_CONNECTION = 'device_log_fixture';

    public static function accepts(?string $database, ?string $address): bool
    {
        return $database === self::DATABASE
            && is_string($address)
            && str_starts_with($address, '127.0.0.1');
    }

    /**
     * A local SQLite copy of the device log, used only by the guarded test
     * server so the preview can be exercised without any live source.
     */
    public static function isDeviceLogFixture(string $path): bool
    {
        if (! is_file($path) || ! is_readable($path)) {
            return false;
        }
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }
        $header = fread($handle, 16);
        fclose($handle);

        return $header === "SQLite format 3\0";
    }

    /**
     * @return array{driver: string, database: string, prefix: string, foreign_key_constraints: bool}|null
     */
    public static function deviceLogFixtureConnection(): ?array
    {
        if (self::environment('HRMS_TEST_DATABASE_ONLY') !== '1') {
            return null;
        }
        $path = self::environment('HRMS_DEVICE_LOG_FIXTURE');
        if (! is_string($path) || $path === '' || ! self::isDeviceLogFixture($path)) {
            return null;
        }

        return ['driver' => 'sqlite', 'database' => $path, 'prefix' => '', 'foreign_key_constraints' => false];
    }

    private static function environment(string $key): mixed
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    }
}
