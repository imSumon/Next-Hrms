<?php

namespace App\Http\Controllers;

use App\Http\Requests\EndPunchIdentityRequest;
use App\Http\Requests\StorePunchIdentityRequest;
use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\PunchEvent;
use App\Models\PunchIdentity;
use App\Models\PunchIdentityOverlap;
use App\Punch\PunchIdentityRegistry;
use App\Tenancy\CompanyContext;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PunchIdentityController extends Controller
{
    public function __construct(private PunchIdentityRegistry $identities) {}

    public function jsonIndex(Request $request, CompanyContext $context): JsonResponse
    {
        $company = $this->company($context);
        $rows = $this->identities->list($company->id, $request->string('q')->toString());

        return response()->json([
            'company_name' => $company->company_name,
            'data' => $rows->map(fn (PunchIdentity $identity): array => $this->row($identity))->values()->all(),
        ]);
    }

    public function jsonConflicts(CompanyContext $context): JsonResponse
    {
        $company = $this->company($context);
        $overlaps = PunchIdentityOverlap::query()->where('company_id', $company->id)->orderBy('identity_value')->orderBy('effective_from')->get();

        return response()->json([
            'company_name' => $company->company_name,
            'documented_overlaps' => $overlaps->map(fn (PunchIdentityOverlap $overlap): array => [
                'identity_value' => $overlap->identity_value,
                'effective_from' => $this->atom($overlap->effective_from),
                'effective_to' => $this->atom($overlap->effective_to),
                'reason' => $overlap->reason,
            ])->values()->all(),
        ] + $this->identities->conflicts($company->id));
    }

    public function jsonShow(CompanyContext $context, string $identity): JsonResponse
    {
        $company = $this->company($context);
        $found = PunchIdentity::query()->where('company_id', $company->id)->where('id', $identity)->with('employee')->first();
        abort_if($found === null, 404);
        $history = AuditEvent::query()
            ->where('company_id', $company->id)
            ->where('subject_kind', AuditEvent::SUBJECT_PUNCH_IDENTITY)
            ->where('subject_id', $found->id)
            ->orderBy('acted_at')
            ->orderBy('id')
            ->with('actingUser')
            ->get();

        return response()->json($this->row($found) + [
            'punch_event_count' => PunchEvent::query()->where('company_id', $company->id)->where('punch_identity_id', $found->id)->count(),
            'history' => $history->map(fn (AuditEvent $event): array => [
                'action_kind' => $event->action_kind,
                'acting_user_id' => $event->acting_user_id,
                'acting_user_name' => $event->actingUser?->name,
                'acted_at' => $this->atom($event->acted_at),
                'previous_value' => $event->previous_value,
                'new_value' => $event->new_value,
                'reason' => $event->reason,
            ])->values()->all(),
        ]);
    }

    public function jsonStore(StorePunchIdentityRequest $request, CompanyContext $context): JsonResponse
    {
        $company = $this->company($context);
        $validated = $request->validated();
        $identity = $this->identities->register(
            $company->id,
            $validated['employee_id'],
            $validated['identity_value'],
            $validated['effective_from'],
            $validated['effective_to'] ?? null,
            $validated['reference'] ?? null,
        );
        $identity->load('employee');

        return response()->json($this->row($identity), 201);
    }

    public function jsonEnd(EndPunchIdentityRequest $request, CompanyContext $context, string $identity): JsonResponse
    {
        $company = $this->company($context);
        $ended = $this->identities->end($company->id, $identity, $request->validated('effective_to'), $request->validated('reason'));
        $ended->load('employee');

        return response()->json($this->row($ended));
    }

    private function company(CompanyContext $context): Company
    {
        $company = Company::query()->find($context->companyId);
        abort_if($company === null, 404);

        return $company;
    }

    /**
     * @return array<string, mixed>
     */
    private function row(PunchIdentity $identity): array
    {
        $registeredElsewhere = $this->identities->registeredElsewhere($identity);

        return [
            'id' => $identity->id,
            'employee_id' => $identity->employee_id,
            'employee_code' => $identity->employee?->employee_code,
            'display_name' => $identity->employee?->display_name,
            'identity_value' => $identity->identity_value,
            'effective_from' => $this->atom($identity->effective_from),
            'effective_to' => $this->atom($identity->effective_to),
            'status' => $this->identities->status($identity),
            'registered_in_another_company' => $registeredElsewhere,
            'warning' => $registeredElsewhere
                ? 'This card is also registered in another company for an overlapping period. The shared device log has no company column, so its rows stay ambiguous in the preview.'
                : null,
        ];
    }

    private function atom(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface ? $value->format(DateTimeInterface::ATOM) : null;
    }
}
