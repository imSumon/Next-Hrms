<?php

declare(strict_types=1);

namespace Hrms\EmployeeClassification;

/**
 * Streams a mysqldump and keeps only the employee identity columns from Step 20J section 4.
 * The decompressed dump is not held as one string. One kept field is buffered; skipped
 * fields, including employment_history, are discarded while they are read.
 */
final class DumpParser
{
    /** @var list<string> */
    public const KEPT_COLUMNS = [
        'id',
        'emp_id_no',
        'emp_first_name',
        'emp_mid_name',
        'emp_last_name',
        'joining_date',
        'active',
        'disconrinue',
        'discontinue_date',
    ];

    /** @var list<string> */
    public const LIMITATIONS = [
        'Accepts mysqldump CREATE TABLE employees and INSERT INTO employees tuples only.',
        'Values may be NULL, unquoted numbers, or single-quoted strings with backslash escapes and doubled quotes.',
        'INSERT SET, REPLACE, hexadecimal literals, bit literals, and expressions fail closed.',
        'Header database names are read only from -- comment lines in the first 8192 bytes. Zero names, or more than one distinct name, fail closed.',
        'Column order comes from that dump CREATE TABLE, or from an INSERT column list when one is present.',
        'A single kept SQL string is buffered. Skipped strings are not retained. The full decompressed file is not materialized.',
    ];

    /**
     * @return array{header_database: string, columns: list<string>, rows: list<array<string, ?string>>}
     */
    public function parse(DumpStream $stream, string $expectedDatabase): array
    {
        $window = new ByteWindow($stream);
        $header = $this->headerDatabase($window);
        if ($header !== $expectedDatabase) {
            throw new ClassificationException('Dump header database does not match the manifest entry for this path.');
        }

        $columns = null;
        $rows = [];
        while (! $window->done()) {
            $this->skipWsAndComments($window);
            if ($window->done()) {
                break;
            }
            if ($window->startsWithKeyword('REPLACE')) {
                throw new ClassificationException('Unsupported REPLACE statement.');
            }
            if ($window->startsWithKeyword('CREATE')) {
                $columns = $this->readCreate($window, $columns);
                continue;
            }
            if ($window->startsWithKeyword('INSERT')) {
                $parsed = $this->readInsert($window, $columns);
                if ($parsed !== null) {
                    foreach ($parsed as $row) {
                        $rows[] = $row;
                    }
                }
                continue;
            }
            $this->consumeStatement($window);
        }

        if ($columns === null) {
            throw new ClassificationException('CREATE TABLE employees was not found.');
        }

        return [
            'header_database' => $header,
            'columns' => $columns,
            'rows' => $rows,
        ];
    }

    private function headerDatabase(ByteWindow $window): string
    {
        $prefix = $window->peek(8192);
        $matched = preg_match_all('/^--.*\bDatabase:\s*`?([A-Za-z0-9_]+)`?/m', $prefix, $matches);
        if ($matched === false || $matches[1] === []) {
            throw new ClassificationException('Dump header does not name a database.');
        }
        $names = array_values(array_unique($matches[1]));
        if (count($names) !== 1) {
            throw new ClassificationException('Dump header database is ambiguous.');
        }

        return $names[0];
    }

    /** @param  list<string>|null  $existing */
    private function readCreate(ByteWindow $window, ?array $existing): ?array
    {
        $window->consumeKeyword('CREATE');
        $this->skipWsAndComments($window);
        if (! $window->startsWithKeyword('TABLE')) {
            $this->consumeStatement($window);

            return $existing;
        }
        $window->consumeKeyword('TABLE');
        $this->skipWsAndComments($window);
        if ($window->startsWithKeyword('IF')) {
            throw new ClassificationException('Unsupported CREATE TABLE form.');
        }
        $name = $this->readTableName($window);
        if ($name !== 'employees') {
            $this->consumeStatement($window);

            return $existing;
        }
        if ($existing !== null) {
            throw new ClassificationException('More than one CREATE TABLE employees statement.');
        }
        $body = $this->readCreateBody($window);
        $columns = $this->columnsFromBody($body);
        foreach (self::KEPT_COLUMNS as $required) {
            if (! in_array($required, $columns, true)) {
                throw new ClassificationException('employees table is missing a required column.');
            }
        }
        $this->consumeStatement($window);

        return $columns;
    }

