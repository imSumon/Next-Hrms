<?php

namespace Tests\Feature;

use App\Console\LocalTestDatabaseGuard;
use Tests\EmployeeDatabaseTestCase;

class LocalTestDatabaseServerTest extends EmployeeDatabaseTestCase
{
    public function test_the_guard_accepts_only_the_local_test_database(): void
    {
        $this->assertTrue(LocalTestDatabaseGuard::accepts('proedge_new_hrms_test', '127.0.0.1'));
        $this->assertFalse(LocalTestDatabaseGuard::accepts('proedge_new_hrms', '127.0.0.1'));
        $this->assertFalse(LocalTestDatabaseGuard::accepts('proedge_new_hrms_uat', '127.0.0.1'));
        $this->assertFalse(LocalTestDatabaseGuard::accepts('proedge_new_hrms_test', '10.0.0.8'));
        $this->assertFalse(LocalTestDatabaseGuard::accepts(null, '127.0.0.1'));
    }

    public function test_the_probe_stays_hidden_until_the_test_server_flag_is_set(): void
    {
        $this->getJson('/api/local-test-database')->assertNotFound();

        $_ENV['HRMS_TEST_DATABASE_ONLY'] = '1';
        putenv('HRMS_TEST_DATABASE_ONLY=1');
        try {
            $this->getJson('/api/local-test-database')
                ->assertOk()
                ->assertJsonPath('database', 'proedge_new_hrms_test');
        } finally {
            unset($_ENV['HRMS_TEST_DATABASE_ONLY']);
            putenv('HRMS_TEST_DATABASE_ONLY');
        }

        $this->getJson('/api/local-test-database')->assertNotFound();
    }

    public function test_a_device_log_fixture_is_used_only_by_the_test_server_and_only_when_it_is_sqlite(): void
    {
        $fixture = tempnam(sys_get_temp_dir(), 'hrms-fixture-');
        $notSqlite = tempnam(sys_get_temp_dir(), 'hrms-fixture-');
        (new \PDO('sqlite:'.$fixture))->exec('CREATE TABLE all_att_log (id integer primary key)');
        file_put_contents($notSqlite, 'host=10.0.0.8');
        putenv('HRMS_DEVICE_LOG_FIXTURE='.$fixture);
        try {
            $this->assertNull(LocalTestDatabaseGuard::deviceLogFixtureConnection());

            putenv('HRMS_TEST_DATABASE_ONLY=1');
            $this->assertSame($fixture, LocalTestDatabaseGuard::deviceLogFixtureConnection()['database'] ?? null);
            $this->assertSame('sqlite', LocalTestDatabaseGuard::deviceLogFixtureConnection()['driver'] ?? null);

            putenv('HRMS_DEVICE_LOG_FIXTURE='.$notSqlite);
            $this->assertNull(LocalTestDatabaseGuard::deviceLogFixtureConnection());
        } finally {
            putenv('HRMS_TEST_DATABASE_ONLY');
            putenv('HRMS_DEVICE_LOG_FIXTURE');
            @unlink($fixture);
            @unlink($notSqlite);
        }
    }
}
