<?php

namespace App\Providers;

use App\Console\LocalTestDatabaseGuard;
use App\Models\CompanyMembership;
use App\Punch\PunchRecordingWriterGrant;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $fixture = LocalTestDatabaseGuard::deviceLogFixtureConnection();
        if ($fixture !== null) {
            config([
                'database.connections.'.LocalTestDatabaseGuard::DEVICE_LOG_FIXTURE_CONNECTION => $fixture,
                'attendance.device_log.connection' => LocalTestDatabaseGuard::DEVICE_LOG_FIXTURE_CONNECTION,
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::share('canManageEmployees', false);
        View::share('canOpenAttendance', false);
        View::share('navMemberships', collect());

        View::composer('layouts.hrms', function ($view): void {
            $user = auth()->user();
            $memberships = $user === null
                ? collect()
                : CompanyMembership::query()
                    ->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->with('company')
                    ->orderBy('id')
                    ->get();
            $current = $memberships->firstWhere('company_id', session('company_context_id'));

            $view->with('navMemberships', $memberships);
            $view->with('canManageEmployees', (bool) ($current->can_manage_employees ?? false));
            $view->with('canOpenAttendance', $user !== null && $current !== null
                && app(PunchRecordingWriterGrant::class)->allows((string) $user->id, (string) $current->company_id));
        });
    }
}