    /**
     * @param  list<string>|null  $createColumns
     * @return list<array<string, ?string>>|null
     */
    private function readInsert(ByteWindow $window, ?array $createColumns): ?array
    {
        $window->consumeKeyword('INSERT');
        $this->skipWsAndComments($window);
        if (! $window->startsWithKeyword('INTO')) {
            throw new ClassificationException('Unsupported INSERT form.');
        }
        $window->consumeKeyword('INTO');
        $name = $this->readTableName($window);
        if ($name !== 'employees') {
            $this->consumeStatement($window);

            return null;
        }
        if ($createColumns === null) {
            throw new ClassificationException('INSERT INTO employees appears before CREATE TABLE employees.');
        }

        $this->skipWsAndComments($window);
        $insertColumns = $createColumns;
        if ($window->peek(1) === '(') {
            $insertColumns = $this->readIdentifierList($window);
            $this->assertUniqueColumns($insertColumns);
        }
        foreach (self::KEPT_COLUMNS as $required) {
            if (! in_array($required, $insertColumns, true)) {
                throw new ClassificationException('employees INSERT is missing a required column.');
            }
        }
        $this->skipWsAndComments($window);
        if (! $window->startsWithKeyword('VALUES')) {
            throw new ClassificationException('Unsupported INSERT form.');
        }
        $window->consumeKeyword('VALUES');

        $rows = [];
        $expected = count($insertColumns);
        while (true) {
            $this->skipWsAndComments($window);
            $next = $window->peek(1);
            if ($next === ';') {
                $window->consume(1);
                break;
            }
            if ($next === ',') {
                $window->consume(1);
                continue;
            }
            if ($next !== '(') {
                throw new ClassificationException('Malformed employees INSERT.');
            }
            $values = $this->readTuple($window, $insertColumns, $expected);
            $rows[] = $this->keep($insertColumns, $values);
        }

        return $rows;
    }

    /** @return list<string> */
    private function columnsFromBody(string $body): array
    {
        $matched = preg_match_all(
            '/^\s*`([A-Za-z0-9_]+)`\s+(?:int|tinyint|smallint|mediumint|bigint|varchar|char|text|tinytext|mediumtext|longtext|date|datetime|timestamp|decimal|numeric|float|double|enum|set|blob|tinyblob|mediumblob|longblob|year|time|json|bit)\b/mi',
            $body,
            $matches,
        );
        if ($matched === false || $matches[1] === []) {
            throw new ClassificationException('employees column list could not be read.');
        }
        $this->assertUniqueColumns($matches[1]);

        return $matches[1];
    }

    private function readCreateBody(ByteWindow $window): string
    {
        $this->skipWsAndComments($window);
        if ($window->peek(1) !== '(') {
            throw new ClassificationException('CREATE TABLE employees has no column list.');
        }
        $window->consume(1);
        $body = '';
        $depth = 1;
        while (! $window->done()) {
            if ($this->commentOpens($window)) {
                $this->skipWsAndComments($window);
                continue;
            }
            $character = $window->peek(1);
            if ($character === "'") {
                $this->skipQuoted($window);
                continue;
            }
            if ($character === '(') {
                $depth++;
            }
            if ($character === ')') {
                $depth--;
                if ($depth === 0) {
                    $window->consume(1);

                    return $body;
                }
            }
            $body .= $character;
            $window->consume(1);
            if (strlen($body) > 2000000) {
                throw new ClassificationException('CREATE TABLE employees is too large to classify.');
            }
        }

        throw new ClassificationException('Unterminated CREATE TABLE employees.');
    }

    /** @return list<string> */
    private function readIdentifierList(ByteWindow $window): array
    {
        if ($window->peek(1) !== '(') {
            throw new ClassificationException('Malformed INSERT column list.');
        }
        $window->consume(1);
        $names = [];
        while (true) {
            $this->skipWsAndComments($window);
            if ($window->peek(1) === ')') {
                $window->consume(1);

                return $names;
            }
            if ($names !== []) {
                if ($window->peek(1) !== ',') {
                    throw new ClassificationException('Malformed INSERT column list.');
                }
                $window->consume(1);
                $this->skipWsAndComments($window);
            }
            $names[] = $this->readIdent($window);
        }
    }

    /** @param  list<string>  $columns */
    private function assertUniqueColumns(array $columns): void
    {
        if (count($columns) !== count(array_unique($columns))) {
            throw new ClassificationException('employees column list repeats a column.');
        }
    }

