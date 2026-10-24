export type LeaveTypeStatus = 'Active' | 'withdrawn';
export type LeavePositionStatus = 'proposed' | 'excepted';

export type LeaveTypeRecord = {
  id: string;
  name: string;
  status: LeaveTypeStatus;
};

export type LeaveTypeChange = {
  previous_name: string | null;
  new_name: string | null;
  previous_status: string | null;
  new_status: string | null;
  actor_name: string | null;
  acted_at: string | null;
};

export type LeaveTypeDetail = LeaveTypeRecord & {
  company_name: string;
  changes: LeaveTypeChange[];
};

export type LeaveTypeList = {
  company_name: string;
  data: LeaveTypeRecord[];
};

export type LeavePositionRecord = {
  id: string;
  employee_id: string;
  leave_type_id: string;
  proposed_amount: string;
  period_label: string | null;
  period_start: string | null;
  period_end: string | null;
  source_evidence: string;
  stated_basis: string;
  status: LeavePositionStatus;
  confirmation_recorded_at: null;
  confirmation_actor_id: null;
  confirmation_decision: null;
  usable_as_approved_balance: false;
};

export type LeavePositionList = {
  company_name: string;
  data: LeavePositionRecord[];
};

export type LeavePositionInput = {
  employee_id: string;
  leave_type_id: string;
  proposed_amount: string;
  period_label: string;
  period_start: string;
  period_end: string;
  source_evidence: string;
  stated_basis: string;
  status: LeavePositionStatus;
};

export type LeavePositionUpdateInput = Omit<LeavePositionInput, 'employee_id' | 'leave_type_id'>;

export type LeaveMovementRecord = {
  id: string;
  leave_position_id: string;
  amount: string;
  direction: string;
  related_reference: string | null;
  reverses_movement_id: string | null;
  acting_user_id: string;
  recorded_at: string | null;
  usable_as_approved_balance: false;
};

export type LeaveMovementList = {
  leave_position_id: string;
  usable_as_approved_balance: false;
  data: LeaveMovementRecord[];
};

export type LeaveMovementInput = {
  amount: string;
  direction: string;
  related_reference: string;
  reverses_movement_id: string;
};

export type LeaveListResult<T> =
  | { status: 'ok'; page: T }
  | { status: 'unauthenticated' }
  | { status: 'forbidden'; message: string }
  | { status: 'error'; message: string }
  | { status: 'aborted' };

export type LeaveDetailResult<T> =
  | { status: 'ok'; record: T }
  | { status: 'unauthenticated' }
  | { status: 'forbidden'; message: string }
  | { status: 'not_found' }
  | { status: 'error'; message: string }
  | { status: 'aborted' };

export type LeaveWriteResult<T> =
  | { status: 'ok'; record: T }
  | { status: 'unauthenticated' }
  | { status: 'forbidden'; message: string }
  | { status: 'not_found' }
  | { status: 'invalid'; errors: Record<string, string[]> }
  | { status: 'error'; message: string }
  | { status: 'aborted' };

const unauthenticatedMessage = 'The Laravel session is not authenticated.';
const forbiddenMessage = 'Leave-position records are not authorized.';
const errorMessage = 'The leave-position records could not be read.';
const saveErrorMessage = 'The leave-position record could not be saved.';

export async function fetchLeaveTypes(signal?: AbortSignal): Promise<LeaveListResult<LeaveTypeList>> {
  return listResult(await request('/api/leave-types', 'GET', undefined, signal), parseLeaveTypeList, errorMessage);
}

export async function fetchLeaveType(id: string, signal?: AbortSignal): Promise<LeaveDetailResult<LeaveTypeDetail>> {
  return detailResult(await request(`/api/leave-types/${id}`, 'GET', undefined, signal), parseLeaveTypeDetail, errorMessage);
}

export async function createLeaveType(input: { name: string; status: string }): Promise<LeaveWriteResult<LeaveTypeDetail>> {
  return writeResult(await request('/api/leave-types', 'POST', { name: input.name, status: input.status }), parseLeaveTypeDetail);
}

