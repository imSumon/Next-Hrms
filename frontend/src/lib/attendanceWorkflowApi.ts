export type AttendancePunch = {
  id: string;
  employee_id: string | null;
  identity_value: string | null;
  source_name: string | null;
  event_at: string | null;
  source_event_id: string | null;
};

export type AttendanceDaySummary = {
  employee_id: string;
  employee_code: string | null;
  display_name: string | null;
  work_date: string;
  current_version_id: string;
  version_count: number;
  status_code: string | null;
  incomplete: boolean;
  late_seconds: number | null;
  early_seconds: number | null;
  selected_in_event_id: string | null;
  selected_out_event_id: string | null;
  generation_kind: string | null;
};

export type AttendanceDayVersion = {
  id: string;
  previous_version_id: string | null;
  generation_kind: string;
  status_code: string | null;
  incomplete: boolean;
  late_seconds: number | null;
  early_seconds: number | null;
  shift_version_id: string | null;
  selected_in_event_id: string | null;
  selected_out_event_id: string | null;
  override_in_at: string | null;
  override_out_at: string | null;
  override_in_request_id: string | null;
  override_out_request_id: string | null;
  calculated_at: string | null;
};

export type MissingPunchRequest = {
  id: string;
  employee_id: string;
  work_date: string;
  punch_side: 'in' | 'out';
  requested_event_at: string | null;
  reason: string;
  request_state: 'submitted' | 'rejected' | 'applied';
  requested_by_user_id: string;
  requested_at: string | null;
  decided_by_user_id: string | null;
  decided_at: string | null;
  result_version_id: string;
  applied_version_id: string | null;
};

export type AttendanceCorrection = {
  id: string;
  employee_id: string;
  work_date: string;
  field_key: string;
  correction_state: string;
  reason: string;
  result_version_id: string;
  applied_version_id: string | null;
};

export type AttendanceDayDetail = {
  company_name: string;
  employee_id: string;
  work_date: string;
  current_version_id: string;
  version_added?: boolean;
  versions: AttendanceDayVersion[];
  corrections: AttendanceCorrection[];
  missing_punch_requests: MissingPunchRequest[];
};

export type AttendanceReadResult<T> =
  | { status: 'ok'; body: T }
  | { status: 'unauthenticated' }
  | { status: 'forbidden'; message: string }
  | { status: 'error'; message: string }
  | { status: 'aborted' };

export type AttendanceWriteResult<T> =
  | { status: 'ok'; body: T }
  | { status: 'unauthenticated' }
  | { status: 'forbidden'; message: string }
  | { status: 'invalid'; message: string }
  | { status: 'error'; message: string };

const unauthenticatedMessage = 'The Laravel session is not authenticated.';
const forbiddenMessage = 'Punch recording is not authorized. No writer has been named.';
const errorMessage = 'The attendance records could not be read.';
const saveErrorMessage = 'The attendance change could not be saved.';

export const attendanceMessages = {
  unauthenticated: unauthenticatedMessage,
  forbidden: forbiddenMessage,
  error: errorMessage,
  saveError: saveErrorMessage,
};

export function fetchAttendancePunches(signal?: AbortSignal): Promise<AttendanceReadResult<{ company_name: string; data: AttendancePunch[] }>> {
  return readJson('/api/punch-events', signal);
}

export function fetchAttendanceDays(signal?: AbortSignal): Promise<AttendanceReadResult<{ company_name: string; data: AttendanceDaySummary[] }>> {
  return readJson('/api/attendance/day-results', signal);
}

export function fetchAttendanceDay(employeeId: string, workDate: string, signal?: AbortSignal): Promise<AttendanceReadResult<AttendanceDayDetail>> {
  return readJson(`/api/attendance/day-results/${employeeId}/${workDate}`, signal);
}

export function calculateAttendanceDay(employeeId: string, workDate: string): Promise<AttendanceWriteResult<AttendanceDayDetail>> {
  return writeJson('/api/attendance/day-results', { employee_id: employeeId, work_date: workDate });
}

export function submitAttendanceCorrection(employeeId: string, workDate: string, statusCode: string, reason: string): Promise<AttendanceWriteResult<AttendanceCorrection>> {
  return writeJson('/api/attendance/corrections', {
    employee_id: employeeId,
    work_date: workDate,
    status_code: statusCode,
    reason,
  });
}

