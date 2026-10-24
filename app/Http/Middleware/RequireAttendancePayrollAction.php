<?php

namespace App\Http\Middleware;

use App\Attendance\AttendancePayrollActionGrantCheck;
use App\Models\AttendancePayrollActionGrant;
use App\Tenancy\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAttendancePayrollAction
{
    public function handle(Request $request, Closure $next, string $action): Response
    {
        if (! in_array($action, AttendancePayrollActionGrant::ACTIONS, true)) {
            throw new \InvalidArgumentException('Unknown attendance or payroll action.');
        }
        $user = $request->user();
        $companyId = app(CompanyContext::class)->companyId;
        $allowed = $user !== null && is_string($companyId)
            && app(AttendancePayrollActionGrantCheck::class)->allows((string) $user->id, $companyId, $action);

        if (! $allowed) {
            return response()->json([
                'message' => 'This action is not authorized for the active company.',
                'action' => $action,
            ], 403);
        }

        return $next($request);
    }
}