export async function updateLeaveType(id: string, input: { name: string; status: string }): Promise<LeaveWriteResult<LeaveTypeDetail>> {
  return writeResult(await request(`/api/leave-types/${id}`, 'PUT', { name: input.name, status: input.status }), parseLeaveTypeDetail);
}

export async function fetchLeavePositions(signal?: AbortSignal): Promise<LeaveListResult<LeavePositionList>> {
  return listResult(await request('/api/leave-positions', 'GET', undefined, signal), parseLeavePositionList, errorMessage);
}

export async function fetchLeavePosition(id: string, signal?: AbortSignal): Promise<LeaveDetailResult<LeavePositionRecord>> {
  return detailResult(await request(`/api/leave-positions/${id}`, 'GET', undefined, signal), parseLeavePosition, errorMessage);
}

export async function createLeavePosition(input: LeavePositionInput): Promise<LeaveWriteResult<LeavePositionRecord>> {
  return writeResult(await request('/api/leave-positions', 'POST', positionPayload(input, true)), parseLeavePosition);
}

export async function updateLeavePosition(id: string, input: LeavePositionUpdateInput): Promise<LeaveWriteResult<LeavePositionRecord>> {
  return writeResult(await request(`/api/leave-positions/${id}`, 'PUT', positionPayload(input, false)), parseLeavePosition);
}

export async function fetchLeaveMovements(positionId: string, signal?: AbortSignal): Promise<LeaveListResult<LeaveMovementList>> {
  return listResult(await request(`/api/leave-positions/${positionId}/movements`, 'GET', undefined, signal), parseLeaveMovementList, errorMessage);
}

export async function createLeaveMovement(positionId: string, input: LeaveMovementInput): Promise<LeaveWriteResult<LeaveMovementRecord>> {
  const body: Record<string, string | null> = {
    amount: input.amount,
    direction: input.direction,
    related_reference: emptyToNull(input.related_reference),
    reverses_movement_id: emptyToNull(input.reverses_movement_id),
  };
  return writeResult(await request(`/api/leave-positions/${positionId}/movements`, 'POST', body), parseLeaveMovement);
}

function positionPayload(input: LeavePositionUpdateInput | LeavePositionInput, includeIdentity: boolean): Record<string, string | null> {
  const payload: Record<string, string | null> = {
    proposed_amount: input.proposed_amount,
    period_label: emptyToNull(input.period_label),
    period_start: emptyToNull(input.period_start),
    period_end: emptyToNull(input.period_end),
    source_evidence: input.source_evidence,
    stated_basis: input.stated_basis,
    status: input.status,
  };
  if (includeIdentity && 'employee_id' in input && 'leave_type_id' in input) {
    payload.employee_id = input.employee_id;
    payload.leave_type_id = input.leave_type_id;
  }
  return payload;
}

function emptyToNull(value: string): string | null {
  return value.trim() === '' ? null : value.trim();
}

async function listResult<T>(response: RequestOutcome, parse: (body: unknown) => T, message: string): Promise<LeaveListResult<T>> {
  if (response.status !== 'ok') {
    return response.status === 'not_found' || response.status === 'invalid' ? { status: 'error', message } : response;
  }
  try {
    return { status: 'ok', page: parse(response.body) };
  } catch {
    return { status: 'error', message };
  }
}

async function detailResult<T>(response: RequestOutcome, parse: (body: unknown) => T, message: string): Promise<LeaveDetailResult<T>> {
  if (response.status !== 'ok') {
    return response.status === 'invalid' ? { status: 'error', message } : response;
  }
  try {
    return { status: 'ok', record: parse(response.body) };
  } catch {
    return { status: 'error', message };
  }
}

async function writeResult<T>(response: RequestOutcome, parse: (body: unknown) => T): Promise<LeaveWriteResult<T>> {
  if (response.status !== 'ok') {
    return response;
  }
  try {
    return { status: 'ok', record: parse(response.body) };
  } catch {
    return { status: 'error', message: saveErrorMessage };
  }
}

type RequestOutcome =
  | { status: 'ok'; body: unknown }
  | { status: 'unauthenticated' }
  | { status: 'forbidden'; message: string }
  | { status: 'not_found' }
  | { status: 'invalid'; errors: Record<string, string[]> }
  | { status: 'error'; message: string }
  | { status: 'aborted' };

