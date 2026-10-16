<?php

declare(strict_types=1);

namespace Hrms\EmployeeClassification;

use Throwable;

/**
 * Writes a finished report to a temporary file in the approved output directory
 * and renames it into place only after the full byte count is on disk.
 */
final class ReportPublisher
{
    /**
     * @param  (callable(string, string): int|false)|null  $write
     */
    public function publish(string $destination, string $contents, ?callable $write = null): void
    {
        $partial = $destination.'.partial';
        if (file_exists($destination) || file_exists($partial)) {
            throw new ClassificationException('Output report already exists and will not be overwritten.');
        }

        $write ??= function (string $path, string $body): int|false {
            return file_put_contents($path, $body);
        };

        try {
            $written = $write($partial, $contents);
            if ($written !== strlen($contents) || ! is_file($partial) || filesize($partial) !== strlen($contents)) {
                throw new ClassificationException('Classification report write was incomplete.');
            }
            if (! rename($partial, $destination)) {
                throw new ClassificationException('Classification report could not be published.');
            }
        } catch (Throwable $exception) {
            if (is_file($partial)) {
                unlink($partial);
            }
            if ($exception instanceof ClassificationException) {
                throw $exception;
            }
            throw new ClassificationException('Classification report could not be written.');
        }
    }
}
