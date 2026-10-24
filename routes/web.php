<?php

use App\Http\Controllers\AttendanceDashboardController;
use App\Http\Controllers\AttendanceDayResultController;
use App\Http\Controllers\AttendancePeriodController;
use App\Http\Controllers\CompanyContextController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmploymentEpisodeController;
use App\Http\Controllers\LeavePositionController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\LocalTestDatabaseProbeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\OrganizationMasterController;
use App\Http\Controllers\PayrollIntakeController;
use App\Http\Controllers\PunchEventController;
use App\Http\Controllers\PunchIdentityController;
use App\Http\Middleware\RequireAttendancePayrollAction;
use App\Http\Middleware\RequireCompanyContext;
use App\Http\Middleware\RequireEmployeeManagement;
use App\Http\Middleware\RequireLeavePositionWriter;
use App\Http\Middleware\RequireOrganizationMasters;
use App\Http\Middleware\RequirePunchRecordingWriter;
use App\Http\Requests\StoreOrganizationMasterRequest;
use App\Http\Requests\UpdateOrganizationMasterRequest;
use App\Models\AttendancePayrollActionGrant;
use App\Tenancy\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api/local-test-database', LocalTestDatabaseProbeController::class)->name('api.local-test-database');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/company', [CompanyContextController::class, 'show'])->name('company.choose');
    Route::post('/company', [CompanyContextController::class, 'update'])->name('company.switch');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::middleware(RequireCompanyContext::class)->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/api/employees', [EmployeeController::class, 'jsonIndex'])->name('api.employees.index');
        Route::get('/api/employees/{employee}', [EmployeeController::class, 'jsonShow'])
            ->where('employee', '[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}')
            ->name('api.employees.show');
        foreach (['departments' => 'department', 'designations' => 'designation'] as $path => $master) {
            Route::get('/api/'.$path, function (Request $request, CompanyContext $context) use ($master) {
                return app(OrganizationMasterController::class)->jsonIndex($request, $context, $master);
            })->name('api.'.$path.'.index');
            Route::get('/api/'.$path.'/{record}', function (CompanyContext $context, string $record) use ($master) {
                return app(OrganizationMasterController::class)->jsonShow($context, $master, $record);
            })->where('record', '[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}')
                ->name('api.'.$path.'.show');
        }
        Route::middleware(RequireLeavePositionWriter::class)->group(function () {
            Route::get('/api/leave-types', [LeaveTypeController::class, 'jsonIndex'])->name('api.leave-types.index');
            Route::post('/api/leave-types', [LeaveTypeController::class, 'jsonStore'])->name('api.leave-types.store');
            Route::get('/api/leave-types/{leaveType}', [LeaveTypeController::class, 'jsonShow'])
                ->where('leaveType', '[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}')
                ->name('api.leave-types.show');
            Route::put('/api/leave-types/{leaveType}', [LeaveTypeController::class, 'jsonUpdate'])
                ->where('leaveType', '[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}')
                ->name('api.leave-types.update');
            Route::get('/api/leave-positions', [LeavePositionController::class, 'jsonIndex'])->name('api.leave-positions.index');
            Route::post('/api/leave-positions', [LeavePositionController::class, 'jsonStore'])->name('api.leave-positions.store');
            Route::get('/api/leave-positions/{leavePosition}', [LeavePositionController::class, 'jsonShow'])
                ->where('leavePosition', '[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}')
                ->name('api.leave-positions.show');
            Route::put('/api/leave-positions/{leavePosition}', [LeavePositionController::class, 'jsonUpdate'])
                ->where('leavePosition', '[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}')
                ->name('api.leave-positions.update');
            Route::get('/api/leave-positions/{leavePosition}/movements', [LeavePositionController::class, 'jsonMovements'])
                ->where('leavePosition', '[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}')
                ->name('api.leave-positions.movements.index');
            Route::post('/api/leave-positions/{leavePosition}/movements', [LeavePositionController::class, 'jsonStoreMovement'])
                ->where('leavePosition', '[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}')
                ->name('api.leave-positions.movements.store');
        });
        Route::middleware(RequirePunchRecordingWriter::class)->group(function () {
            Route::get('/api/punch-events', [PunchEventController::class, 'jsonIndex'])->name('api.punch-events.index');
            Route::post('/api/punch-events', [PunchEventController::class, 'jsonStore'])->name('api.punch-events.store');
            Route::post('/api/punch-events/imports', [PunchEventController::class, 'jsonImport'])->name('api.punch-events.imports.store');
            Route::get('/api/punch-identities', [PunchIdentityController::class, 'jsonIndex'])->name('api.punch-identities.index');
            Route::post('/api/punch-identities', [PunchIdentityController::class, 'jsonStore'])->name('api.punch-identities.store');
            Route::get('/api/punch-identities/conflicts', [PunchIdentityController::class, 'jsonConflicts'])->name('api.punch-identities.conflicts');
            Route::get('/api/punch-identities/{identity}', [PunchIdentityController::class, 'jsonShow'])
                ->where('identity', '[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}')
                ->name('api.punch-identities.show');
            Route::post('/api/punch-identities/{identity}/end', [PunchIdentityController::class, 'jsonEnd'])
                ->where('identity', '[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}')
                ->name('api.punch-identities.end');
            Route::get('/api/punch-events/{punchEvent}', [PunchEventController::class, 'jsonShow'])
                ->where('punchEvent', '[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}')
                ->name('api.punch-events.show');
            Route::get('/api/attendance/day-results', [AttendanceDayResultController::class, 'jsonIndex'])->name('api.attendance.day-results.index');
            Route::post('/api/attendance/day-results', [AttendanceDayResultController::class, 'jsonCalculate'])->name('api.attendance.day-results.store');
            Route::get('/api/attendance/day-results/{employee}/{workDate}', [AttendanceDayResultController::class, 'jsonShow'])
                ->where([
                    'employee' => '[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}',
                    'workDate' => '[0-9]{4}-[0-9]{2}-[0-9]{2}',
                ])
                ->name('api.attendance.day-results.show');
            Route::post('/api/attendance/corrections', [AttendanceDayResultController::class, 'jsonStoreCorrection'])->name('api.attendance.corrections.store');
            Route::post('/api/attendance/corrections/{correction}/decision', [AttendanceDayResultController::class, 'jsonDecideCorrection'])
                ->where('correction', '[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}')
                ->name('api.attendance.corrections.decide');
            Route::get('/api/attendance/report', [AttendancePeriodController::class, 'jsonReport'])->name('api.attendance.report');
            Route::get('/api/attendance/dashboard', [AttendanceDashboardController::class, 'jsonDashboard'])->name('api.attendance.dashboard');
            Route::get('/api/attendance/device-log/preview', [AttendanceDashboardController::class, 'jsonDeviceLogPreview'])->name('api.attendance.device-log.preview');
            Route::post('/api/attendance/device-log/import', [AttendanceDashboardController::class, 'jsonDeviceLogImport'])->name('api.attendance.device-log.import');
            Route::get('/api/attendance/periods', [AttendancePeriodController::class, 'jsonIndex'])->name('api.attendance.periods.index');
            Route::post('/api/attendance/periods', [AttendancePeriodController::class, 'jsonStore'])->name('api.attendance.periods.store');
            Route::get('/api/attendance/periods/{period}', [AttendancePeriodController::class, 'jsonShow'])
                ->where('period', '[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}')
                ->name('api.attendance.periods.show');
            Route::get('/api/attendance/payroll-handoffs/{handoff}', [AttendancePeriodController::class, 'jsonShowHandoff'])
                ->where('handoff', '[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}')
                ->name('api.attendance.payroll-handoffs.show');
            Route::post('/api/attendance/missing-punch-requests', [AttendanceDayResultController::class, 'jsonStoreMissingPunch'])->name('api.attendance.missing-punch-requests.store');
        });
        Route::post('/api/attendance/periods/{period}/finalize', [AttendancePeriodController::class, 'jsonFinalize'])
            ->where('period', '[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}')
            ->middleware(RequireAttendancePayrollAction::class.':'.AttendancePayrollActionGrant::FINALIZE_PERIOD)
            ->name('api.attendance.periods.finalize');
        Route::post('/api/attendance/periods/{period}/reopen', [AttendancePeriodController::class, 'jsonReopen'])
            ->where('period', '[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}')
            ->middleware(RequireAttendancePayrollAction::class.':'.AttendancePayrollActionGrant::REOPEN_PERIOD)
            ->name('api.attendance.periods.reopen');
        Route::post('/api/attendance/periods/{period}/payroll-handoff', [AttendancePeriodController::class, 'jsonExport'])
            ->where('period', '[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}')
            ->middleware(RequireAttendancePayrollAction::class.':'.AttendancePayrollActionGrant::HANDOFF_PERIOD)
            ->name('api.attendance.periods.payroll-handoff');
        Route::post('/api/attendance/missing-punch-requests/{missingPunch}/decision', [AttendanceDayResultController::class, 'jsonDecideMissingPunch'])
            ->where('missingPunch', '[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}')
            ->middleware(RequireAttendancePayrollAction::class.':'.AttendancePayrollActionGrant::REVIEW_MISSING_PUNCH)
            ->name('api.attendance.missing-punch-requests.decide');
        Route::middleware(RequireAttendancePayrollAction::class.':'.AttendancePayrollActionGrant::CONSUME_HANDOFF)->group(function () {
            Route::get('/api/payroll/periods', [PayrollIntakeController::class, 'jsonIndex'])->name('api.payroll.periods.index');
            Route::post('/api/payroll/periods', [PayrollIntakeController::class, 'jsonStorePeriod'])->name('api.payroll.periods.store');
            Route::post('/api/payroll/periods/{payrollPeriod}/runs', [PayrollIntakeController::class, 'jsonStoreRun'])
                ->where('payrollPeriod', '[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}')
                ->name('api.payroll.runs.store');
            Route::get('/api/payroll/runs/{payrollRun}', [PayrollIntakeController::class, 'jsonShowRun'])
                ->where('payrollRun', '[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}')
                ->name('api.payroll.runs.show');
            Route::post('/api/payroll/runs/{payrollRun}/attendance', [PayrollIntakeController::class, 'jsonTakeAttendance'])
                ->where('payrollRun', '[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}')
                ->name('api.payroll.runs.attendance');
        });
        Route::middleware(RequireOrganizationMasters::class)->group(function () {
            foreach (['departments' => 'department', 'designations' => 'designation'] as $path => $master) {
                Route::post('/api/'.$path, function (StoreOrganizationMasterRequest $request, CompanyContext $context) use ($master) {
                    return app(OrganizationMasterController::class)->jsonStore($request, $context, $master);
                })->name('api.'.$path.'.store');
                Route::put('/api/'.$path.'/{record}', function (UpdateOrganizationMasterRequest $request, CompanyContext $context, string $record) use ($master) {
                    return app(OrganizationMasterController::class)->jsonUpdate($request, $context, $master, $record);
                })->where('record', '[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}')
                    ->name('api.'.$path.'.update');
            }
        });
        Route::middleware(RequireEmployeeManagement::class)->group(function () {
            Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
            Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
            Route::put('/api/employees/{employee}', [EmployeeController::class, 'jsonUpdate'])
                ->where('employee', '[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}')
                ->name('api.employees.update');
        });
        Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::middleware(RequireEmployeeManagement::class)->group(function () {
            Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
            Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
            Route::get('/employees/{employee}/episodes/create', [EmploymentEpisodeController::class, 'create'])->name('employees.episodes.create');
            Route::post('/employees/{employee}/episodes', [EmploymentEpisodeController::class, 'store'])->name('employees.episodes.store');
            Route::get('/employees/{employee}/episodes/{episode}/edit', [EmploymentEpisodeController::class, 'edit'])->name('employees.episodes.edit');
            Route::put('/employees/{employee}/episodes/{episode}', [EmploymentEpisodeController::class, 'update'])->name('employees.episodes.update');
        });
    });
});