async function request(path: string, method: 'GET' | 'POST' | 'PUT', body?: unknown, signal?: AbortSignal): Promise<RequestOutcome> {
  let response: Response;
  try {
    response = await fetch(path, {
      method,
      headers: {
        Accept: 'application/json',
        ...(body === undefined ? {} : { 'Content-Type': 'application/json', ...xsrfHeader() }),
      },
      credentials: 'same-origin',
      body: body === undefined ? undefined : JSON.stringify(body),
      signal,
    });
  } catch (error) {
    if (error instanceof DOMException && error.name === 'AbortError') {
      return { status: 'aborted' };
    }
    return { status: 'error', message: method === 'GET' ? errorMessage : saveErrorMessage };
  }

  if (response.status === 401) {
    return { status: 'unauthenticated' };
  }
  if (response.status === 403) {
    return { status: 'forbidden', message: await forbiddenText(response) };
  }
  if (response.status === 404) {
    return { status: 'not_found' };
  }
  if (response.status === 422) {
    return { status: 'invalid', errors: await validationErrors(response) };
  }
  if (!response.ok) {
    return { status: 'error', message: method === 'GET' ? errorMessage : saveErrorMessage };
  }
  try {
    return { status: 'ok', body: await response.json() };
  } catch (error) {
    if (error instanceof DOMException && error.name === 'AbortError') {
      return { status: 'aborted' };
    }
    return { status: 'error', message: method === 'GET' ? errorMessage : saveErrorMessage };
  }
}

function parseLeaveTypeList(body: unknown): LeaveTypeList {
  if (!body || typeof body !== 'object') {
    throw new Error('leave');
  }
  const value = body as Record<string, unknown>;
  if (typeof value.company_name !== 'string' || !Array.isArray(value.data)) {
    throw new Error('leave');
  }
  return { company_name: value.company_name, data: value.data.map(parseLeaveTypeRow) };
}

function parseLeaveTypeDetail(body: unknown): LeaveTypeDetail {
  const record = parseLeaveTypeRow(body);
  if (!body || typeof body !== 'object') {
    throw new Error('leave');
  }
  const value = body as Record<string, unknown>;
  if (typeof value.company_name !== 'string' || !Array.isArray(value.changes)) {
    throw new Error('leave');
  }
  return {
    ...record,
    company_name: value.company_name,
    changes: value.changes.map(parseLeaveTypeChange),
  };
}

function parseLeaveTypeRow(body: unknown): LeaveTypeRecord {
  if (!body || typeof body !== 'object') {
    throw new Error('leave');
  }
  const value = body as Record<string, unknown>;
  if (typeof value.id !== 'string' || typeof value.name !== 'string' || (value.status !== 'Active' && value.status !== 'withdrawn')) {
    throw new Error('leave');
  }
  return { id: value.id, name: value.name, status: value.status };
}

function parseLeaveTypeChange(body: unknown): LeaveTypeChange {
  if (!body || typeof body !== 'object') {
    throw new Error('leave');
  }
  const value = body as Record<string, unknown>;
  if (!isOptionalText(value.previous_name) || !isOptionalText(value.new_name) || !isOptionalText(value.previous_status) || !isOptionalText(value.new_status) || !isOptionalText(value.actor_name) || !isOptionalText(value.acted_at)) {
    throw new Error('leave');
  }
  return {
    previous_name: value.previous_name,
    new_name: value.new_name,
    previous_status: value.previous_status,
    new_status: value.new_status,
    actor_name: value.actor_name,
    acted_at: value.acted_at,
  };
}

function parseLeavePositionList(body: unknown): LeavePositionList {
  if (!body || typeof body !== 'object') {
    throw new Error('leave');
  }
  const value = body as Record<string, unknown>;
  if (typeof value.company_name !== 'string' || !Array.isArray(value.data)) {
    throw new Error('leave');
  }
  return { company_name: value.company_name, data: value.data.map(parseLeavePosition) };
}

