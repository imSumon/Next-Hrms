export type DirectoryEmployee = {
  id: string;
  employee_code: string;
  display_name: string;
};

export type DirectoryPage = {
  company_name: string;
  current_page: number;
  data: DirectoryEmployee[];
  from: number | null;
  last_page: number;
  per_page: number;
  to: number | null;
  total: number;
};

export type DirectoryResult =
  | { status: 'ok'; page: DirectoryPage }
  | { status: 'unauthenticated' }
  | { status: 'forbidden'; message: string }
  | { status: 'error'; message: string }
  | { status: 'aborted' };

const unauthenticatedMessage = 'The Laravel session is not authenticated.';
const forbiddenMessage = 'This account has no valid company context or access.';
const errorMessage = 'The employee directory could not be read.';

export function buildDirectoryPath(query: string, page: number): string {
  const params = new URLSearchParams();
  if (query !== '') {
    params.set('q', query);
  }
  if (page > 1) {
    params.set('page', String(page));
  }
  const value = params.toString();
  return value === '' ? '/api/employees' : `/api/employees?${value}`;
}

export async function fetchEmployeeDirectory(query: string, page: number, signal?: AbortSignal): Promise<DirectoryResult> {
  let response: Response;
  try {
    response = await fetch(buildDirectoryPath(query, page), {
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
    return { status: 'forbidden', message: await forbiddenText(response) };
  }

  if (!response.ok) {
    return { status: 'error', message: errorMessage };
  }

  try {
    return { status: 'ok', page: parseDirectoryPage(await response.json()) };
  } catch (error) {
    if (error instanceof DOMException && error.name === 'AbortError') {
      return { status: 'aborted' };
    }
    return { status: 'error', message: errorMessage };
  }
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

function parseDirectoryPage(body: unknown): DirectoryPage {
  if (!body || typeof body !== 'object') {
    throw new Error('directory');
  }
  const value = body as Record<string, unknown>;
  if (typeof value.company_name !== 'string' || !Array.isArray(value.data)) {
    throw new Error('directory');
  }
  return {
    company_name: value.company_name,
    current_page: requiredNumber(value.current_page),
    data: value.data.map(parseEmployee),
    from: optionalNumber(value.from),
    last_page: requiredNumber(value.last_page),
    per_page: requiredNumber(value.per_page),
    to: optionalNumber(value.to),
    total: requiredNumber(value.total),
  };
}

function parseEmployee(value: unknown): DirectoryEmployee {
  if (!value || typeof value !== 'object') {
    throw new Error('directory');
  }
  const row = value as Record<string, unknown>;
  if (typeof row.id !== 'string' || typeof row.employee_code !== 'string' || typeof row.display_name !== 'string') {
    throw new Error('directory');
  }
  return {
    id: row.id,
    employee_code: row.employee_code,
    display_name: row.display_name,
  };
}

function requiredNumber(value: unknown): number {
  if (typeof value !== 'number' || !Number.isFinite(value)) {
    throw new Error('directory');
  }
  return value;
}

function optionalNumber(value: unknown): number | null {
  if (value === null) {
    return null;
  }
  return requiredNumber(value);
}

export const directoryMessages = {
  unauthenticated: unauthenticatedMessage,
  forbidden: forbiddenMessage,
  error: errorMessage,
};
