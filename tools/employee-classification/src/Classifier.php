<?php

declare(strict_types=1);

namespace Hrms\EmployeeClassification;

final class GateSet
{
    private function __construct(
        public readonly bool $closed,
        public readonly string $d1,
        public readonly string $d2,
        public readonly string $d3,
        public readonly string $d4,
        public readonly string $d6,
    ) {
    }

    public static function closed(): self
    {
        return new self(true, 'proposed', 'proposed', 'proposed', 'proposed', 'proposed');
    }

    /** @param  array<string, string>  $gates */
    public static function explicit(array $gates): self
    {
        foreach (['d1', 'd2', 'd3', 'd4', 'd6'] as $name) {
            $value = $gates[$name] ?? '';
            if ($value !== 'proposed' && $value !== 'approved') {
                throw new ClassificationException('Gate '.$name.' must be proposed or approved.');
            }
        }

        return new self(false, $gates['d1'], $gates['d2'], $gates['d3'], $gates['d4'], $gates['d6']);
    }
}

final class Classifier
{
    private const ULID = '/^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$/';

    public function __construct(private readonly GateSet $gates)
    {
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  list<array<string, ?string>>  $rows
     * @return array{employees: list<array<string, mixed>>, episodes: list<array<string, mixed>>, groups: list<array<string, mixed>>}
     */
    public function classify(array $meta, array $rows): array
    {
        if ($this->gates->closed) {
            foreach (['d1', 'd2', 'd3', 'd4', 'd6'] as $gate) {
                if ($this->gates->{$gate} !== 'proposed') {
                    throw new ClassificationException('gates_closed cannot approve a gate.');
                }
            }
        }

        $ulid = (string) ($meta['target_company_ulid'] ?? '');
        if ($ulid !== '' && preg_match(self::ULID, $ulid) !== 1) {
            throw new ClassificationException('Company ULID does not match the target check.');
        }
        $catalogId = (int) $meta['catalog_company_id'];
        $known = Manifest::companyUlids()[$catalogId] ?? null;
        if ($ulid !== '' && $known !== null && ! hash_equals($known, $ulid)) {
            throw new ClassificationException('Company ULID is not the seeded crosswalk value.');
        }

        $seen = [];
        foreach ($rows as $row) {
            $pk = $row['id'];
            if ($pk === null || $pk === '') {
                throw new ClassificationException('A source primary key is null.');
            }
            if (isset($seen[$pk])) {
                throw new ClassificationException('A source primary key is repeated inside one dump.');
            }
            $seen[$pk] = true;
        }

        $duplicateKeys = $this->duplicateKeys($rows);
        $employees = [];
        $episodes = [];
        foreach ($rows as $row) {
            $code = $row['emp_id_no'];
            $duplicate = $code !== null && $code !== '' && isset($duplicateKeys[$this->bucket($code)]);
            $employee = $this->classifyEmployee($meta, $row, $ulid, $duplicate);
            $episode = $this->classifyEpisode($meta, $row, $employee['mapping_status'] === 'proposed_loadable');
            if ($episode['episode_candidate_status'] === 'inactive') {
                throw new ClassificationException('inactive is not an episode outcome.');
            }
            if ($episode['mapping_status'] === 'proposed_loadable' && $employee['mapping_status'] !== 'proposed_loadable') {
                throw new ClassificationException('An episode cannot be proposed-loadable when its employee is not.');
            }
            $employees[] = $employee;
            $episodes[] = $episode;
        }

        return [
            'employees' => $employees,
            'episodes' => $episodes,
            'groups' => $this->groups($meta, $rows, $duplicateKeys),
        ];
    }

    /** @param  list<array<string, ?string>>  $rows
     * @return array<string, true>
     */
    private function duplicateKeys(array $rows): array
    {
        $counts = [];
        foreach ($rows as $row) {
            $code = $row['emp_id_no'];
            if ($code === null || $code === '') {
                continue;
            }
            $key = $this->bucket($code);
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }
        $duplicates = [];
        foreach ($counts as $key => $count) {
            if ($count > 1) {
                $duplicates[$key] = true;
            }
        }

        return $duplicates;
    }

    private function bucket(string $code): string
    {
        return bin2hex($code);
    }

    /** @param  array<string, mixed>  $meta
     * @param  array<string, ?string>  $row
     * @return array<string, mixed>
     */
    private function classifyEmployee(array $meta, array $row, string $ulid, bool $duplicate): array
    {
        $reasons = [];
        $disposition = 'classified_only';
        if ($this->gates->d1 !== 'approved') {
            $display = '';
            $reasons[] = 'name_rule_proposed';
        } else {
            $parts = [];
            foreach (['emp_first_name', 'emp_mid_name', 'emp_last_name'] as $column) {
                $part = $row[$column];
                if ($part === null) {
                    continue;
                }
                $trimmed = trim($part);
                if ($trimmed !== '') {
                    $parts[] = $trimmed;
                }
            }
            $display = implode(' ', $parts);
            if ($display === '') {
                $disposition = 'quarantined';
                $reasons[] = 'name_empty';
            }
        }
        if ($row['emp_id_no'] === null || $row['emp_id_no'] === '') {
            $disposition = 'quarantined';
            $reasons[] = 'source_code_missing';
        }
        if ($duplicate) {
            $disposition = 'quarantined';
            $reasons[] = 'duplicate_code_group';
        }
        if ($this->gates->d1 === 'proposed' && $display !== '') {
            throw new ClassificationException('Display name is nonempty while D1 is proposed.');
        }

        $loadable = $this->gates->d1 === 'approved'
            && $display !== ''
            && $disposition !== 'quarantined'
            && $ulid !== ''
            && ! $duplicate;

        return [
            'catalog_company_id' => (int) $meta['catalog_company_id'],
            'target_company_ulid' => $ulid,
            'company_row_is_migration_approval' => false,
            'source_system' => $meta['source_system'],
            'source_database' => $meta['source_database'],
            'source_table' => 'employees',
            'source_pk' => (string) $row['id'],
            'source_file_sha256' => $meta['source_file_sha256'],
            'source_emp_id_no' => $row['emp_id_no'],
            'name_component_classes' => [
                'emp_first_name' => $this->componentClass($row['emp_first_name']),
                'emp_mid_name' => $this->componentClass($row['emp_mid_name']),
                'emp_last_name' => $this->componentClass($row['emp_last_name']),
            ],
            'display_name_candidate' => $display,
            'proposed_employee_ulid' => $this->ulid(),
            'mapping_status' => $loadable ? 'proposed_loadable' : 'proposed_not_loadable',
            'disposition' => $disposition,
            'reason_codes' => array_values(array_unique($reasons)),
            'exception_id' => '',
        ];
    }

    /** @param  array<string, mixed>  $meta
     * @param  array<string, ?string>  $row
     * @return array<string, mixed>
     */
    private function classifyEpisode(array $meta, array $row, bool $employeeLoadable): array
    {
        $joining = $this->dateClass($row['joining_date']);
        $leaving = $this->dateClass($row['discontinue_date']);
        $active = (string) $row['active'];
        $discontinued = (string) $row['disconrinue'];
        $reasons = [];
        $candidate = '';
        $disposition = 'classified_only';

        if ($joining === 'zero' || $leaving === 'zero') {
            $reasons[] = 'zero_date_rule_proposed';
        }
        if ($joining === 'other' || $leaving === 'other') {
            $reasons[] = 'date_not_calendar';
        }

        $bothTrue = $active === '1' && $discontinued === '1';
        $bothFalse = $active === '0' && $discontinued === '0';
        $openPair = $active === '1' && $discontinued === '0' && $joining === 'calendar';
        $separatedPair = $active === '0' && $discontinued === '1' && $joining === 'calendar' && $leaving === 'calendar';
        $separatedUnresolved = $active === '0' && $discontinued === '1' && $leaving !== 'calendar';

        if ($bothTrue) {
            $disposition = 'quarantined';
            $reasons[] = 'both_flags_true';
        } elseif ($bothFalse) {
            $disposition = 'no_episode_evidence';
            $reasons[] = 'flag_pair_no_status';
        } elseif ($separatedUnresolved) {
            $disposition = 'quarantined';
            $reasons[] = 'leaving_date_not_calendar';
        } elseif ($openPair) {
            $candidate = 'Open';
            if ($this->gates->d6 !== 'approved') {
                $reasons[] = 'episode_rule_proposed';
            }
        } elseif ($separatedPair) {
            $candidate = 'separated';
            if ($this->gates->d6 !== 'approved' || $this->gates->d2 !== 'approved') {
                $reasons[] = 'episode_rule_proposed';
            }
        } elseif ($active === '1' && $discontinued === '0') {
            $disposition = 'quarantined';
            $reasons[] = $joining === 'null' ? 'joining_date_null' : 'joining_date_not_calendar';
        } else {
            $disposition = 'quarantined';
            $reasons[] = 'flag_pair_unmapped';
        }

        if ($candidate === 'inactive' || ($bothTrue && $candidate !== '')) {
            throw new ClassificationException('inactive or a both-true status was emitted.');
        }

        $loadable = $candidate !== ''
            && $disposition === 'classified_only'
            && $employeeLoadable
            && $this->gates->d6 === 'approved'
            && ($candidate !== 'separated' || $this->gates->d2 === 'approved');

        $proposedJoining = $loadable && $joining === 'calendar' ? $row['joining_date'] : null;
        $proposedLeaving = $loadable && $candidate === 'separated' && $leaving === 'calendar' ? $row['discontinue_date'] : null;
        if ($proposedJoining === '0000-00-00' || $proposedLeaving === '0000-00-00') {
            throw new ClassificationException('A zero date was copied into a proposed target date.');
        }

        return [
            'catalog_company_id' => (int) $meta['catalog_company_id'],
            'target_company_ulid' => $meta['target_company_ulid'],
            'source_system' => $meta['source_system'],
            'source_database' => $meta['source_database'],
            'source_table' => 'employees',
            'source_pk' => (string) $row['id'],
            'source_file_sha256' => $meta['source_file_sha256'],
            'source_emp_id_no' => $row['emp_id_no'],
            'flag_pair' => $active.'/'.$discontinued,
            'joining_date_class' => $joining,
            'discontinue_date_class' => $leaving,
            'source_joining_date' => $row['joining_date'],
            'source_discontinue_date' => $row['discontinue_date'],
            'episode_candidate_status' => $candidate,
            'proposed_target_joining_date' => $proposedJoining,
            'proposed_target_last_working_date' => $proposedLeaving,
            'proposed_episode_ulid' => $candidate !== '' ? $this->ulid() : '',
            'mapping_status' => $loadable ? 'proposed_loadable' : 'proposed_not_loadable',
            'disposition' => $disposition,
            'reason_codes' => array_values(array_unique($reasons)),
            'exception_id' => '',
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  list<array<string, ?string>>  $rows
     * @param  array<string, true>  $duplicateKeys
     * @return list<array<string, mixed>>
     */
    private function groups(array $meta, array $rows, array $duplicateKeys): array
    {
        $members = [];
        foreach ($rows as $row) {
            $code = $row['emp_id_no'];
            if ($code === null || $code === '' || ! isset($duplicateKeys[$this->bucket($code)])) {
                continue;
            }
            $members[$this->bucket($code)]['code'] = $code;
            $members[$this->bucket($code)]['pks'][] = (string) $row['id'];
        }

        $groups = [];
        foreach ($members as $member) {
            $pks = $member['pks'];
            usort($pks, function (string $left, string $right): int {
                if (ctype_digit($left) && ctype_digit($right)) {
                    return $left <=> $right;
                }

                return strcmp($left, $right);
            });
            $groups[] = [
                'group_id' => $meta['alias'].':emp_id_no:'.$member['code'],
                'catalog_company_id' => (int) $meta['catalog_company_id'],
                'source_emp_id_no' => $member['code'],
                'member_source_pks' => $pks,
                'member_count' => count($pks),
                'authorization' => '',
            ];
        }
        usort($groups, fn (array $left, array $right): int => strcmp($left['group_id'], $right['group_id']));

        return $groups;
    }

    private function componentClass(?string $value): string
    {
        if ($value === null) {
            return 'null';
        }
        if ($value === '') {
            return 'empty';
        }

        return 'present';
    }

    private function dateClass(?string $value): string
    {
        if ($value === null) {
            return 'null';
        }
        if ($value === '0000-00-00') {
            return 'zero';
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches) !== 1) {
            return 'other';
        }
        if (! checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
            return 'other';
        }

        return 'calendar';
    }

    private function ulid(): string
    {
        $alphabet = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
        $time = (int) floor(microtime(true) * 1000);
        $timeChars = '';
        for ($index = 0; $index < 10; $index++) {
            $timeChars = $alphabet[$time & 31].$timeChars;
            $time >>= 5;
        }
        $bits = '';
        foreach (str_split(random_bytes(10)) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }
        $bits = substr($bits, 0, 80);
        $random = '';
        for ($index = 0; $index < 16; $index++) {
            $random .= $alphabet[bindec(substr($bits, $index * 5, 5))];
        }

        return $timeChars.$random;
    }
}
