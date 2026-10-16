<?php

declare(strict_types=1);

use Hrms\EmployeeClassification\ClassificationException;
use Hrms\EmployeeClassification\Runner;
use Hrms\EmployeeClassification\Safeguard;

require __DIR__.'/bootstrap.php';

if (PHP_SAPI === 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    exit(main($argv));
}

/**
 * Standalone Step 20J entry point. It is not an Artisan command, route, or scheduler.
 *
 * @param  list<string>  $argv
 */
function main(array $argv): int
{
    try {
        guardArguments($argv);
        $projectRoot = argument($argv, '--project-root');
        $output = argument($argv, '--output');
        if ($projectRoot === null || $output === null) {
            fwrite(STDERR, "Usage: php classify.php --project-root <hrms-root> --output <absolute-path-outside-the-repository>\n");

            return 1;
        }
        (new Runner())->runApprovedExtracts($projectRoot, $output);
        fwrite(STDOUT, "Classification report written.\n");

        return 0;
    } catch (ClassificationException $exception) {
        fwrite(STDERR, $exception->getMessage()."\n");

        return 1;
    }
}

/** @param  list<string>  $argv */
function guardArguments(array $argv): void
{
    (new Safeguard())->assertNoForbiddenTarget($argv, 'arguments');
    foreach ($argv as $argument) {
        if (str_starts_with($argument, '--approve') || str_contains($argument, '--mode=')) {
            throw new ClassificationException('This entry point stays in gates_closed mode.');
        }
    }
}

/** @param  list<string>  $argv */
function argument(array $argv, string $name): ?string
{
    $count = count($argv);
    for ($index = 0; $index < $count; $index++) {
        if ($argv[$index] === $name && isset($argv[$index + 1])) {
            return $argv[$index + 1];
        }
        if (str_starts_with($argv[$index], $name.'=')) {
            return substr($argv[$index], strlen($name) + 1);
        }
    }

    return null;
}
