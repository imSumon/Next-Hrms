<?php

declare(strict_types=1);

namespace Hrms\EmployeeClassification;

final class Runner
{
    public function __construct(
        private readonly Safeguard $safeguard = new Safeguard,
        private readonly Fingerprint $fingerprint = new Fingerprint,
        private readonly DumpParser $parser = new DumpParser,
        private readonly Reconciler $reconciler = new Reconciler,
    ) {}

    /**
     * Classify rows already read by the parser. This does not open a dump or a database.
     *
     * @param  array<string, mixed>  $meta
     * @param  list<array<string, ?string>>  $rows
     * @return array<string, mixed>
     */
    public function fromRows(array $meta, array $rows, ?GateSet $gates = null): array
    {
        $gates ??= GateSet::closed();
        if (($meta['signed_exceptions'] ?? []) !== []) {
            throw new ClassificationException('No signed exception register is approved.');
        }
        $meta['parsed_row_count'] = count($rows);
        $classified = (new Classifier($gates))->classify($meta, $rows);

        return $this->finish([$classified], $gates, [$meta]);
    }

    /**
     * Production entry. Not invoked by the test suite or by application startup.
     */
    public function runApprovedExtracts(string $projectRoot, string $outputPath): string
    {
        return $this->writeClassification($projectRoot, $outputPath, GateSet::closed(), null, null);
    }

    public function classifyCatalog(string $projectRoot, string $outputPath, GateSet $gates, int $catalogCompanyId, string $authorizationNote): string
    {
        if ($gates->closed) {
            throw new ClassificationException('This classification needs explicit gates.');
        }
        if ($gates->d4 !== 'proposed') {
            throw new ClassificationException('D4 stays proposed. Duplicate-code groups are not authorized.');
        }
        if (! in_array($catalogCompanyId, [24, 20, 10], true)) {
            throw new ClassificationException('Catalog company is outside the approved crosswalk.');
        }

        return $this->writeClassification($projectRoot, $outputPath, $gates, $catalogCompanyId, $authorizationNote);
    }

    private function writeClassification(string $projectRoot, string $outputPath, GateSet $gates, ?int $catalogCompanyId, ?string $authorizationNote): string
    {
        $config = Manifest::config();
        if (($config['signed_exceptions'] ?? []) !== []) {
            throw new ClassificationException('No signed exception register is approved.');
        }
        if (($config['mode'] ?? '') !== 'gates_closed') {
            throw new ClassificationException('The manifest mode stays gates_closed. Explicit gates are an execution input.');
        }
        $this->safeguard->assertNoForbiddenTarget($config);
        $this->safeguard->assertNoForbiddenTarget($projectRoot, 'project root');
        $applicationRoot = dirname(__DIR__, 3);
        $destination = $this->safeguard->assertOutputPath($outputPath, $applicationRoot, $projectRoot);

        $classified = [];
        $metas = [];
        foreach (Manifest::sources() as $source) {
            if ($catalogCompanyId !== null && (int) $source['catalog_company_id'] !== $catalogCompanyId) {
                continue;
            }
            $path = Manifest::resolve($projectRoot, (string) $source['relative_path']);
            $stream = $this->fingerprint->openVerified(
                $path,
                (int) $source['bytes'],
                (string) $source['sha256'],
                (bool) $source['compressed'],
            );
            try {
                $parsed = $this->parser->parse($stream, (string) $source['header_database']);
            } finally {
                $stream->close();
            }
            $meta = $this->metaFromSource($source, (string) $source['sha256']);
            $meta['parsed_row_count'] = count($parsed['rows']);
            $classified[] = (new Classifier($gates))->classify($meta, $parsed['rows']);
            $metas[] = $meta;
        }
        if ($classified === []) {
            throw new ClassificationException('No manifest source matched the requested catalog company.');
        }

        $report = $this->finish($classified, $gates, $metas);
        if ($authorizationNote !== null) {
            $report['decision_record'] = [
                'step_20r_updated' => false,
                'company_migration_approved' => false,
                'note' => $authorizationNote,
            ];
        }
        $encoded = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        (new ReportPublisher)->publish($destination, $encoded);

        return $destination;
    }

    /** @param  array<string, mixed>  $source */
    private function metaFromSource(array $source, string $sha256): array
    {
        $catalogId = (int) $source['catalog_company_id'];

        return [
            'catalog_company_id' => $catalogId,
            'alias' => $source['alias'],
            'source_database' => $source['source_database'],
            'source_system' => $source['source_system'],
            'source_file_sha256' => strtoupper($sha256),
            'target_company_ulid' => Manifest::companyUlids()[$catalogId] ?? '',
            'relative_path' => $source['relative_path'],
            'signed_exceptions' => [],
        ];
    }

