<?php

namespace App\Http\Controllers;

use App\Models\CompanyMembership;
use App\Tenancy\CompanyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CompanyContextController extends Controller
{
    public function show(Request $request): View|Response
    {
        $memberships = $this->activeMemberships($request);
        if ($memberships->isEmpty()) {
            return response()->view('employees.unavailable', [], 403);
        }

        return view('auth.company', [
            'memberships' => $memberships,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $companyId = $request->string('company_id')->toString();

        $membership = CompanyMembership::query()
            ->where('user_id', $request->user()->id)
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->first();

        if ($membership === null) {
            throw ValidationException::withMessages([
                'company_id' => 'That company is not an active membership.',
            ]);
        }

        $request->session()->put(CompanyContext::SESSION_KEY, $membership->company_id);

        return redirect()->route('employees.index');
    }

    private function activeMemberships(Request $request)
    {
        return CompanyMembership::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->with('company')
            ->orderBy('id')
            ->get();
    }
}