export function decideAttendanceCorrection(correctionId: string, decision: 'approved' | 'rejected'): Promise<AttendanceWriteResult<AttendanceCorrection>> {
  return writeJson(`/api/attendance/corrections/${correctionId}/decision`, { decision });
}

export function submitMissingPunch(employeeId: string, workDate: string, side: 'in' | 'out', requestedEventAt: string, reason: string): Promise<AttendanceWriteResult<MissingPunchRequest>> {
  return writeJson('/api/attendance/missing-punch-requests', {
    employee_id: employeeId,
    work_date: workDate,
    punch_side: side,
    requested_event_at: requestedEventAt,
    reason,
  });
}

export function decideMissingPunch(requestId: string, decision: 'approved' | 'rejected'): Promise<AttendanceWriteResult<MissingPunchRequest>> {
  return writeJson(`/api/attendance/missing-punch-requests/${requestId}/decision`, { decision });
}

export type PayrollRunSummary = {
  id: string;
  payroll_period_id: string;
  run_sequence: number;
  run_state: string;
  attendance_usage: string;
  attendance_payroll_handoff_id: string | null;
  attendance_taken_at: string | null;
  previous_run_id: string | null;
};

export type PayrollRunDetail = PayrollRunSummary & {
  created?: boolean;
  declared_inputs: string[];
  attendance_inputs: Array<{ employee_id: string; input_key: string; copied_value: number; attendance_payroll_handoff_id: string }>;
};

export type PayrollIntakeIndex = {
  company_name: string;
  input_keys: Record<string, string>;
  data: Array<{ id: string; period_start: string; period_end: string; label: string; runs: PayrollRunSummary[] }>;
  exported_handoffs: Array<{ id: string; attendance_period_id: string; employee_count: number; line_count: number }>;
};

export function fetchPayrollIntake(signal?: AbortSignal): Promise<AttendanceReadResult<PayrollIntakeIndex>> {
  return readJson('/api/payroll/periods', signal);
}

export function openPayrollPeriod(periodStart: string, periodEnd: string, label: string): Promise<AttendanceWriteResult<{ id: string }>> {
  return writeJson('/api/payroll/periods', { period_start: periodStart, period_end: periodEnd, label });
}

export function startPayrollRun(payrollPeriodId: string, inputKeys: string[]): Promise<AttendanceWriteResult<PayrollRunDetail>> {
  return writeJson(`/api/payroll/periods/${payrollPeriodId}/runs`, { input_keys: inputKeys });
}

export function takePayrollAttendance(payrollRunId: string, handoffId: string): Promise<AttendanceWriteResult<PayrollRunDetail>> {
  return writeJson(`/api/payroll/runs/${payrollRunId}/attendance`, { attendance_payroll_handoff_id: handoffId });
}

export function fetchPayrollRun(payrollRunId: string, signal?: AbortSignal): Promise<AttendanceReadResult<PayrollRunDetail>> {
  return readJson(`/api/payroll/runs/${payrollRunId}`, signal);
}

export type DeviceLogSourceState = {
  configured: boolean;
  connection: string | null;
  table: string;
  source_name: string;
  driver: string | null;
  status?: 'not_configured' | 'not_checked' | 'read' | 'refused' | 'unavailable';
  message?: string;
  row_count?: number;
  truncated?: boolean;
  counts?: Record<string, number>;
  writes_enabled?: boolean;
};

export type AttendanceDashboard = {
  company_name: string;
  policy_configured: boolean;
  timezone?: string;
  work_date: string | null;
  day?: Record<
    | 'expected_employee_days'
    | 'employees_with_punches'
    | 'raw_punch_events'
    | 'results_calculated'
    | 'expected_without_result'
    | 'no_punch_received'
    | 'awaiting_processing'
    | 'present'
    | 'calculated_absent'
    | 'incomplete'
    | 'non_working'
    | 'other_status'
    | 'late'
    | 'late_not_calculated'
    | 'early_departure'
    | 'early_not_calculated'
    | 'approved_corrections'
    | 'approved_missing_punch_times',
    number
  >;
  queue?: {
    from: string;
    to: string;
    basis: 'attendance_period' | 'calendar_month';
    pending_missing_punch_requests: number;
    pending_corrections: number;
    missing_punch_conflicts: number;
    expected_without_result: number;
    awaiting_processing: number;
  };
  period?: { id: string; period_start: string; period_end: string; period_state: string; finalization_blockers: string[] } | null;
  source: DeviceLogSourceState;
  ingestion: Array<{ source_name: string; events: number; latest_ingested_at: string | null; latest_event_at: string | null }>;
};

