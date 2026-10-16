<?php

declare(strict_types=1);

namespace Hrms\EmployeeClassification;

final class Safeguard
{
    public const FORBIDDEN_DATABASES = [
        'proedge_new_hrms_test',
        'proedge_new_hrms',
    ];

    public function assertNoForbiddenTarget(mixed $value, string $label = 'configuration'): void
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                if (is_string($key) && $this->isForbiddenKey($key)) {
                    throw new ClassificationException('Database connection settings are refused.');
                }
                $this->assertNoForbiddenTarget($item, $label);
            }

            return;
        }

        if (! is_string($value) || $value === '') {
            return;
        }

        $haystack = strtolower(str_replace('\\', '/', $value));
        foreach (self::FORBIDDEN_DATABASES as $database) {
            if (str_contains($haystack, $database)) {
                throw new ClassificationException('Refusing '.$database.' as a target, connection, or output location.');
            }
        }
        foreach (['pgsql:', 'mysql:', 'mysqli:', 'sqlite:', 'sqlsrv:', 'dbname='] as $driver) {
            if (str_contains($haystack, $driver)) {
                throw new ClassificationException('Database connection strings are refused.');
            }
        }
    }

    /**
     * @return string Absolute output path that was accepted and not created
     */
    public function assertOutputPath(string $outputPath, string $applicationRoot, string $projectRoot): string
    {
        $this->assertNoForbiddenTarget($outputPath, 'output');
        if ($outputPath === '' || ! $this->isAbsolute($outputPath)) {
            throw new ClassificationException('Output path must be an explicit absolute path.');
        }

        $parent = dirname($outputPath);
        $realParent = realpath($parent);
        if ($realParent === false) {
            throw new ClassificationException('Output directory does not exist.');
        }

        $full = $realParent.DIRECTORY_SEPARATOR.basename($outputPath);
        $this->assertNoForbiddenTarget($full, 'output');
        if (file_exists($full)) {
            throw new ClassificationException('Output report already exists and will not be overwritten.');
        }

        $roots = array_filter([
            realpath($applicationRoot) ?: '',
            realpath($projectRoot) ?: '',
            realpath(rtrim($projectRoot, '\\/').DIRECTORY_SEPARATOR.'legacy-data') ?: '',
        ]);
        foreach ($roots as $root) {
            if ($this->isInside($full, $root) || $this->isInside($realParent, $root)) {
                throw new ClassificationException('Output path is inside the repository or a source dump directory.');
            }
        }

        return $full;
    }

    private function isForbiddenKey(string $key): bool
    {
        return in_array(strtolower($key), [
            'connection',
            'dsn',
            'pdo',
            'db_connection',
            'db_database',
            'db_url',
            'database_url',
        ], true);
    }

    private function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }

    private function isInside(string $path, string $root): bool
    {
        $path = strtolower(rtrim(str_replace('\\', '/', $path), '/'));
        $root = strtolower(rtrim(str_replace('\\', '/', $root), '/'));

        return $path === $root || str_starts_with($path, $root.'/');
    }
}