    /**
     * @param  list<string>  $columns
     * @return list<?string>
     */
    private function readTuple(ByteWindow $window, array $columns, int $expected): array
    {
        if ($window->peek(1) !== '(') {
            throw new ClassificationException('Malformed employees tuple.');
        }
        $window->consume(1);
        $values = [];
        for ($index = 0; $index < $expected; $index++) {
            $this->skipWsAndComments($window);
            if ($index > 0) {
                if ($window->peek(1) !== ',') {
                    throw new ClassificationException('employees tuple field count does not match the column list.');
                }
                $window->consume(1);
                $this->skipWsAndComments($window);
            }
            $keep = in_array($columns[$index], self::KEPT_COLUMNS, true);
            $values[] = $keep ? $this->readValue($window) : $this->skipValue($window);
        }
        $this->skipWsAndComments($window);
        if ($window->peek(1) === ',') {
            throw new ClassificationException('employees tuple field count does not match the column list.');
        }
        if ($window->peek(1) !== ')') {
            throw new ClassificationException('Malformed employees tuple.');
        }
        $window->consume(1);

        return $values;
    }

    /**
     * @param  list<string>  $columns
     * @param  list<?string>  $values
     * @return array<string, ?string>
     */
    private function keep(array $columns, array $values): array
    {
        if (count($columns) !== count($values)) {
            throw new ClassificationException('employees tuple field count does not match the column list.');
        }
        $associated = [];
        foreach ($columns as $index => $name) {
            if (in_array($name, self::KEPT_COLUMNS, true)) {
                $associated[$name] = $values[$index];
            }
        }
        $kept = [];
        foreach (self::KEPT_COLUMNS as $name) {
            if (! array_key_exists($name, $associated)) {
                throw new ClassificationException('employees row is missing a required column.');
            }
            $kept[$name] = $associated[$name];
        }

        return $kept;
    }

    private function readValue(ByteWindow $window): ?string
    {
        $this->skipWsAndComments($window);
        if ($window->startsWithKeyword('NULL')) {
            $window->consumeKeyword('NULL');

            return null;
        }
        $character = $window->peek(1);
        if ($character === "'") {
            return $this->readQuoted($window);
        }
        if ($this->isHexOrBit($window)) {
            throw new ClassificationException('Unsupported literal in employees INSERT.');
        }
        if ($character === '-' || ctype_digit($character)) {
            return $this->readNumber($window);
        }

        throw new ClassificationException('Unsupported employees value.');
    }

    private function skipValue(ByteWindow $window): null
    {
        $this->skipWsAndComments($window);
        if ($window->startsWithKeyword('NULL')) {
            $window->consumeKeyword('NULL');

            return null;
        }
        $character = $window->peek(1);
        if ($character === "'") {
            $this->skipQuoted($window);

            return null;
        }
        if ($this->isHexOrBit($window)) {
            throw new ClassificationException('Unsupported literal in employees INSERT.');
        }
        if ($character === '-' || ctype_digit($character)) {
            $this->readNumber($window);

            return null;
        }

        throw new ClassificationException('Unsupported employees value.');
    }

    private function isHexOrBit(ByteWindow $window): bool
    {
        $two = $window->peek(2);
        if (strlen($two) < 2) {
            return false;
        }
        if (($two[0] === '0') && ($two[1] === 'x' || $two[1] === 'X')) {
            return true;
        }

        return ($two[0] === 'b' || $two[0] === 'B' || $two[0] === 'x' || $two[0] === 'X') && $two[1] === "'";
    }

    private function readNumber(ByteWindow $window): string
    {
        $token = '';
        if ($window->peek(1) === '-') {
            $token = '-';
            $window->consume(1);
        }
        if (! ctype_digit($window->peek(1))) {
            throw new ClassificationException('Malformed numeric value.');
        }
        while (ctype_digit($window->peek(1))) {
            $token .= $window->peek(1);
            $window->consume(1);
        }
        if ($window->peek(1) === '.') {
            $token .= '.';
            $window->consume(1);
            if (! ctype_digit($window->peek(1))) {
                throw new ClassificationException('Malformed numeric value.');
            }
            while (ctype_digit($window->peek(1))) {
                $token .= $window->peek(1);
                $window->consume(1);
            }
        }

        return $token;
    }