export type DeviceLogPreview = {
  company_name: string;
  source: DeviceLogSourceState;
  from?: string;
  to?: string;
  time_basis?: string;
  row_count?: number;
  truncated?: boolean;
  max_rows?: number;
  counts?: Record<string, number>;
  rows?: Array<{
    source_event_id: string;
    source_wall_time: string | null;
    event_at: string | null;
    identity_value: string | null;
    employee_id: string | null;
    employee_code: string | null;
    source_area: string | null;
    outcome: string;
    message: string | null;
  }>;
  writes_enabled: boolean;
  blocking_decisions: string[];
};

export function fetchAttendanceDashboard(date: string | null, signal?: AbortSignal): Promise<AttendanceReadResult<AttendanceDashboard>> {
  return readJson(date ? `/api/attendance/dashboard?date=${encodeURIComponent(date)}` : '/api/attendance/dashboard', signal);
}

export function fetchDeviceLogPreview(from: string, to: string, signal?: AbortSignal): Promise<AttendanceReadResult<DeviceLogPreview>> {
  return readJson(`/api/attendance/device-log/preview?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`, signal);
}

export type PunchIdentityRow = {
  id: string;
  employee_id: string;
  employee_code: string | null;
  display_name: string | null;
  identity_value: string;
  effective_from: string | null;
  effective_to: string | null;
  status: 'active' | 'ended' | 'scheduled';
  registered_in_another_company: boolean;
  warning: string | null;
};

export type PunchIdentityDetail = PunchIdentityRow & {
  punch_event_count: number;
  history: Array<{
    action_kind: string;
    acting_user_id: string | null;
    acting_user_name: string | null;
    acted_at: string | null;
    previous_value: Record<string, unknown> | null;
    new_value: Record<string, unknown> | null;
    reason: string | null;
  }>;
};

export type PunchIdentityConflicts = {
  company_name: string;
  documented_overlaps: Array<{ identity_value: string; effective_from: string | null; effective_to: string | null; reason: string }>;
  in_company: Array<{ identity_value: string; identity_ids: string[]; employee_codes: Array<string | null>; documented: boolean }>;
  other_company: Array<{ identity_value: string; identity_ids: string[]; shared_periods: Array<{ from: string; to: string | null }> }>;
};

export function fetchPunchIdentities(query: string, signal?: AbortSignal): Promise<AttendanceReadResult<{ company_name: string; data: PunchIdentityRow[] }>> {
  return readJson(query === '' ? '/api/punch-identities' : `/api/punch-identities?q=${encodeURIComponent(query)}`, signal);
}

export function fetchPunchIdentity(identityId: string, signal?: AbortSignal): Promise<AttendanceReadResult<PunchIdentityDetail>> {
  return readJson(`/api/punch-identities/${identityId}`, signal);
}

export function fetchPunchIdentityConflicts(signal?: AbortSignal): Promise<AttendanceReadResult<PunchIdentityConflicts>> {
  return readJson('/api/punch-identities/conflicts', signal);
}

export function registerPunchIdentity(employeeId: string, identityValue: string, effectiveFrom: string, effectiveTo: string, reference: string): Promise<AttendanceWriteResult<PunchIdentityRow>> {
  return writeJson('/api/punch-identities', {
    employee_id: employeeId,
    identity_value: identityValue,
    effective_from: effectiveFrom,
    effective_to: effectiveTo,
    reference,
  });
}

export function endPunchIdentity(identityId: string, effectiveTo: string, reason: string): Promise<AttendanceWriteResult<PunchIdentityRow>> {
  return writeJson(`/api/punch-identities/${identityId}/end`, { effective_to: effectiveTo, reason });
}

export type PunchLogRow = {
  employee_code: string;
  identity_value: string;
  event_at: string;
  source_event_id: string | null;
};