function parseLeavePosition(body: unknown): LeavePositionRecord {
  if (!body || typeof body !== 'object') {
    throw new Error('leave');
  }
  const value = body as Record<string, unknown>;
  if (
    typeof value.id !== 'string' ||
    typeof value.employee_id !== 'string' ||
    typeof value.leave_type_id !== 'string' ||
    typeof value.proposed_amount !== 'string' ||
    !isOptionalText(value.period_label) ||
    !isOptionalText(value.period_start) ||
    !isOptionalText(value.period_end) ||
    typeof value.source_evidence !== 'string' ||
    typeof value.stated_basis !== 'string' ||
    (value.status !== 'proposed' && value.status !== 'excepted') ||
    value.confirmation_recorded_at !== null ||
    value.confirmation_actor_id !== null ||
    value.confirmation_decision !== null ||
    value.usable_as_approved_balance !== false
  ) {
    throw new Error('leave');
  }
  return {
    id: value.id,
    employee_id: value.employee_id,
    leave_type_id: value.leave_type_id,
    proposed_amount: value.proposed_amount,
    period_label: value.period_label,
    period_start: value.period_start,
    period_end: value.period_end,
    source_evidence: value.source_evidence,
    stated_basis: value.stated_basis,
    status: value.status,
    confirmation_recorded_at: null,
    confirmation_actor_id: null,
    confirmation_decision: null,
    usable_as_approved_balance: false,
  };
}

function parseLeaveMovementList(body: unknown): LeaveMovementList {
  if (!body || typeof body !== 'object') {
    throw new Error('leave');
  }
  const value = body as Record<string, unknown>;
  if (typeof value.leave_position_id !== 'string' || value.usable_as_approved_balance !== false || !Array.isArray(value.data)) {
    throw new Error('leave');
  }
  return {
    leave_position_id: value.leave_position_id,
    usable_as_approved_balance: false,
    data: value.data.map(parseLeaveMovement),
  };
}

function parseLeaveMovement(body: unknown): LeaveMovementRecord {
  if (!body || typeof body !== 'object') {
    throw new Error('leave');
  }
  const value = body as Record<string, unknown>;
  if (
    typeof value.id !== 'string' ||
    typeof value.leave_position_id !== 'string' ||
    typeof value.amount !== 'string' ||
    typeof value.direction !== 'string' ||
    !isOptionalText(value.related_reference) ||
    !isOptionalText(value.reverses_movement_id) ||
    typeof value.acting_user_id !== 'string' ||
    !isOptionalText(value.recorded_at) ||
    value.usable_as_approved_balance !== false
  ) {
    throw new Error('leave');
  }
  return {
    id: value.id,
    leave_position_id: value.leave_position_id,
    amount: value.amount,
    direction: value.direction,
    related_reference: value.related_reference,
    reverses_movement_id: value.reverses_movement_id,
    acting_user_id: value.acting_user_id,
    recorded_at: value.recorded_at,
    usable_as_approved_balance: false,
  };
}

function isOptionalText(value: unknown): value is string | null {
  return value === null || typeof value === 'string';
}

async function forbiddenText(response: Response): Promise<string> {
  try {
    const body: unknown = await response.json();
    if (body && typeof body === 'object' && 'message' in body && typeof body.message === 'string' && body.message !== '') {
      return body.message;
    }
  } catch {
    return forbiddenMessage;
  }
  return forbiddenMessage;
}

async function validationErrors(response: Response): Promise<Record<string, string[]>> {
  try {
    const body: unknown = await response.json();
    if (!body || typeof body !== 'object' || !('errors' in body) || !body.errors || typeof body.errors !== 'object') {
      return { name: [saveErrorMessage] };
    }
    const errors: Record<string, string[]> = {};
    for (const [field, messages] of Object.entries(body.errors)) {
      if (Array.isArray(messages)) {
        const text = messages.filter((message): message is string => typeof message === 'string' && message !== '');
        if (text.length > 0) {
          errors[field] = text;
        }
      }
    }
    return Object.keys(errors).length > 0 ? errors : { name: [saveErrorMessage] };
  } catch {
    return { name: [saveErrorMessage] };
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

export const leaveMessages = {
  unauthenticated: unauthenticatedMessage,
  forbidden: forbiddenMessage,
  error: errorMessage,
  saveError: saveErrorMessage,
  notFound: 'This record is not available in the active company.',
  saved: 'Saved.',
};
