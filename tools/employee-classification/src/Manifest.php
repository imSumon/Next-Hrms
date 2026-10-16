<?php

declare(strict_types=1);

namespace Hrms\EmployeeClassification;

final class Manifest
{
    /** @var array<string, mixed>|null */
    private static ?array $config = null;

    public static function config(): array
    {
        if (self::$config === null) {
            $loaded = require dirname(__DIR__).'/config/classification.php';
            if (! is_array($loaded)) {
                throw new ClassificationException('Classification configuration is unreadable.');
            }
            self::$config = $loaded;
        }

        return self::$config;
    }

    /** @return list<array<string, mixed>> */
    public static function sources(): array
    {
        $sources = self::config()['sources'] ?? null;
        if (! is_array($sources) || $sources === []) {
            throw new ClassificationException('Approved source manifest is empty.');
        }

        return array_values($sources);
    }

    /** @return array<int, string> */
    public static function companyUlids(): array
    {
        $ulids = self::config()['company_ulids'] ?? null;
        if (! is_array($ulids)) {
            throw new ClassificationException('Company crosswalk is missing.');
        }

        return $ulids;
    }

    public static function assertRelativePath(string $relativePath): string
    {
        $normalized = str_replace('\\', '/', $relativePath);
        if (
            $normalized === ''
            || str_contains($normalized, '..')
            || str_starts_with($normalized, '/')
            || preg_match('/^[A-Za-z]:/', $normalized) === 1
        ) {
            throw new ClassificationException('Source path is outside the approved manifest.');
        }

        foreach (self::sources() as $source) {
            if ($source['relative_path'] === $normalized) {
                return $normalized;
            }
        }

        throw new ClassificationException('Source path is outside the approved manifest.');
    }

    public static function resolve(string $projectRoot, string $relativePath): string
    {
        $relativePath = self::assertRelativePath($relativePath);
        $root = rtrim(str_replace('\\', '/', $projectRoot), '/');
        if ($root === '' || preg_match('/^[A-Za-z]:$/', $root) === 1) {
            throw new ClassificationException('Project root is not usable.');
        }

        return $root.'/'.$relativePath;
    }

    public static function sourceByRelativePath(string $relativePath): array
    {
        $relativePath = self::assertRelativePath($relativePath);
        foreach (self::sources() as $source) {
            if ($source['relative_path'] === $relativePath) {
                return $source;
            }
        }

        throw new ClassificationException('Source path is outside the approved manifest.');
    }
}