export type PunchLogReport = {
  committed: boolean;
  row_count: number;
  accepted_count: number;
  rejected_count: number;
  duplicate_count: number;
  rows: Array<PunchLogRow & { line: number; outcome: 'accepted' | 'rejected' | 'duplicate'; message: string | null; event_id: string | null }>;
};

export type AttendanceReportEmployee = {
  employee_id: string;
  employee_code: string | null;
  display_name: string | null;
  expected: number;
  processed: number;
  present: number;
  incomplete: number;
  non_working: number;
  other: number;
  late_seconds: number;
  corrected: number;
};

export type AttendanceReport = {
  from: string;
  to: string;
  employees: AttendanceReportEmployee[];
  totals: Record<'expected' | 'processed' | 'present' | 'incomplete' | 'non_working' | 'other' | 'late_seconds', number>;
  reconciled: boolean;
  exceptions: Array<{ employee_id: string; employee_code: string | null; work_date: string; status_code: string | null; incomplete: boolean; missing: string }>;
  missing_results: Array<{ employee_id: string; employee_code: string | null; work_date: string }>;
  pending_corrections: Array<{ id: string; employee_id: string; work_date: string }>;
  pending_missing_punch_requests: Array<{ id: string; employee_id: string; work_date: string; punch_side: string }>;
  missing_punch_conflicts: Array<{ employee_id: string; work_date: string; punch_sides: string[] }>;
  unprocessed_punches: Array<{ employee_id: string; work_date: string; event_count: number }>;
  raw_event_count: number;
  selected_event_count: number;
};

export type AttendancePeriodRow = {
  id: string;
  period_start: string;
  period_end: string;
  period_state: 'open' | 'finalized';
  finalized_at: string | null;
  reopened_at: string | null;
};

export type PayrollHandoffHeader = {
  id: string;
  attendance_period_id: string;
  handoff_state: 'exported' | 'superseded';
  exported_by_user_id: string;
  exported_at: string | null;
  employee_count: number;
  line_count: number;
  present_count: number;
  incomplete_count: number;
  non_working_count: number;
  other_status_count: number;
  late_seconds_total: number;
};

export type PayrollHandoff = PayrollHandoffHeader & {
  created?: boolean;
  lines: Array<{ employee_id: string; work_date: string; result_version_id: string; day_class: string; status_code: string | null; incomplete: boolean; late_seconds: number | null; early_seconds: number | null }>;
};

export type AttendancePeriodDetail = {
  company_name: string;
  period: AttendancePeriodRow;
  events: Array<{ event_kind: string; acting_user_id: string; reason: string | null; occurred_at: string | null }>;
  handoffs: PayrollHandoffHeader[];
  report: AttendanceReport;
};

export async function importPunchLog(sourceName: string, mode: 'preview' | 'commit', rows: PunchLogRow[]): Promise<AttendanceWriteResult<PunchLogReport>> {
  let response: Response;
  try {
    response = await fetch('/api/punch-events/imports', {
      method: 'POST',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json', ...xsrfHeader() },
      credentials: 'same-origin',
      body: JSON.stringify({ source_name: sourceName, mode, rows }),
    });
  } catch {
    return { status: 'error', message: saveErrorMessage };
  }
  if (response.status === 401) {
    return { status: 'unauthenticated' };
  }
  if (response.status === 403) {
    return { status: 'forbidden', message: await responseText(response, forbiddenMessage) };
  }
  try {
    const body = (await response.json()) as Partial<PunchLogReport> & { message?: string };
    if (Array.isArray(body.rows)) {
      return { status: 'ok', body: body as PunchLogReport };
    }
    return { status: 'invalid', message: typeof body.message === 'string' ? body.message : saveErrorMessage };
  } catch {
    return { status: 'error', message: saveErrorMessage };
  }
}

export function fetchAttendanceReport(from: string, to: string, signal?: AbortSignal): Promise<AttendanceReadResult<AttendanceReport & { company_name: string }>> {
  return readJson(`/api/attendance/report?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`, signal);
}

export function fetchAttendancePeriods(signal?: AbortSignal): Promise<AttendanceReadResult<{ company_name: string; data: AttendancePeriodRow[] }>> {
  return readJson('/api/attendance/periods', signal);
}

