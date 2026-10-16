<?php

declare(strict_types=1);

namespace Hrms\EmployeeClassification;

interface DumpStream
{
    public function read(int $length): string;

    public function close(): void;
}

final class StringDumpStream implements DumpStream
{
    private int $offset = 0;

    public function __construct(private readonly string $contents)
    {
    }

    public function read(int $length): string
    {
        $chunk = substr($this->contents, $this->offset, $length);
        $this->offset += strlen($chunk);

        return $chunk;
    }

    public function close(): void
    {
    }
}

final class FileDumpStream implements DumpStream
{
    /** @param resource $handle */
    private function __construct(private $handle)
    {
    }

    public static function open(string $path): self
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new ClassificationException('Approved source file could not be opened.');
        }

        return new self($handle);
    }

    public function read(int $length): string
    {
        $chunk = fread($this->handle, $length);

        return $chunk === false ? '' : $chunk;
    }

    public function close(): void
    {
        fclose($this->handle);
    }
}

final class GzipDumpStream implements DumpStream
{
    /** @param resource $handle */
    private function __construct(private $handle)
    {
    }

    public static function open(string $path): self
    {
        $handle = gzopen($path, 'rb');
        if ($handle === false) {
            throw new ClassificationException('Compressed source could not be opened as a stream.');
        }

        return new self($handle);
    }

    public function read(int $length): string
    {
        $chunk = gzread($this->handle, $length);

        return $chunk === false ? '' : $chunk;
    }

    public function close(): void
    {
        gzclose($this->handle);
    }
}

final class ByteWindow
{
    private string $data = '';

    private int $offset = 0;

    private bool $eof = false;

    public function __construct(private readonly DumpStream $stream)
    {
    }

    public function done(): bool
    {
        return $this->peek(1) === '';
    }

    public function peek(int $length): string
    {
        $this->fill($length);

        return substr($this->data, $this->offset, $length);
    }

    public function consume(int $length): void
    {
        $this->fill($length);
        $available = strlen($this->data) - $this->offset;
        if ($length > $available) {
            throw new ClassificationException('Unexpected end of dump.');
        }
        $this->offset += $length;
        if ($this->offset > 1048576) {
            $this->data = substr($this->data, $this->offset);
            $this->offset = 0;
        }
    }

    public function startsWithKeyword(string $word): bool
    {
        $length = strlen($word);
        $got = $this->peek($length + 1);
        if (strlen($got) < $length || strncasecmp($got, $word, $length) !== 0) {
            return false;
        }
        if (strlen($got) === $length) {
            return true;
        }
        $next = $got[$length];

        return ! ctype_alnum($next) && $next !== '_';
    }

    public function consumeKeyword(string $word): void
    {
        if (! $this->startsWithKeyword($word)) {
            throw new ClassificationException('Expected SQL keyword '.$word.'.');
        }
        $this->consume(strlen($word));
    }

    private function fill(int $length): void
    {
        while (! $this->eof && (strlen($this->data) - $this->offset) < $length) {
            $chunk = $this->stream->read(65536);
            if ($chunk === '') {
                $this->eof = true;

                return;
            }
            $this->data .= $chunk;
        }
    }
}
