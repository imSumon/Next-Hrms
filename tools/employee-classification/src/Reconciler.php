<?php

declare(strict_types=1);

namespace Hrms\EmployeeClassification;

final class Reconciler
{
    public function assertConsistent(array $report): void
    {
        if (($report['target_database_write'] ?? true) !== false) {
            throw new ClassificationException('Target database write is not allowed.');
        }
        $final = $report['final_load_contract'] ?? [];
        if (($final['executed'] ?? true) !== false || ($final['loaded'] ?? 1) !== 0) {
            throw new ClassificationException('The final load equation has not been executed.');
        }
        if (($final['equation'] ?? '') !== 'SOURCE COUNT = LOADED + APPROVED EXCEPTIONS') {
            throw new ClassificationException('Final load equation text is missing.');
        }

        $employees = $report['employee_classifications'] ?? null;
        $episodes = $report['episode_classifications'] ?? null;
        if (! is_array($employees) || ! is_array($episodes)) {
            throw new ClassificationException('Classification lists are missing.');
        }
        $this->assertOneEach($employees, 'employee');
        $this->assertOneEach($episodes, 'episode');
        $this->assertPairs($employees, $episodes);
        $this->assertSafety($report, $employees, $episodes);

        $companies = $report['companies'] ?? null;
        if (! is_array($companies)) {
            throw new ClassificationException('Reconciliation summary is missing.');
        }
        $seenCompanies = [];
        foreach ($companies as $company) {
            $id = (int) ($company['catalog_company_id'] ?? 0);
            if (isset($seenCompanies[$id])) {
                throw new ClassificationException('A company summary is repeated.');
            }
            $seenCompanies[$id] = true;
            $employeeRows = $this->forCompany($employees, $id);
            $episodeRows = $this->forCompany($episodes, $id);
            if (count($employeeRows) !== count($episodeRows)) {
                throw new ClassificationException('Employee and episode counts differ inside one company.');
            }
            $this->assertEquation($company['employees'] ?? [], $this->equation($employeeRows), 'employees');
            $this->assertEquation($company['episodes'] ?? [], $this->equation($episodeRows), 'episodes');
            $this->assertSameHash($company['employees']['classification_sha256'] ?? '', $employeeRows, 'employees');
            $this->assertSameHash($company['episodes']['classification_sha256'] ?? '', $episodeRows, 'episodes');
            if ((int) ($company['parsed_row_count'] ?? -1) !== count($employeeRows) || count($employeeRows) !== count($episodeRows)) {
                throw new ClassificationException('Parsed row count does not match the classifications.');
            }
            $groupRows = array_values(array_filter(
                is_array($report['exception_groups'] ?? null) ? $report['exception_groups'] : [],
                fn (array $group): bool => (int) ($group['catalog_company_id'] ?? 0) === $id,
            ));
            $this->assertSameHash($company['exception_groups_sha256'] ?? '', $groupRows, 'exception groups');
            if (($report['mode'] ?? '') === 'gates_closed') {
                if (($company['employees']['proposed_load'] ?? 1) !== 0 || ($company['episodes']['proposed_load'] ?? 1) !== 0) {
                    throw new ClassificationException('gates_closed emitted a proposed load.');
                }
            }
        }

        foreach ($employees as $row) {
            $id = (int) $row['catalog_company_id'];
            if (! isset($seenCompanies[$id])) {
                throw new ClassificationException('A source row has no company summary.');
            }
        }
        $this->assertGroups($report, $employees);
    }

    /** @param  list<array<string, mixed>>  $rows */
    private function assertOneEach(array $rows, string $label): void
    {
        $seen = [];
        foreach ($rows as $row) {
            if (! isset($row['disposition'], $row['source_pk'], $row['catalog_company_id'])) {
                throw new ClassificationException('A '.$label.' row has no classification.');
            }
            $key = $row['catalog_company_id']."\0".$row['source_pk'];
            if (isset($seen[$key])) {
                throw new ClassificationException('A source row has two '.$label.' classifications.');
            }
            $seen[$key] = true;
        }
    }

    /** @param  list<array<string, mixed>>  $employees
     * @param  list<array<string, mixed>>  $episodes
     */
    private function assertPairs(array $employees, array $episodes): void
    {
        $episodeKeys = [];
        foreach ($episodes as $row) {
            $episodeKeys[$row['catalog_company_id']."\0".$row['source_pk']] = true;
        }
        foreach ($employees as $row) {
            $key = $row['catalog_company_id']."\0".$row['source_pk'];
            if (! isset($episodeKeys[$key])) {
                throw new ClassificationException('A source row has no episode classification.');
            }
            unset($episodeKeys[$key]);
        }
        if ($episodeKeys !== []) {
            throw new ClassificationException('An episode classification has no employee row.');
        }
    }

