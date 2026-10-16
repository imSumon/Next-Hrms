<?php

namespace App\Http\Middleware;

use App\Models\CompanyMembership;
use App\Tenancy\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireOrganizationMasters
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = app(CompanyContext::class);
        $user = $request->user();

        $allowed = $user !== null && CompanyMembership::query()
            ->where('user_id', $user->id)
            ->where('company_id', $context->companyId)
            ->where('status', 'active')
            ->where('can_manage_organization_masters', true)
            ->exists();

        if (! $allowed) {
            return response()->json([
                'message' => 'Organization master changes are not authorized.',
            ], 403);
        }

        return $next($request);
    }
}
