<?php

namespace App\Http\Middleware;

use App\Models\CompanyMembership;
use App\Punch\PunchRecordingWriterGrant;
use App\Tenancy\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePunchRecordingWriter
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $companyId = app(CompanyContext::class)->companyId;
        $member = $user !== null && CompanyMembership::query()
            ->where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->exists();
        $allowed = $member && app(PunchRecordingWriterGrant::class)->allows((string) $user->id, $companyId);

        if (! $allowed) {
            return response()->json([
                'message' => 'Punch recording is not authorized. No writer has been named.',
            ], 403);
        }

        return $next($request);
    }
}
