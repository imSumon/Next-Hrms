<?php

namespace App\Providers;

use App\Models\CompanyMembership;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::share('canManageEmployees', false);
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
        });
    }
}
