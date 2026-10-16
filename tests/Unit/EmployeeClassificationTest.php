<?php

declare(strict_types=1);

namespace Tests\Unit;

use Hrms\EmployeeClassification\ClassificationException;
use Hrms\EmployeeClassification\DumpParser;
use Hrms\EmployeeClassification\Fingerprint;
use Hrms\EmployeeClassification\GateSet;
use Hrms\EmployeeClassification\Manifest;
use Hrms\EmployeeClassification\Reconciler;
use Hrms\EmployeeClassification\ReportPublisher;
use Hrms\EmployeeClassification\Runner;
use Hrms\EmployeeClassification\Safeguard;
use Hrms\EmployeeClassification\StringDumpStream;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2).'/tools/employee-classification/classify.php';

class EmployeeClassificationTest extends TestCase
{
    private string $fixtureSql = <<<'SQL'
-- Host: localhost    Database: proedgea_newproedgehrm
CREATE TABLE `other` (
  `id` int NOT NULL,
  `note` text
);
INSERT INTO `other` VALUES (1,'HISTORY_SENTINEL_DO_NOT_KEEP INSERT INTO `employees` VALUES (9,''HACK'');');
CREATE TABLE `employees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `emp_id_no` varchar(50) DEFAULT NULL,
  `emp_first_name` varchar(128) DEFAULT NULL,
  `emp_mid_name` varchar(128) DEFAULT NULL,
  `emp_last_name` varchar(128) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `disconrinue` tinyint(1) DEFAULT '0',
  `discontinue_date` date DEFAULT NULL,
  `dis` int DEFAULT '0',
  `employment_history` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;
INSERT INTO `employees` VALUES
(1,'10024','Rupa','','Ahmed','2020-01-15',1,0,NULL,9,'HISTORY_SENTINEL_DO_NOT_KEEP'),
(2,'10024','Imran',NULL,'Hossain','0000-00-00',1,1,'2024-05-01',1,'note; INSERT INTO `employees` VALUES (99,''X'');'),
(3,'EB1','Lina','K','Akter','2019-03-03',0,1,'2021-06-06',0,NULL),
(4,'EB2','No','Leave','Date','2018-01-01',0,1,NULL,0,NULL),
(5,'EB3','Both','False','Zero',NULL,0,0,'0000-00-00',0,NULL),
(6,NULL,'Missing','Code','Person','2022-02-02',1,0,NULL,0,NULL),
(7,'10024 ','Space','','O\'Brien','2020-01-01',1,0,NULL,0,NULL);
SQL;

    public function test_manifest_allowlists_only_the_three_approved_paths(): void
    {
        $this->assertSame(
            'legacy-data/companies/proedgea_newproedgehrm.sql',
            Manifest::assertRelativePath('legacy-data\\companies\\proedgea_newproedgehrm.sql'),
        );
        $this->assertSame(
            'legacy-data/companies/proedgea_atlas.sql',
            Manifest::assertRelativePath('legacy-data/companies/proedgea_atlas.sql'),
        );
        $this->assertSame(
            'legacy-data/companies/vtl/proedgea_vtlhrm.sql.gz',
            Manifest::assertRelativePath('legacy-data/companies/vtl/proedgea_vtlhrm.sql.gz'),
        );

        foreach ([
            'legacy-data/companies/other.sql',
            'legacy-data/companies/../proedgea_newproedgehrm.sql',
            '/legacy-data/companies/proedgea_newproedgehrm.sql',
            'C:/legacy-data/companies/proedgea_newproedgehrm.sql',
        ] as $rejected) {
            try {
                Manifest::assertRelativePath($rejected);
                $this->fail('Path was accepted: '.$rejected);
            } catch (ClassificationException $exception) {
                $this->assertStringContainsString('approved manifest', $exception->getMessage());
            }
        }
    }

    public function test_size_mismatch_does_not_hash_or_open(): void
    {
        $path = $this->tempFile('abc');
        $hashed = false;
        $opened = false;
        try {
            (new Fingerprint())->openVerified($path, 1, str_repeat('A', 64), false, function () use (&$hashed): string {
                $hashed = true;

                return str_repeat('B', 64);
            }, function () use (&$opened): StringDumpStream {
                $opened = true;

                return new StringDumpStream('');
            });
            $this->fail('Size mismatch was accepted.');
        } catch (ClassificationException $exception) {
            $this->assertStringContainsString('size', $exception->getMessage());
        } finally {
            unlink($path);
        }
        $this->assertFalse($hashed);
        $this->assertFalse($opened);
    }

    public function test_hash_mismatch_does_not_open_a_compressed_stream(): void
    {
        $path = $this->tempFile('compressed-bytes');
        $opened = false;
        try {
            (new Fingerprint())->openVerified($path, filesize($path), str_repeat('A', 64), true, null, function () use (&$opened): StringDumpStream {
                $opened = true;

                return new StringDumpStream('');
            });
            $this->fail('Hash mismatch was accepted.');
        } catch (ClassificationException $exception) {
            $this->assertStringContainsString('SHA-256', $exception->getMessage());
        } finally {
            unlink($path);
        }
        $this->assertFalse($opened);
    }

    public function test_compressed_hash_is_verified_before_streaming_decompression(): void
    {
        $sql = "-- Host: localhost    Database: proedgea_vtlhrm\n".$this->minimalEmployeesSql();
        $path = tempnam(sys_get_temp_dir(), 'hrmscls');
        $this->assertNotFalse($path);
        $gz = $path.'.gz';
        file_put_contents($gz, gzencode($sql));
        unlink($path);
        $hash = strtoupper((string) hash_file('sha256', $gz));
        $stream = (new Fingerprint())->openVerified($gz, (int) filesize($gz), $hash, true);
        try {
            $parsed = (new DumpParser())->parse($stream, 'proedgea_vtlhrm');
        } finally {
            $stream->close();
            unlink($gz);
        }
        $this->assertCount(1, $parsed['rows']);
        $this->assertSame('A1', $parsed['rows'][0]['emp_id_no']);
    }

    public function test_closed_gates_classify_every_row_and_propose_no_loads(): void
    {
        $parsed = (new DumpParser())->parse(new StringDumpStream($this->fixtureSql), 'proedgea_newproedgehrm');
        $this->assertCount(7, $parsed['rows']);
        $this->assertSame("O'Brien", $parsed['rows'][6]['emp_last_name']);
        $this->assertSame('10024 ', $parsed['rows'][6]['emp_id_no']);
        $this->assertArrayNotHasKey('dis', $parsed['rows'][0]);
        $this->assertArrayNotHasKey('employment_history', $parsed['rows'][0]);

        $report = (new Runner())->fromRows($this->proedgeMeta(), $parsed['rows']);
        $encoded = json_encode($report, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('HISTORY_SENTINEL_DO_NOT_KEEP', $encoded);
        $this->assertFalse($report['final_load_contract']['executed']);
        $this->assertSame(0, $report['final_load_contract']['loaded']);
        $this->assertSame('not executed', $report['final_load_contract']['result']);
        $this->assertSame('gates_closed', $report['mode']);
        $this->assertSame('matched', $report['second_pass']);

        $summary = $report['companies'][0];
        $this->assertSame(7, $summary['employees']['source_count']);
        $this->assertSame(0, $summary['employees']['proposed_load']);
        $this->assertSame(7, $summary['employees']['quarantined']);
        $this->assertSame(0, $summary['employees']['approved_exceptions']);
        $this->assertSame(0, $summary['employees']['unexplained']);
        $this->assertSame(0, $summary['employees']['loaded']);
        $this->assertSame(5, $summary['employees']['distinct_non_empty_codes']);
        $this->assertSame(7, $summary['episodes']['source_count']);
        $this->assertSame(0, $summary['episodes']['proposed_load']);
        $this->assertSame(0, $summary['episodes']['unexplained']);
        $this->assertFalse($summary['company_row_is_migration_approval']);

        $employees = $this->byPk($report['employee_classifications']);
        $episodes = $this->byPk($report['episode_classifications']);
        $this->assertCount(7, $employees);
        $this->assertCount(7, $episodes);

        $this->assertSame('quarantined', $employees['1']['disposition']);
        $this->assertContains('duplicate_code_group', $employees['1']['reason_codes']);
        $this->assertContains('name_rule_proposed', $employees['1']['reason_codes']);
        $this->assertSame('', $employees['1']['display_name_candidate']);
        $this->assertSame('10024', $employees['1']['source_emp_id_no']);
        $this->assertSame('proposed_not_loadable', $employees['1']['mapping_status']);
        $this->assertSame('Open', $episodes['1']['episode_candidate_status']);
        $this->assertSame('classified_only', $episodes['1']['disposition']);
        $this->assertSame('proposed_not_loadable', $episodes['1']['mapping_status']);
        $this->assertNull($episodes['1']['proposed_target_joining_date']);

        $this->assertSame('', $episodes['2']['episode_candidate_status']);
        $this->assertContains('both_flags_true', $episodes['2']['reason_codes']);
        $this->assertContains('zero_date_rule_proposed', $episodes['2']['reason_codes']);
        $this->assertSame('zero', $episodes['2']['joining_date_class']);
        $this->assertSame('0000-00-00', $episodes['2']['source_joining_date']);
        $this->assertNull($episodes['2']['proposed_target_joining_date']);

        $this->assertSame('separated', $episodes['3']['episode_candidate_status']);
        $this->assertSame('classified_only', $episodes['3']['disposition']);
        $this->assertSame('proposed_not_loadable', $episodes['3']['mapping_status']);
        $this->assertSame('quarantined', $episodes['4']['disposition']);
        $this->assertContains('leaving_date_not_calendar', $episodes['4']['reason_codes']);
        $this->assertSame('', $episodes['4']['episode_candidate_status']);
        $this->assertSame('no_episode_evidence', $episodes['5']['disposition']);
        $this->assertContains('flag_pair_no_status', $episodes['5']['reason_codes']);
        $this->assertSame('null', $episodes['5']['joining_date_class']);
        $this->assertSame('zero', $episodes['5']['discontinue_date_class']);
        $this->assertSame('quarantined', $employees['6']['disposition']);
        $this->assertContains('source_code_missing', $employees['6']['reason_codes']);
        $this->assertNull($employees['6']['source_emp_id_no']);
        $this->assertSame('classified_only', $employees['7']['disposition']);
        $this->assertSame('10024 ', $employees['7']['source_emp_id_no']);

        $this->assertCount(1, $report['exception_groups']);
        $this->assertSame('proedge:emp_id_no:10024', $report['exception_groups'][0]['group_id']);
        $this->assertSame(['1', '2'], $report['exception_groups'][0]['member_source_pks']);
        $this->assertSame('', $report['exception_groups'][0]['authorization']);

        foreach ($episodes as $episode) {
            $this->assertNotSame('inactive', $episode['episode_candidate_status']);
        }
    }

    public function test_repeat_classification_keeps_dispositions_and_second_pass_rejects_a_missing_row(): void
    {
        $parsed = (new DumpParser())->parse(new StringDumpStream($this->fixtureSql), 'proedgea_newproedgehrm');
        $runner = new Runner();
        $first = $runner->fromRows($this->proedgeMeta(), $parsed['rows']);
        $second = $runner->fromRows($this->proedgeMeta(), $parsed['rows']);
        $this->assertSame($this->stable($first['employee_classifications']), $this->stable($second['employee_classifications']));
        $this->assertSame($this->stable($first['episode_classifications']), $this->stable($second['episode_classifications']));

        array_pop($first['employee_classifications']);
        $this->expectException(ClassificationException::class);
        $this->expectExceptionMessage('has no employee row');
        (new \Hrms\EmployeeClassification\Reconciler())->assertConsistent($first);
    }

    public function test_second_pass_rejects_a_repeated_source_key(): void
    {
        $parsed = (new DumpParser())->parse(new StringDumpStream($this->fixtureSql), 'proedgea_newproedgehrm');
        $report = (new Runner())->fromRows($this->proedgeMeta(), $parsed['rows']);
        $report['employee_classifications'][] = $report['employee_classifications'][0];
        $report['episode_classifications'][] = $report['episode_classifications'][0];

        $this->expectException(ClassificationException::class);
        $this->expectExceptionMessage('two employee classifications');
        (new \Hrms\EmployeeClassification\Reconciler())->assertConsistent($report);
    }

    public function test_explicit_gates_still_quarantine_duplicates_and_both_true_rows(): void
    {
        $rows = [
            $this->row('1', 'DUP', 'Rupa', '', 'Ahmed', '2020-01-15', '1', '0', null),
            $this->row('2', 'DUP', 'Imran', null, 'Hossain', '2020-01-15', '1', '0', null),
            $this->row('3', 'OK', 'Lina', '', 'Akter', '2020-01-15', '1', '1', '2024-05-01'),
            $this->row('4', 'BLANK', '   ', null, '', '2020-01-15', '1', '0', null),
            $this->row('5', 'ZERO', 'Ada', '', 'Lovelace', '0000-00-00', '1', '0', null),
            $this->row('6', 'OPEN', 'Grace', '', 'Hopper', '2021-02-03', '1', '0', null),
        ];
        $report = (new Runner())->fromRows($this->proedgeMeta(), $rows, GateSet::explicit([
            'd1' => 'approved',
            'd2' => 'approved',
            'd3' => 'approved',
            'd4' => 'approved',
            'd6' => 'approved',
        ]));
        $employees = $this->byPk($report['employee_classifications']);
        $episodes = $this->byPk($report['episode_classifications']);

        $this->assertSame('Rupa Ahmed', $employees['1']['display_name_candidate']);
        $this->assertSame('proposed_not_loadable', $employees['1']['mapping_status']);
        $this->assertSame('proposed_not_loadable', $employees['2']['mapping_status']);
        $this->assertSame('Lina Akter', $employees['3']['display_name_candidate']);
        $this->assertSame('proposed_loadable', $employees['3']['mapping_status']);
        $this->assertSame('quarantined', $episodes['3']['disposition']);
        $this->assertSame('', $episodes['3']['episode_candidate_status']);
        $this->assertSame('proposed_not_loadable', $episodes['3']['mapping_status']);
        $this->assertSame('', $employees['4']['display_name_candidate']);
        $this->assertSame('quarantined', $employees['4']['disposition']);
        $this->assertSame('zero', $episodes['5']['joining_date_class']);
        $this->assertNull($episodes['5']['proposed_target_joining_date']);
        $this->assertSame('proposed_not_loadable', $episodes['5']['mapping_status']);
        $this->assertSame('proposed_loadable', $episodes['6']['mapping_status']);
        $this->assertSame('Open', $episodes['6']['episode_candidate_status']);
        $this->assertSame('2021-02-03', $episodes['6']['proposed_target_joining_date']);
        $this->assertSame(3, $report['companies'][0]['employees']['proposed_load']);
        $this->assertSame(1, $report['companies'][0]['episodes']['proposed_load']);
        $this->assertFalse($report['final_load_contract']['executed']);
    }

    public function test_malformed_dump_and_header_mismatch_fail_closed(): void
    {
        $parser = new DumpParser();
        try {
            $parser->parse(new StringDumpStream("-- Host: localhost    Database: wrong\n".$this->minimalEmployeesSql()), 'proedgea_newproedgehrm');
            $this->fail('Header mismatch was accepted.');
        } catch (ClassificationException $exception) {
            $this->assertStringContainsString('header database', $exception->getMessage());
        }

        $short = <<<'SQL'
-- Host: localhost    Database: proedgea_newproedgehrm
CREATE TABLE `employees` (
  `id` int NOT NULL,
  `emp_id_no` varchar(50) DEFAULT NULL,
  `emp_first_name` varchar(128) DEFAULT NULL,
  `emp_mid_name` varchar(128) DEFAULT NULL,
  `emp_last_name` varchar(128) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `disconrinue` tinyint(1) DEFAULT '0',
  `discontinue_date` date DEFAULT NULL
);
INSERT INTO `employees` VALUES (1,'A');
SQL;
        $this->expectException(ClassificationException::class);
        $this->expectExceptionMessage('field count');
        $parser->parse(new StringDumpStream($short), 'proedgea_newproedgehrm');
    }

    public function test_output_paths_and_database_names_are_refused(): void
    {
        $project = sys_get_temp_dir().DIRECTORY_SEPARATOR.'hrms-cls-project-'.bin2hex(random_bytes(4));
        $legacy = $project.DIRECTORY_SEPARATOR.'legacy-data';
        mkdir($project);
        mkdir($legacy);
        $application = dirname(__DIR__, 2);
        $safeguard = new Safeguard();
        try {
            foreach ([
                $application.DIRECTORY_SEPARATOR.'classification-report.json',
                $project.DIRECTORY_SEPARATOR.'classification-report.json',
                $legacy.DIRECTORY_SEPARATOR.'classification-report.json',
                sys_get_temp_dir().DIRECTORY_SEPARATOR.'proedge_new_hrms'.DIRECTORY_SEPARATOR.'out.json',
                sys_get_temp_dir().DIRECTORY_SEPARATOR.'proedge_new_hrms_test'.DIRECTORY_SEPARATOR.'out.json',
            ] as $rejected) {
                try {
                    $safeguard->assertOutputPath($rejected, $application, $project);
                    $this->fail('Output path was accepted: '.$rejected);
                } catch (ClassificationException) {
                    $this->assertTrue(true);
                }
            }

            $allowed = sys_get_temp_dir().DIRECTORY_SEPARATOR.'hrms-cls-out-'.bin2hex(random_bytes(4)).'.json';
            $accepted = $safeguard->assertOutputPath($allowed, $application, $project);
            $this->assertFileDoesNotExist($accepted);

            $existing = sys_get_temp_dir().DIRECTORY_SEPARATOR.'hrms-cls-existing-'.bin2hex(random_bytes(4)).'.json';
            file_put_contents($existing, '{}');
            try {
                $safeguard->assertOutputPath($existing, $application, $project);
                $this->fail('Existing output was accepted.');
            } catch (ClassificationException $exception) {
                $this->assertStringContainsString('will not be overwritten', $exception->getMessage());
            } finally {
                unlink($existing);
            }
        } finally {
            rmdir($legacy);
            rmdir($project);
        }

        try {
            guardArguments(['classify.php', '--output', sys_get_temp_dir().'/proedge_new_hrms/out.json']);
            $this->fail('A database name argument was accepted.');
        } catch (ClassificationException $exception) {
            $this->assertStringContainsString('proedge_new_hrms', $exception->getMessage());
        }
    }

    public function test_cli_refuses_database_arguments_and_sources_open_no_database(): void
    {
        try {
            guardArguments(['classify.php', '--approve-d1']);
            $this->fail('An approval switch was accepted.');
        } catch (ClassificationException $exception) {
            $this->assertStringContainsString('gates_closed', $exception->getMessage());
        }

        $root = dirname(__DIR__, 2).'/tools/employee-classification';
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $source = (string) file_get_contents($file->getPathname());
            $this->assertDoesNotMatchRegularExpression(
                '/\bnew\s+PDO\b|\bmysqli_connect\b|\bpg_connect\b|\bpg_query\b|Illuminate\\\\Database|\bDB::/',
                $source,
                $file->getPathname(),
            );
        }

        $runner = (string) file_get_contents($root.'/src/Runner.php');
        $this->assertLessThan(strpos($runner, '->parse('), strpos($runner, 'openVerified('));
    }

    public function test_ambiguous_header_and_repeated_columns_fail_closed(): void
    {
        $parser = new DumpParser();
        $ambiguous = "-- Host: localhost    Database: proedgea_newproedgehrm\n"
            ."-- Current Database: `proedgea_other`\n"
            .$this->minimalEmployeesSql();
        try {
            $parser->parse(new StringDumpStream($ambiguous), 'proedgea_newproedgehrm');
            $this->fail('An ambiguous header was accepted.');
        } catch (ClassificationException $exception) {
            $this->assertStringContainsString('ambiguous', $exception->getMessage());
        }

        $repeated = "-- Host: localhost    Database: proedgea_newproedgehrm\n"
            ."-- Current Database: `proedgea_newproedgehrm`\n"
            .<<<'SQL'
CREATE TABLE `employees` (
  `id` int NOT NULL,
  `emp_id_no` varchar(50) DEFAULT NULL,
  `emp_first_name` varchar(128) DEFAULT NULL,
  `emp_mid_name` varchar(128) DEFAULT NULL,
  `emp_last_name` varchar(128) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `disconrinue` tinyint(1) DEFAULT '0',
  `discontinue_date` date DEFAULT NULL
);
INSERT INTO `employees` (`id`,`id`,`emp_id_no`,`emp_first_name`,`emp_mid_name`,`emp_last_name`,`joining_date`,`active`,`disconrinue`,`discontinue_date`)
VALUES (1,9,'A1','Ada',NULL,'Lovelace','2020-01-02',1,0,NULL);
SQL;
        try {
            $parser->parse(new StringDumpStream($repeated), 'proedgea_newproedgehrm');
            $this->fail('A repeated column was accepted.');
        } catch (ClassificationException $exception) {
            $this->assertStringContainsString('repeats a column', $exception->getMessage());
        }

        $agreed = "-- Host: localhost    Database: proedgea_newproedgehrm\n"
            ."-- Current Database: `proedgea_newproedgehrm`\n"
            .$this->minimalEmployeesSql();
        $parsed = $parser->parse(new StringDumpStream($agreed), 'proedgea_newproedgehrm');
        $this->assertSame('A1', $parsed['rows'][0]['emp_id_no']);
        $this->assertSame('1', $parsed['rows'][0]['id']);
    }

    public function test_second_pass_detects_a_changed_or_reordered_classification_and_an_illegal_load(): void
    {
        $rows = [
            $this->row('1', 'A1', 'Ada', '', 'Lovelace', '2020-01-02', '1', '0', null),
            $this->row('2', 'B2', 'Grace', '', 'Hopper', '2020-01-03', '1', '0', null),
        ];
        $report = (new Runner())->fromRows($this->proedgeMeta(), $rows);
        $this->assertSame(2, $report['companies'][0]['parsed_row_count']);
        $reconciler = new Reconciler();

        $changed = $report;
        $changed['employee_classifications'][0]['reason_codes'][] = 'tampered';
        try {
            $reconciler->assertConsistent($changed);
            $this->fail('A changed reason was accepted.');
        } catch (ClassificationException $exception) {
            $this->assertStringContainsString('employees classification does not match', $exception->getMessage());
        }

        $reordered = $report;
        $reordered['employee_classifications'] = array_reverse($reordered['employee_classifications']);
        try {
            $reconciler->assertConsistent($reordered);
            $this->fail('A reordered classification was accepted.');
        } catch (ClassificationException $exception) {
            $this->assertStringContainsString('employees classification does not match', $exception->getMessage());
        }

        $illegal = $report;
        $illegal['employee_classifications'][0]['disposition'] = 'quarantined';
        $illegal['employee_classifications'][0]['mapping_status'] = 'proposed_loadable';
        try {
            $reconciler->assertConsistent($illegal);
            $this->fail('A quarantined proposed load was accepted.');
        } catch (ClassificationException $exception) {
            $this->assertStringContainsString('non-loadable disposition', $exception->getMessage());
        }

        $short = $report;
        $short['companies'][0]['parsed_row_count'] = 1;
        try {
            $reconciler->assertConsistent($short);
            $this->fail('A short parsed count was accepted.');
        } catch (ClassificationException $exception) {
            $this->assertStringContainsString('Parsed row count', $exception->getMessage());
        }
    }

    public function test_incomplete_publish_removes_the_partial_file_and_does_not_create_the_destination(): void
    {
        $destination = sys_get_temp_dir().DIRECTORY_SEPARATOR.'hrms-cls-pub-'.bin2hex(random_bytes(4)).'.json';
        $partial = $destination.'.partial';
        try {
            (new ReportPublisher())->publish($destination, 'not-a-classification-report', function (string $path, string $body): int {
                file_put_contents($path, 'partial');

                return 1;
            });
            $this->fail('An incomplete write was accepted.');
        } catch (ClassificationException $exception) {
            $this->assertStringContainsString('incomplete', $exception->getMessage());
        }
        $this->assertFileDoesNotExist($destination);
        $this->assertFileDoesNotExist($partial);
    }

    /** @param  list<array<string, mixed>>  $rows
     * @return array<string, array<string, mixed>>
     */
    private function byPk(array $rows): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(string) $row['source_pk']] = $row;
        }

        return $indexed;
    }

    /** @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function stable(array $rows): array
    {
        foreach ($rows as &$row) {
            unset($row['proposed_employee_ulid'], $row['proposed_episode_ulid']);
        }

        return $rows;
    }

    /** @return array<string, mixed> */
    private function proedgeMeta(): array
    {
        return [
            'catalog_company_id' => 24,
            'alias' => 'proedge',
            'source_database' => 'proedgea_newproedgehrm',
            'source_system' => 'legacy_proedge',
            'source_file_sha256' => 'ABC',
            'target_company_ulid' => '01M4KG2364VXPYVZCM7HZTGPB1',
            'relative_path' => 'legacy-data/companies/proedgea_newproedgehrm.sql',
            'signed_exceptions' => [],
        ];
    }

    /** @return array<string, ?string> */
    private function row(
        string $id,
        string $code,
        ?string $first,
        ?string $middle,
        ?string $last,
        ?string $joining,
        string $active,
        string $discontinued,
        ?string $leaving,
    ): array {
        return [
            'id' => $id,
            'emp_id_no' => $code,
            'emp_first_name' => $first,
            'emp_mid_name' => $middle,
            'emp_last_name' => $last,
            'joining_date' => $joining,
            'active' => $active,
            'disconrinue' => $discontinued,
            'discontinue_date' => $leaving,
        ];
    }

    private function minimalEmployeesSql(): string
    {
        return <<<'SQL'
CREATE TABLE `employees` (
  `id` int NOT NULL,
  `emp_id_no` varchar(50) DEFAULT NULL,
  `emp_first_name` varchar(128) DEFAULT NULL,
  `emp_mid_name` varchar(128) DEFAULT NULL,
  `emp_last_name` varchar(128) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `disconrinue` tinyint(1) DEFAULT '0',
  `discontinue_date` date DEFAULT NULL
);
INSERT INTO `employees` VALUES (1,'A1','Ada',NULL,'Lovelace','2020-01-02',1,0,NULL);
SQL;
    }

    private function tempFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'hrmscls');
        $this->assertNotFalse($path);
        file_put_contents($path, $contents);

        return $path;
    }
}
