<?php

namespace App\Console\Commands;

use App\Console\LocalTestDatabaseGuard;
use Illuminate\Console\Command;
use PDO;
use RuntimeException;

class ServeTestDatabase extends Command
{
    protected $signature = 'hrms:serve-test-database
        {--port=8000 : The local port to serve}
        {--device-log-fixture= : A local SQLite file with a synthetic all_att_log table for the read-only preview}';

    protected $description = 'Serve the application against proedge_new_hrms_test without changing .env.';

    public function handle(): int
    {
        if (! $this->laravel->environment(['local', 'testing'])) {
            $this->error('This command runs only when APP_ENV is local or testing.');

            return self::FAILURE;
        }

        $port = (string) $this->option('port');
        if (preg_match('/^\d+$/', $port) !== 1) {
            $this->error('The port must be a number.');

            return self::FAILURE;
        }

        try {
            $this->assertTarget();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $fixture = trim((string) $this->option('device-log-fixture'));
        if ($fixture !== '') {
            $path = realpath($fixture);
            if ($path === false || ! LocalTestDatabaseGuard::isDeviceLogFixture($path)) {
                $this->error('The device log fixture must be an existing local SQLite file.');

                return self::FAILURE;
            }
            $_ENV['HRMS_DEVICE_LOG_FIXTURE'] = $path;
            $_SERVER['HRMS_DEVICE_LOG_FIXTURE'] = $path;
            putenv('HRMS_DEVICE_LOG_FIXTURE='.$path);
            $this->info('The device log preview reads the local SQLite fixture '.$path.' read only. No live source is connected.');
        }

        $_ENV['DB_DATABASE'] = LocalTestDatabaseGuard::DATABASE;
        $_SERVER['DB_DATABASE'] = LocalTestDatabaseGuard::DATABASE;
        $_ENV['HRMS_TEST_DATABASE_ONLY'] = '1';
        $_SERVER['HRMS_TEST_DATABASE_ONLY'] = '1';
        putenv('DB_DATABASE='.LocalTestDatabaseGuard::DATABASE);
        putenv('HRMS_TEST_DATABASE_ONLY=1');

        $this->info('Serving '.LocalTestDatabaseGuard::DATABASE.' on 127.0.0.1:'.$port.'. The .env database name is not changed.');
        $this->info('Reload is disabled so this process database name reaches the server. A request aborts unless the live connection is the local test database.');

        return $this->call('serve', [
            '--host' => '127.0.0.1',
            '--port' => $port,
            '--no-reload' => true,
        ]);
    }

    private function assertTarget(): void
    {
        $pdo = new PDO(
            'pgsql:host=127.0.0.1;port=5432;dbname='.LocalTestDatabaseGuard::DATABASE,
            (string) config('database.connections.pgsql.username'),
            (string) config('database.connections.pgsql.password'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
        $meta = $pdo->query('SELECT current_database() AS db, inet_server_addr()::text AS addr, inet_server_port() AS port')->fetch(PDO::FETCH_ASSOC);
        if (! is_array($meta) || ! LocalTestDatabaseGuard::accepts($meta['db'], (string) $meta['addr']) || (int) $meta['port'] !== 5432) {
            throw new RuntimeException('Refusing to serve because the connection is not local '.LocalTestDatabaseGuard::DATABASE.'.');
        }
    }
}
