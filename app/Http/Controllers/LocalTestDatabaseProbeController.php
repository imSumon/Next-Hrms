<?php

namespace App\Http\Controllers;

use App\Console\LocalTestDatabaseGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LocalTestDatabaseProbeController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $value = $_ENV['HRMS_TEST_DATABASE_ONLY'] ?? $_SERVER['HRMS_TEST_DATABASE_ONLY'] ?? getenv('HRMS_TEST_DATABASE_ONLY');
        abort_unless($value === '1', 404);

        $meta = DB::selectOne('SELECT current_database() AS db, inet_server_addr()::text AS addr');
        abort_unless($meta !== null && LocalTestDatabaseGuard::accepts($meta->db ?? null, $meta->addr ?? null), 404);

        return response()->json([
            'database' => $meta->db,
        ]);
    }
}