export function fetchAttendancePeriod(periodId: string, signal?: AbortSignal): Promise<AttendanceReadResult<AttendancePeriodDetail>> {
  return readJson(`/api/attendance/periods/${periodId}`, signal);
}

export function openAttendancePeriod(periodStart: string, periodEnd: string): Promise<AttendanceWriteResult<AttendancePeriodDetail>> {
  return writeJson('/api/attendance/periods', { period_start: periodStart, period_end: periodEnd });
}

export function finalizeAttendancePeriod(periodId: string): Promise<AttendanceWriteResult<AttendancePeriodDetail>> {
  return writeJson(`/api/attendance/periods/${periodId}/finalize`, {});
}

export function reopenAttendancePeriod(periodId: string, reason: string): Promise<AttendanceWriteResult<AttendancePeriodDetail>> {
  return writeJson(`/api/attendance/periods/${periodId}/reopen`, { reason });
}

export function exportPayrollHandoff(periodId: string): Promise<AttendanceWriteResult<PayrollHandoff>> {
  return writeJson(`/api/attendance/periods/${periodId}/payroll-handoff`, {});
}

export function fetchPayrollHandoff(handoffId: string, signal?: AbortSignal): Promise<AttendanceReadResult<PayrollHandoff>> {
  return readJson(`/api/attendance/payroll-handoffs/${handoffId}`, signal);
}

async function readJson<T>(path: string, signal?: AbortSignal): Promise<AttendanceReadResult<T>> {
  let response: Response;
  try {
    response = await fetch(path, {
      method: 'GET',
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
      signal,
    });
  } catch (error) {
    if (error instanceof DOMException && error.name === 'AbortError') {
      return { status: 'aborted' };
    }
    return { status: 'error', message: errorMessage };
  }
  if (response.status === 401) {
    return { status: 'unauthenticated' };
  }
  if (response.status === 403) {
    return { status: 'forbidden', message: await responseText(response, forbiddenMessage) };
  }
  if (!response.ok) {
    return { status: 'error', message: errorMessage };
  }
  try {
    return { status: 'ok', body: (await response.json()) as T };
  } catch (error) {
    if (error instanceof DOMException && error.name === 'AbortError') {
      return { status: 'aborted' };
    }
    return { status: 'error', message: errorMessage };
  }
}

async function writeJson<T>(path: string, body: Record<string, unknown>): Promise<AttendanceWriteResult<T>> {
  let response: Response;
  try {
    response = await fetch(path, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        ...xsrfHeader(),
      },
      credentials: 'same-origin',
      body: JSON.stringify(body),
    });
  } catch {
    return { status: 'error', message: saveErrorMessage };
  }
  if (response.status === 401) {
    return { status: 'unauthenticated' };
  }
  if (response.status === 403) {
    return { status: 'forbidden', message: await responseText(response, forbiddenMessage) };
  }
  if (response.status === 422) {
    return { status: 'invalid', message: await validationText(response) };
  }
  if (!response.ok) {
    return { status: 'error', message: saveErrorMessage };
  }
  try {
    return { status: 'ok', body: (await response.json()) as T };
  } catch {
    return { status: 'error', message: saveErrorMessage };
  }
}

function xsrfHeader(): Record<string, string> {
  const row = document.cookie.split('; ').find((part) => part.startsWith('XSRF-TOKEN='));
  if (!row) {
    return {};
  }
  const token = decodeURIComponent(row.slice('XSRF-TOKEN='.length));
  return token === '' ? {} : { 'X-XSRF-TOKEN': token };
}

async function responseText(response: Response, fallback: string): Promise<string> {
  try {
    const body = (await response.json()) as { message?: unknown };
    return typeof body.message === 'string' && body.message !== '' ? body.message : fallback;
  } catch {
    return fallback;
  }
}

async function validationText(response: Response): Promise<string> {
  try {
    const body = (await response.json()) as { message?: unknown; errors?: Record<string, unknown> };
    if (body.errors) {
      const messages = Object.values(body.errors)
        .flatMap((value) => (Array.isArray(value) ? value : []))
        .filter((message): message is string => typeof message === 'string' && message !== '');
      if (messages.length > 0) {
        return messages.join(' ');
      }
    }
    return typeof body.message === 'string' && body.message !== '' ? body.message : saveErrorMessage;
  } catch {
    return saveErrorMessage;
  }
}
