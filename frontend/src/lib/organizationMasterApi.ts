export type MasterKind = 'department' | 'designation';

export type MasterRecord = {
  id: string;
  code: string | null;
  name: string;
  status: 'Active' | 'withdrawn';
};

export type MasterChange = {
  previous_name: string | null;
  new_name: string | null;
  previous_status: string | null;
  new_status: string | null;
  actor_name: string | null;
  acted_at: string | null;
};

export type MasterPage = {
  company_name: string;
  can_manage_organization_masters: boolean;
  current_page: number;
  data: MasterRecord[];
  from: number | null;
  last_page: number;
  per_page: number;
  to: number | null;
  total: number;
};

export type MasterDetail = MasterRecord & {
  company_name: string;
  can_manage_organization_masters: boolean;
  changes: MasterChange[];
};

export type MasterListResult =
  | { status: 'ok'; page: MasterPage }
  | { status: 'unauthenticated' }
  | { status: 'forbidden'; message: string }
  | { status: 'error'; message: string }
  | { status: 'aborted' };

export type MasterDetailResult =
  | { status: 'ok'; record: MasterDetail }
  | { status: 'unauthenticated' }
  | { status: 'forbidden'; message: string }
  | { status: 'not_found' }
  | { status: 'error'; message: string }
  | { status: 'aborted' };

export type MasterWriteResult =
  | { status: 'ok'; record: MasterDetail }
  | { status: 'unauthenticated' }
  | { status: 'forbidden'; message: string }
  | { status: 'not_found' }
  | { status: 'invalid'; errors: Record<string, string[]> }
  | { status: 'error'; message: string }
  | { status: 'aborted' };

const unauthenticatedMessage = 'The Laravel session is not authenticated.';
const forbiddenMessage = 'This account has no valid company context or access.';
const errorMessage = 'The organization master could not be read.';
const saveErrorMessage = 'The organization master could not be saved.';

export function masterPath(kind: MasterKind): string {
  return kind === 'department' ? 'departments' : 'designations';
}

export function masterLabel(kind: MasterKind): string {
  return kind === 'department' ? 'Departments' : 'Designations';
}

export async function fetchMasterPage(kind: MasterKind, query: string, page: number, signal?: AbortSignal): Promise<MasterListResult> {
  const response = await request(listPath(kind, query, page), 'GET', undefined, signal);
  if (response.status !== 'ok') {
    return response.status === 'not_found' || response.status === 'invalid' ? { status: 'error', message: errorMessage } : response;
  }
  try {
    return { status: 'ok', page: parsePage(response.body) };
  } catch {
    return { status: 'error', message: errorMessage };
  }
}

export async function fetchMasterRecord(kind: MasterKind, id: string, signal?: AbortSignal): Promise<MasterDetailResult> {
  const response = await request(`/api/${masterPath(kind)}/${id}`, 'GET', undefined, signal);
  if (response.status !== 'ok') {
    if (response.status === 'invalid') {
      return { status: 'error', message: errorMessage };
    }
    return response;
  }
  try {
    return { status: 'ok', record: parseDetail(response.body) };
  } catch {
    return { status: 'error', message: errorMessage };
  }
}

export async function createMasterRecord(kind: MasterKind, input: { name: string; code: string; status: string }): Promise<MasterWriteResult> {
  const response = await request(`/api/${masterPath(kind)}`, 'POST', {
    name: input.name,
    code: input.code.trim() === '' ? null : input.code.trim(),
    status: input.status,
  });
  return writeResult(response);
}

export async function updateMasterRecord(kind: MasterKind, id: string, input: { name: string; status: string }): Promise<MasterWriteResult> {
  const response = await request(`/api/${masterPath(kind)}/${id}`, 'PUT', {
    name: input.name,
    status: input.status,
  });
  return writeResult(response);
}

function listPath(kind: MasterKind, query: string, page: number): string {
  const params = new URLSearchParams();
  if (query !== '') {
    params.set('q', query);
  }
  if (page > 1) {
    params.set('page', String(page));
  }
  const value = params.toString();
  const path = `/api/${masterPath(kind)}`;
  return value === '' ? path : `${path}?${value}`;
}

async function writeResult(response: RequestOutcome): Promise<MasterWriteResult> {
  if (response.status !== 'ok') {
    return response;
  }
  try {
    return { status: 'ok', record: parseDetail(response.body) };
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

function parsePage(body: unknown): MasterPage {
  if (!body || typeof body !== 'object') {
    throw new Error('master');
  }
  const value = body as Record<string, unknown>;
  if (typeof value.company_name !== 'string' || typeof value.can_manage_organization_masters !== 'boolean' || !Array.isArray(value.data)) {
    throw new Error('master');
  }
  return {
    company_name: value.company_name,
    can_manage_organization_masters: value.can_manage_organization_masters,
    current_page: requiredNumber(value.current_page),
    data: value.data.map(parseRecord),
    from: optionalNumber(value.from),
    last_page: requiredNumber(value.last_page),
    per_page: requiredNumber(value.per_page),
    to: optionalNumber(value.to),
    total: requiredNumber(value.total),
  };
}

function parseDetail(body: unknown): MasterDetail {
  const record = parseRecord(body);
  if (!body || typeof body !== 'object') {
    throw new Error('master');
  }
  const value = body as Record<string, unknown>;
  if (typeof value.company_name !== 'string' || typeof value.can_manage_organization_masters !== 'boolean' || !Array.isArray(value.changes)) {
    throw new Error('master');
  }
  return {
    ...record,
    company_name: value.company_name,
    can_manage_organization_masters: value.can_manage_organization_masters,
    changes: value.changes.map(parseChange),
  };
}

function parseRecord(body: unknown): MasterRecord {
  if (!body || typeof body !== 'object') {
    throw new Error('master');
  }
  const value = body as Record<string, unknown>;
  if (typeof value.id !== 'string' || typeof value.name !== 'string' || (value.status !== 'Active' && value.status !== 'withdrawn') || (value.code !== null && typeof value.code !== 'string')) {
    throw new Error('master');
  }
  return { id: value.id, code: value.code, name: value.name, status: value.status };
}

function parseChange(body: unknown): MasterChange {
  if (!body || typeof body !== 'object') {
    throw new Error('master');
  }
  const value = body as Record<string, unknown>;
  if (!isOptionalText(value.previous_name) || !isOptionalText(value.new_name) || !isOptionalText(value.previous_status) || !isOptionalText(value.new_status) || !isOptionalText(value.actor_name) || !isOptionalText(value.acted_at)) {
    throw new Error('master');
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

function isOptionalText(value: unknown): value is string | null {
  return value === null || typeof value === 'string';
}

function requiredNumber(value: unknown): number {
  if (typeof value !== 'number' || !Number.isFinite(value)) {
    throw new Error('master');
  }
  return value;
}

function optionalNumber(value: unknown): number | null {
  if (value === null) {
    return null;
  }
  return requiredNumber(value);
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

export const masterMessages = {
  unauthenticated: unauthenticatedMessage,
  forbidden: forbiddenMessage,
  error: errorMessage,
  saveError: saveErrorMessage,
  notFound: 'This record is not available in the active company.',
  saved: 'Saved.',
};