    /** @param  list<array<string, mixed>>  $employees
     * @param  list<array<string, mixed>>  $episodes
     */
    private function assertSafety(array $report, array $employees, array $episodes): void
    {
        $gates = $report['gates'] ?? [];
        foreach ($employees as $row) {
            if (($gates['d1'] ?? '') === 'proposed' && ($row['display_name_candidate'] ?? null) !== '') {
                throw new ClassificationException('Display name is nonempty while D1 is proposed.');
            }
            if (($row['disposition'] ?? '') === 'approved_exception' && ($row['exception_id'] ?? '') === '') {
                throw new ClassificationException('An approved exception has no signed exception id.');
            }
            $this->assertReasons($row['reason_codes'] ?? []);
            if (($row['mapping_status'] ?? '') === 'proposed_loadable' && ($row['target_company_ulid'] ?? '') === '') {
                throw new ClassificationException('A proposed load has no company ULID.');
            }
        }
        $employeeLoadable = [];
        foreach ($employees as $row) {
            $employeeLoadable[$row['catalog_company_id']."\0".$row['source_pk']] = $row['mapping_status'] === 'proposed_loadable';
        }
        $loadableCodes = [];
        foreach ($employees as $row) {
            if ($row['mapping_status'] !== 'proposed_loadable') {
                continue;
            }
            $codeKey = $row['catalog_company_id']."\0".$row['source_emp_id_no'];
            if (isset($loadableCodes[$codeKey])) {
                throw new ClassificationException('Two proposed-loadable rows share an employee code.');
            }
            $loadableCodes[$codeKey] = true;
        }
        foreach ($episodes as $row) {
            if (($row['episode_candidate_status'] ?? '') === 'inactive') {
                throw new ClassificationException('inactive is not an episode outcome.');
            }
            if (($row['flag_pair'] ?? '') === '1/1' && ($row['episode_candidate_status'] ?? '') !== '') {
                throw new ClassificationException('A status was emitted from both flags being 1.');
            }
            foreach (['proposed_target_joining_date', 'proposed_target_last_working_date'] as $field) {
                if (($row[$field] ?? null) === '0000-00-00') {
                    throw new ClassificationException('A zero date was copied into a proposed target date.');
                }
            }
            $key = $row['catalog_company_id']."\0".$row['source_pk'];
            if (($row['mapping_status'] ?? '') === 'proposed_loadable' && ! ($employeeLoadable[$key] ?? false)) {
                throw new ClassificationException('An episode cannot be proposed-loadable when its employee is not.');
            }
            $this->assertReasons($row['reason_codes'] ?? []);
        }
    }

    /** @param  list<string>  $reasons */
    private function assertReasons(array $reasons): void
    {
        foreach ($reasons as $reason) {
            if ($reason === 'dis' || str_starts_with($reason, 'dis_')) {
                throw new ClassificationException('dis was used as a deciding reason.');
            }
        }
    }

    /** @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function forCompany(array $rows, int $catalogId): array
    {
        return array_values(array_filter(
            $rows,
            fn (array $row): bool => (int) $row['catalog_company_id'] === $catalogId,
        ));
    }

    /** @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public function equation(array $rows): array
    {
        $proposed = 0;
        $quarantined = 0;
        $approved = 0;
        $unexplained = 0;
        $dispositions = [];
        foreach ($rows as $row) {
            $disposition = (string) ($row['disposition'] ?? '');
            $dispositions[$disposition] = ($dispositions[$disposition] ?? 0) + 1;
            if ($disposition === 'approved_exception') {
                if (($row['mapping_status'] ?? '') === 'proposed_loadable') {
                    throw new ClassificationException('An approved exception was marked proposed-loadable.');
                }
                $approved++;
                continue;
            }
            if (($row['mapping_status'] ?? '') === 'proposed_loadable') {
                if ($disposition !== 'classified_only') {
                    throw new ClassificationException('A non-loadable disposition was marked proposed-loadable.');
                }
                $proposed++;
                continue;
            }
            if (in_array($disposition, ['quarantined', 'classified_only', 'no_episode_evidence'], true)) {
                $quarantined++;
                continue;
            }
            $unexplained++;
        }
        $source = count($rows);
        if ($proposed + $quarantined + $approved + $unexplained !== $source) {
            throw new ClassificationException('Reconciliation arithmetic does not cover every source row.');
        }

        return [
            'equation' => 'SOURCE COUNT = PROPOSED LOAD + QUARANTINED + APPROVED EXCEPTIONS',
            'source_count' => $source,
            'proposed_load' => $proposed,
            'quarantined' => $quarantined,
            'approved_exceptions' => $approved,
            'unexplained' => $unexplained,
            'loaded' => 0,
            'disposition_counts' => $dispositions,
            'quarantined_term_includes' => [
                'quarantined',
                'classified_only when it is not a proposed load',
                'no_episode_evidence',
            ],
        ];
    }

    /** @param  array<string, mixed>  $actual
     * @param  array<string, mixed>  $expected
     */
    private function assertEquation(array $actual, array $expected, string $label): void
    {
        foreach (['source_count', 'proposed_load', 'quarantined', 'approved_exceptions', 'unexplained', 'loaded', 'disposition_counts'] as $field) {
            if (($actual[$field] ?? null) !== $expected[$field]) {
                throw new ClassificationException('Second-pass '.$label.' '.$field.' does not match.');
            }
        }
        if (($actual['unexplained'] ?? 1) !== 0) {
            throw new ClassificationException('Unexplained '.$label.' rows must be zero.');
        }
        if (($expected['source_count']) !== $expected['proposed_load'] + $expected['quarantined'] + $expected['approved_exceptions']) {
            throw new ClassificationException('Dry-run equation does not balance for '.$label.'.');
        }
    }