    private function readQuoted(ByteWindow $window): string
    {
        if ($window->peek(1) !== "'") {
            throw new ClassificationException('Expected a quoted string.');
        }
        $window->consume(1);
        $value = '';
        while (! $window->done()) {
            $character = $window->peek(1);
            $window->consume(1);
            if ($character === '\\') {
                if ($window->done()) {
                    throw new ClassificationException('Unterminated string.');
                }
                $escaped = $window->peek(1);
                $window->consume(1);
                $value .= match ($escaped) {
                    '0' => "\0",
                    'b' => "\x08",
                    'n' => "\n",
                    'r' => "\r",
                    't' => "\t",
                    'Z' => "\x1A",
                    default => $escaped,
                };
                continue;
            }
            if ($character === "'") {
                if ($window->peek(1) === "'") {
                    $window->consume(1);
                    $value .= "'";
                    continue;
                }

                return $value;
            }
            $value .= $character;
        }

        throw new ClassificationException('Unterminated string.');
    }

    private function skipQuoted(ByteWindow $window): void
    {
        if ($window->peek(1) !== "'") {
            throw new ClassificationException('Expected a quoted string.');
        }
        $window->consume(1);
        while (! $window->done()) {
            $character = $window->peek(1);
            $window->consume(1);
            if ($character === '\\') {
                if ($window->done()) {
                    throw new ClassificationException('Unterminated string.');
                }
                $window->consume(1);
                continue;
            }
            if ($character === "'") {
                if ($window->peek(1) === "'") {
                    $window->consume(1);
                    continue;
                }

                return;
            }
        }

        throw new ClassificationException('Unterminated string.');
    }

    private function readTableName(ByteWindow $window): string
    {
        $this->skipWsAndComments($window);
        $name = $this->readIdent($window);
        $this->skipWsAndComments($window);
        if ($window->peek(1) === '.') {
            $window->consume(1);
            $this->skipWsAndComments($window);
            $name = $this->readIdent($window);
        }

        return $name;
    }

    private function readIdent(ByteWindow $window): string
    {
        $quoted = $window->peek(1) === '`';
        if ($quoted) {
            $window->consume(1);
        }
        $name = '';
        while (! $window->done()) {
            $character = $window->peek(1);
            if ($character === '`' || $character === ' ' || $character === "\n" || $character === "\r" || $character === "\t" || $character === '(' || $character === ')' || $character === ',' || $character === '.') {
                break;
            }
            $name .= $character;
            $window->consume(1);
        }
        if ($name === '') {
            throw new ClassificationException('Malformed SQL identifier.');
        }
        if ($quoted) {
            if ($window->peek(1) !== '`') {
                throw new ClassificationException('Malformed SQL identifier.');
            }
            $window->consume(1);
        }

        return $name;
    }

    private function consumeStatement(ByteWindow $window): void
    {
        while (! $window->done()) {
            if ($this->commentOpens($window)) {
                $this->skipWsAndComments($window);
                continue;
            }
            $character = $window->peek(1);
            if ($character === "'") {
                $this->skipQuoted($window);
                continue;
            }
            if ($character === '`') {
                $this->readIdent($window);
                continue;
            }
            if ($character === ';') {
                $window->consume(1);

                return;
            }
            $window->consume(1);
        }

        throw new ClassificationException('Unterminated SQL statement.');
    }

    private function skipWsAndComments(ByteWindow $window): void
    {
        while (! $window->done()) {
            $character = $window->peek(1);
            if ($character === ' ' || $character === "\n" || $character === "\r" || $character === "\t" || $character === "\f") {
                $window->consume(1);
                continue;
            }
            $two = $window->peek(2);
            if ($two === '--' || $character === '#') {
                while (! $window->done() && $window->peek(1) !== "\n") {
                    $window->consume(1);
                }
                continue;
            }
            if ($two === '/*') {
                $window->consume(2);
                while (! $window->done() && $window->peek(2) !== '*/') {
                    $window->consume(1);
                }
                if ($window->peek(2) !== '*/') {
                    throw new ClassificationException('Unterminated comment.');
                }
                $window->consume(2);
                continue;
            }

            return;
        }
    }

    private function commentOpens(ByteWindow $window): bool
    {
        $two = $window->peek(2);

        return $two === '--' || $two === '/*' || $window->peek(1) === '#';
    }
}
