<?php

namespace App\Http\Middleware;

use App\Leave\LeavePositionWriterGrant;
use App\Tenancy\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireLeavePositionWriter
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = app(CompanyContext::class);
        $user = $request->user();
        $allowed = $user !== null && app(LeavePositionWriterGrant::class)->allows(
            (string) $user->id,
            $context->companyId,
        );

        if (! $allowed) {
            return response()->json([
                'message' => 'Leave-position records are not authorized.',
            ], 403);
        }

        return $next($request);
    }
}