    /**
     * @param  list<array{employees: list<array<string, mixed>>, episodes: list<array<string, mixed>>, groups: list<array<string, mixed>>}>  $classified
     * @param  list<array<string, mixed>>  $metas
     * @return array<string, mixed>
     */
    private function finish(array $classified, GateSet $gates, array $metas): array
    {
        $employees = [];
        $episodes = [];
        $groups = [];
        foreach ($classified as $one) {
            $employees = array_merge($employees, $one['employees']);
            $episodes = array_merge($episodes, $one['episodes']);
            $groups = array_merge($groups, $one['groups']);
        }

        $companies = [];
        $seen = [];
        foreach ($metas as $meta) {
            $id = (int) $meta['catalog_company_id'];
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $employeeRows = array_values(array_filter($employees, fn (array $row): bool => (int) $row['catalog_company_id'] === $id));
            $episodeRows = array_values(array_filter($episodes, fn (array $row): bool => (int) $row['catalog_company_id'] === $id));
            if (count($employeeRows) !== (int) ($meta['parsed_row_count'] ?? -1) || count($episodeRows) !== count($employeeRows)) {
                throw new ClassificationException('Classification count does not match the parsed employee rows.');
            }
            $groupRows = array_values(array_filter($groups, fn (array $group): bool => (int) $group['catalog_company_id'] === $id));
            $employeeEquation = $this->reconciler->equation($employeeRows);
            $employeeEquation['distinct_non_empty_codes'] = $this->distinctCodes($employeeRows);
            $employeeEquation['classification_sha256'] = $this->reconciler->classificationSha256($employeeRows);
            $episodeEquation = $this->reconciler->equation($episodeRows);
            $episodeEquation['classification_sha256'] = $this->reconciler->classificationSha256($episodeRows);
            $companies[] = [
                'catalog_company_id' => $id,
                'target_company_ulid' => $meta['target_company_ulid'] ?? '',
                'company_row_is_migration_approval' => false,
                'parsed_row_count' => (int) $meta['parsed_row_count'],
                'employees' => $employeeEquation,
                'episodes' => $episodeEquation,
                'exception_groups_sha256' => $this->reconciler->classificationSha256($groupRows),
            ];
        }

        $report = [
            'run_id' => bin2hex(random_bytes(8)),
            'classified_at' => gmdate('c'),
            'mode' => $gates->closed ? 'gates_closed' : 'explicit_gates',
            'gates' => [
                'd1' => $gates->d1,
                'd2' => $gates->d2,
                'd3' => $gates->d3,
                'd4' => $gates->d4,
                'd6' => $gates->d6,
            ],
            'target_database_write' => false,
            'catalog_fingerprint' => null,
            'catalog_fingerprint_note' => 'The master catalog dump is not an approved classification input. Company identity uses the Step 20J crosswalk and the control-center ULIDs.',
            'parser_limitations' => DumpParser::LIMITATIONS,
            'source_fingerprints' => array_map(fn (array $meta): array => [
                'relative_path' => $meta['relative_path'] ?? '',
                'sha256' => $meta['source_file_sha256'] ?? '',
                'catalog_company_id' => (int) $meta['catalog_company_id'],
            ], $metas),
            'dry_run_equation' => 'SOURCE COUNT = PROPOSED LOAD + QUARANTINED + APPROVED EXCEPTIONS',
            'final_load_contract' => [
                'equation' => 'SOURCE COUNT = LOADED + APPROVED EXCEPTIONS',
                'executed' => false,
                'loaded' => 0,
                'result' => 'not executed',
            ],
            'companies' => $companies,
            'employee_classifications' => $employees,
            'episode_classifications' => $episodes,
            'exception_groups' => $groups,
        ];

        $decoded = json_decode(json_encode($report, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        $this->reconciler->assertConsistent($decoded);
        $decoded['second_pass'] = 'matched';
        $this->reconciler->assertConsistent($decoded);

        return $decoded;
    }

    /** @param  list<array<string, mixed>>  $rows */
    private function distinctCodes(array $rows): int
    {
        $codes = [];
        foreach ($rows as $row) {
            $code = $row['source_emp_id_no'];
            if ($code === null || $code === '') {
                continue;
            }
            $codes[bin2hex((string) $code)] = true;
        }

        return count($codes);
    }
}
