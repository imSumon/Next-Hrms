<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportPunchLogRequest;
use App\Http\Requests\StorePunchEventRequest;
use App\Models\Company;
use App\Models\PunchEvent;
use App\Punch\PunchLogImport;
use App\Punch\PunchRecordingRegistry;
use App\Tenancy\CompanyContext;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;

class PunchEventController extends Controller
{
    public function __construct(private PunchRecordingRegistry $punches) {}

    public function jsonIndex(CompanyContext $context): JsonResponse
    {
        $company = $this->company($context);

        return response()->json([
            'company_name' => $company->company_name,
            'data' => $this->punches->list($company->id)
                ->map(fn (PunchEvent $event): array => $this->row($event))
                ->values()
                ->all(),
        ]);
    }

    public function jsonShow(CompanyContext $context, string $punchEvent): JsonResponse
    {
        $company = $this->company($context);

        return response()->json($this->row($this->find($company->id, $punchEvent)));
    }

    public function jsonStore(StorePunchEventRequest $request, CompanyContext $context): JsonResponse
    {
        $company = $this->company($context);
        $validated = $request->validated();
        $event = $this->punches->record($company->id, [
            'employee_id' => $validated['employee_id'],
            'identity_value' => $validated['identity_value'],
            'source_name' => $validated['source_name'],
            'event_at' => $validated['event_at'],
            'source_event_id' => $validated['source_event_id'] ?? null,
        ]);

        return response()->json($this->row($event), 201);
    }

    public function jsonImport(ImportPunchLogRequest $request, CompanyContext $context, PunchLogImport $import): JsonResponse
    {
        $company = $this->company($context);
        $validated = $request->validated();
        $report = $import->run($company->id, $validated['source_name'], array_values($validated['rows']), $validated['mode'] === 'commit');
        $status = match (true) {
            $report['committed'] => 201,
            $report['rejected_count'] > 0 || $report['duplicate_count'] > 0 => 422,
            default => 200,
        };

        return response()->json($report, $status);
    }

    private function company(CompanyContext $context): Company
    {
        $company = Company::query()->find($context->companyId);
        abort_if($company === null, 404);

        return $company;
    }

    private function find(string $companyId, string $eventId): PunchEvent
    {
        $event = $this->punches->find($companyId, $eventId);
        abort_if($event === null, 404);

        return $event;
    }

    /**
     * @return array<string, mixed>
     */
    private function row(PunchEvent $event): array
    {
        return [
            'id' => $event->id,
            'employee_id' => $event->identity?->employee_id,
            'punch_identity_id' => $event->punch_identity_id,
            'identity_value' => $event->identity?->identity_value,
            'source_name' => $event->source_name,
            'event_at' => $this->atom($event->event_at),
            'ingested_at' => $this->atom($event->ingested_at),
            'source_event_id' => $event->source_event_id,
            'acting_user_id' => $event->acting_user_id,
            'withdrawal_status' => $event->withdrawal_status,
        ];
    }

    private function atom(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface ? $value->format(DateTimeInterface::ATOM) : null;
    }
}
