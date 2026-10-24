<?php

namespace App\Http\Controllers;

use App\Attendance\AttendanceDashboard;
use App\Attendance\DeviceLogPreview;
use App\Attendance\DeviceLogSource;
use App\Http\Requests\AttendanceDashboardRequest;
use App\Http\Requests\DeviceLogPreviewRequest;
use App\Http\Requests\ImportDeviceLogBatchRequest;
use App\Models\Company;
use App\Punch\DevicePunchBatchImport;
use App\Tenancy\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AttendanceDashboardController extends Controller
{
    public function jsonDashboard(AttendanceDashboardRequest $request, CompanyContext $context, AttendanceDashboard $dashboard): JsonResponse
    {
        $company = $this->company($context);

        return response()->json(['company_name' => $company->company_name] + $dashboard->build($company->id, $request->validated('date')));
    }

    public function jsonDeviceLogPreview(DeviceLogPreviewRequest $request, CompanyContext $context, DeviceLogSource $source, DeviceLogPreview $preview): JsonResponse
    {
        $company = $this->company($context);
        if (! $source->isConfigured()) {
            return response()->json([
                'company_name' => $company->company_name,
                'source' => $source->describe(),
                'writes_enabled' => false,
                'blocking_decisions' => DeviceLogPreview::BLOCKING_DECISIONS,
            ]);
        }

        try {
            $result = $preview->build($company->id, $request->validated('from'), $request->validated('to'));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable) {
            return response()->json([
                'message' => 'The device log source could not be read.',
                'source' => $source->describe(),
                'writes_enabled' => false,
            ], 503);
        }

        return response()->json(['company_name' => $company->company_name] + $result);
    }

    public function jsonDeviceLogImport(ImportDeviceLogBatchRequest $request, CompanyContext $context, DeviceLogSource $source, DevicePunchBatchImport $batch): JsonResponse
    {
        $company = $this->company($context);
        if (! $source->isConfigured()) {
            return response()->json([
                'company_name' => $company->company_name,
                'source' => $source->describe(),
                'writes_enabled' => false,
                'import_confirmed' => false,
                'durable_exception_storage' => false,
                'blocking_decisions' => DevicePunchBatchImport::BLOCKING_DECISIONS,
            ]);
        }

        $validated = $request->validated();

        try {
            $result = $batch->run(
                $company->id,
                $validated['from'],
                $validated['to'],
                $request->boolean('confirm'),
                $validated['fingerprint'] ?? null,
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable) {
            return response()->json([
                'message' => 'The device log source could not be read.',
                'source' => $source->describe(),
                'writes_enabled' => false,
                'import_confirmed' => false,
            ], 503);
        }

        return response()->json(['company_name' => $company->company_name] + $result);
    }

    private function company(CompanyContext $context): Company
    {
        $company = Company::query()->find($context->companyId);
        abort_if($company === null, 404);

        return $company;
    }
}
