<?php

namespace App\Http\Middleware;

use App\Models\CompanyMembership;
use App\Tenancy\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireCompanyContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            return $this->unavailable($request);
        }

        $activeIds = CompanyMembership::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('company_id');

        if ($activeIds->isEmpty()) {
            $request->session()->forget(CompanyContext::SESSION_KEY);

            return $this->unavailable($request);
        }

        $selected = $request->session()->get(CompanyContext::SESSION_KEY);
        if (! is_string($selected) || ! $activeIds->contains($selected)) {
            $request->session()->forget(CompanyContext::SESSION_KEY);
            $selected = null;
        }

        if ($selected === null && $activeIds->count() === 1) {
            $selected = $activeIds->first();
            $request->session()->put(CompanyContext::SESSION_KEY, $selected);
        }

        if (! is_string($selected)) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'An active company must be selected.',
                ], 403);
            }

            return redirect()->route('company.choose');
        }

        $membership = CompanyMembership::query()
            ->where('user_id', $user->id)
            ->where('company_id', $selected)
            ->where('status', 'active')
            ->first();

        view()->share('canManageEmployees', (bool) $membership?->can_manage_employees);
        view()->share('navMemberships', CompanyMembership::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->with('company')
            ->orderBy('id')
            ->get());

        app()->instance(CompanyContext::class, new CompanyContext($selected));

        return $next($request);
    }

    private function unavailable(Request $request): Response
    {
        if ($request->is('api/*')) {
            return response()->json([
                'message' => 'This account has no active company membership.',
            ], 403);
        }

        return response()->view('employees.unavailable', [], 403);
    }
}