    /** @param  list<array<string, mixed>>  $employees */
    private function assertGroups(array $report, array $employees): void
    {
        $groups = $report['exception_groups'] ?? [];
        if (! is_array($groups)) {
            throw new ClassificationException('Exception groups are missing.');
        }
        $expected = [];
        foreach ($employees as $row) {
            $code = $row['source_emp_id_no'];
            if ($code === null || $code === '') {
                continue;
            }
            $key = $row['catalog_company_id']."\0".bin2hex((string) $code);
            $expected[$key]['code'] = $code;
            $expected[$key]['company'] = (int) $row['catalog_company_id'];
            $expected[$key]['pks'][] = (string) $row['source_pk'];
        }
        $expected = array_filter($expected, fn (array $group): bool => count($group['pks']) > 1);
        if (count($groups) !== count($expected)) {
            throw new ClassificationException('A duplicate group does not match the parse.');
        }
        foreach ($groups as $group) {
            if (($group['authorization'] ?? null) !== '') {
                throw new ClassificationException('A duplicate group has an authorization that was not supplied.');
            }
            $key = $group['catalog_company_id']."\0".bin2hex((string) $group['source_emp_id_no']);
            if (! isset($expected[$key])) {
                throw new ClassificationException('A duplicate group was not found in the parse.');
            }
            $members = $expected[$key]['pks'];
            sort($members);
            $listed = $group['member_source_pks'];
            sort($listed);
            if ($listed !== $members || (int) $group['member_count'] !== count($members)) {
                throw new ClassificationException('A duplicate group lists fewer members than the parse found.');
            }
        }
    }

    /** @param  list<array<string, mixed>>  $rows */
    public function classificationSha256(array $rows): string
    {
        $lines = [];
        foreach ($rows as $row) {
            $lines[] = json_encode([
                'catalog_company_id' => (int) ($row['catalog_company_id'] ?? 0),
                'source_pk' => (string) ($row['source_pk'] ?? ''),
                'source_emp_id_no' => $row['source_emp_id_no'] ?? null,
                'disposition' => (string) ($row['disposition'] ?? ''),
                'mapping_status' => (string) ($row['mapping_status'] ?? ''),
                'reason_codes' => array_values($row['reason_codes'] ?? []),
                'display_name_candidate' => $row['display_name_candidate'] ?? null,
                'episode_candidate_status' => $row['episode_candidate_status'] ?? null,
                'flag_pair' => $row['flag_pair'] ?? null,
                'joining_date_class' => $row['joining_date_class'] ?? null,
                'discontinue_date_class' => $row['discontinue_date_class'] ?? null,
                'proposed_employee_ulid' => $row['proposed_employee_ulid'] ?? null,
                'proposed_episode_ulid' => $row['proposed_episode_ulid'] ?? null,
                'group_id' => $row['group_id'] ?? null,
                'member_source_pks' => $row['member_source_pks'] ?? null,
                'member_count' => $row['member_count'] ?? null,
                'authorization' => array_key_exists('authorization', $row) ? $row['authorization'] : null,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        }

        return hash('sha256', implode("\n", $lines));
    }

    /** @param  list<array<string, mixed>>  $rows */
    private function assertSameHash(string $stored, array $rows, string $label): void
    {
        if ($stored === '' || ! hash_equals($stored, $this->classificationSha256($rows))) {
            throw new ClassificationException('Second-pass '.$label.' classification does not match.');
        }
    }
}
