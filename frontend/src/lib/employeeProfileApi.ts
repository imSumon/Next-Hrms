export type ProfileEpisode = {
  id: string;
  joining_date: string;
  last_working_date: string | null;
  episode_status: 'Open' | 'inactive' | 'separated';
};

export type DisplayNameChange = {
  previous_display_name: string | null;
  new_display_name: string | null;
  actor_name: string | null;
  acted_at: string | null;
};

export type EmployeeProfile = {
  id: string;
  employee_code: string;
  display_name: string;
  company_name: string;
  can_manage_employees: boolean;
  display_name_changes: DisplayNameChange[];
  episodes: ProfileEpisode[];
};

export type ProfileUpdateResult =
  | { status: 'ok'; profile: EmployeeProfile }
  | { status: 'invalid'; errors: Record<string, string[]> }
  | { status: 'unauthenticated' }
  | { status: 'forbidden'; message: string }
  | { status: 'not_found' }
  | { status: 'error'; message: string }
  | { status: 'aborted' };

export type ProfileResult =
  | { status: 'ok'; profile: EmployeeProfile }
  | { status: 'unauthenticated' }
  | { status: 'forbidden'; message: string }
  | { status: 'not_found' }
  | { status: 'error'; message: string }
  | { status: 'aborted' };

const unauthenticatedMessage = 'The Laravel session is not authenticated.';
const forbiddenMessage = 'This account has no valid company context or access.';
const notFoundMessage = 'This employee is not available in the active company.';
const errorMessage = 'The employee profile could not be read.';
const saveErrorMessage = 'The display name could not be saved.';

export function buildProfilePath(employeeId: string): string {
  return `/api/employees/${encodeURIComponent(employeeId)}`;
}

export async function fetchEmployeeProfile(employeeId: string, signal?: AbortSignal): Promise<ProfileResult> {
  let response: Response;
  try {
    response = await fetch(buildProfilePath(employeeId), {
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

  if (response.status === 404) {
    return { status: 'not_found' };
  }

  if (!response.ok) {
    return { status: 'error', message: errorMessage };
  }

  try {
    return { status: 'ok', profile: parseProfile(await response.json()) };
  } catch (error) {
    if (error instanceof DOMException && error.name === 'AbortError') {
      return { status: 'aborted' };
    }
    return { status: 'error', message: errorMessage };
  }
}

export async function updateEmployeeDisplayName(employeeId: string, displayName: string, signal?: AbortSignal): Promise<ProfileUpdateResult> {
  let response: Response;
  try {
    response = await fetch(buildProfilePath(employeeId), {
      method: 'PUT',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        ...xsrfHeader(),
      },
      credentials: 'same-origin',
      body: JSON.stringify({ display_name: displayName }),
      signal,
    });
  } catch (error) {
    if (error instanceof DOMException && error.name === 'AbortError') {
      return { status: 'aborted' };
    }
    return { status: 'error', message: saveErrorMessage };
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
    return { status: 'error', message: saveErrorMessage };
  }

  try {
    return { status: 'ok', profile: parseProfile(await response.json()) };
  } catch (error) {
    if (error instanceof DOMException && error.name === 'AbortError') {
      return { status: 'aborted' };
    }
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

async function validationErrors(response: Response): Promise<Record<string, string[]>> {
  try {
    const body: unknown = await response.json();
    if (!body || typeof body !== 'object' || !('errors' in body) || !body.errors || typeof body.errors !== 'object') {
      return { display_name: [saveErrorMessage] };
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
    return Object.keys(errors).length > 0 ? errors : { display_name: [saveErrorMessage] };
  } catch {
    return { display_name: [saveErrorMessage] };
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

function parseProfile(body: unknown): EmployeeProfile {
  if (!body || typeof body !== 'object') {
    throw new Error('profile');
  }
  const value = body as Record<string, unknown>;
  if (typeof value.id !== 'string' || typeof value.employee_code !== 'string' || typeof value.display_name !== 'string' || typeof value.company_name !== 'string' || typeof value.can_manage_employees !== 'boolean' || !Array.isArray(value.display_name_changes) || !Array.isArray(value.episodes)) {
    throw new Error('profile');
  }
  return {
    id: value.id,
    employee_code: value.employee_code,
    display_name: value.display_name,
    company_name: value.company_name,
    can_manage_employees: value.can_manage_employees,
    display_name_changes: value.display_name_changes.map(parseDisplayNameChange),
    episodes: value.episodes.map(parseEpisode),
  };
}

function parseDisplayNameChange(value: unknown): DisplayNameChange {
  if (!value || typeof value !== 'object') {
    throw new Error('profile');
  }
  const row = value as Record<string, unknown>;
  if (!isOptionalText(row.previous_display_name) || !isOptionalText(row.new_display_name) || !isOptionalText(row.actor_name) || !isOptionalText(row.acted_at)) {
    throw new Error('profile');
  }
  return {
    previous_display_name: row.previous_display_name,
    new_display_name: row.new_display_name,
    actor_name: row.actor_name,
    acted_at: row.acted_at,
  };
}

function isOptionalText(value: unknown): value is string | null {
  return value === null || typeof value === 'string';
}

function parseEpisode(value: unknown): ProfileEpisode {
  if (!value || typeof value !== 'object') {
    throw new Error('profile');
  }
  const row = value as Record<string, unknown>;
  if (typeof row.id !== 'string' || typeof row.joining_date !== 'string' || !isStatus(row.episode_status)) {
    throw new Error('profile');
  }
  if (row.last_working_date !== null && typeof row.last_working_date !== 'string') {
    throw new Error('profile');
  }
  return {
    id: row.id,
    joining_date: row.joining_date,
    last_working_date: row.last_working_date,
    episode_status: row.episode_status,
  };
}

function isStatus(value: unknown): value is ProfileEpisode['episode_status'] {
  return value === 'Open' || value === 'inactive' || value === 'separated';
}

export const profileMessages = {
  unauthenticated: unauthenticatedMessage,
  forbidden: forbiddenMessage,
  notFound: notFoundMessage,
  error: errorMessage,
  saveError: saveErrorMessage,
  saved: 'Display name saved.',
  saving: 'Saving the display name.',
  emptyEpisodes: 'No employment episodes.',
  episodesReadOnly: 'Employment episodes on this profile are read-only.',
  emptyNameHistory: 'No display-name changes.',
};
