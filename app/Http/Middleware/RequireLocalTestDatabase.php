<?php

namespace App\Http\Middleware;

use App\Console\LocalTestDatabaseGuard;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RequireLocalTestDatabase
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->restricted()) {
            return $next($request);
        }

        $meta = DB::selectOne('SELECT current_database() AS db, inet_server_addr()::text AS addr');
        if ($meta === null || ! LocalTestDatabaseGuard::accepts($meta->db ?? null, $meta->addr ?? null)) {
            return response()->json([
                'message' => 'This server is restricted to the local test database.',
            ], 503);
        }

        return $next($request);
    }

    private function restricted(): bool
    {
        $value = $_ENV['HRMS_TEST_DATABASE_ONLY'] ?? $_SERVER['HRMS_TEST_DATABASE_ONLY'] ?? getenv('HRMS_TEST_DATABASE_ONLY');

        return $value === '1';
    }
}
