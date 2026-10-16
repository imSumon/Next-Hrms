<?php

declare(strict_types=1);

namespace Hrms\EmployeeClassification;

final class Fingerprint
{
    /**
     * Check size, then SHA-256 of the file bytes, and only then open a stream.
     * Gzip files are hashed compressed and opened with a streaming decoder.
     *
     * @param  (callable(string, string): string)|null  $hashFile
     * @param  (callable(string): DumpStream)|null  $open
     */
    public function openVerified(
        string $path,
        int $expectedBytes,
        string $expectedHash,
        bool $compressed,
        ?callable $hashFile = null,
        ?callable $open = null,
    ): DumpStream {
        if (! is_file($path)) {
            throw new ClassificationException('Approved source file is missing.');
        }
        $size = filesize($path);
        if ($size !== $expectedBytes) {
            throw new ClassificationException('Source file size does not match the manifest.');
        }

        $hashFile ??= hash_file(...);
        $actual = strtoupper((string) $hashFile('sha256', $path));
        $expected = strtoupper($expectedHash);
        if (strlen($expected) !== 64 || ! hash_equals($expected, $actual)) {
            throw new ClassificationException('Source file SHA-256 does not match the manifest.');
        }

        $open ??= $compressed
            ? GzipDumpStream::open(...)
            : FileDumpStream::open(...);

        return $open($path);
    }
}
