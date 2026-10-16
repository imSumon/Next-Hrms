import type { EmploymentEpisode, EpisodeStatus } from '../data/fixtures';

export const episodeStatuses: EpisodeStatus[] = ['Open', 'inactive', 'separated'];

export const reopenMessage =
  'This inactive or separated episode cannot be set back to Open. Rejoining keeps the same employee and employee code, creates a new episode, and leaves this episode unchanged.';

export const secondOpenMessage =
  'This employee already has an Open episode. Automatic separation or rejoining is not available.';

export const openLastWorkingDateMessage = 'An Open episode cannot have a last working date.';

export const pendingEpisodePolicyNote =
  'Date overlap, date order, and the pay effect of inactive are not approved rules. This screen does not apply them.';

export const rejoinGuidance =
  'Rejoining keeps the same employee identity and employee code, creates a new employment episode, and leaves the previous episode unchanged. A second Open episode is refused.';

export type EpisodeErrors = {
  joining_date?: string;
  episode_status?: string;
  last_working_date?: string;
};

export type EpisodeDraft = {
  joiningDate: string;
  lastWorkingDate: string;
  status: string;
};

export function episodeErrorList(errors: EpisodeErrors): string[] {
  return [errors.joining_date, errors.episode_status, errors.last_working_date].filter((error): error is string => Boolean(error));
}

export function statusesForEdit(current: EpisodeStatus | null): EpisodeStatus[] {
  if (current && current !== 'Open') {
    return ['inactive', 'separated'];
  }
  return episodeStatuses;
}

export function episodesForEmployee(rows: EmploymentEpisode[], companyId: string, employeeId: string): EmploymentEpisode[] {
  return rows
    .filter((row) => row.companyId === companyId && row.employeeId === employeeId)
    .slice()
    .sort((left, right) => left.joiningDate.localeCompare(right.joiningDate) || left.id.localeCompare(right.id));
}

function isIsoDate(value: string): boolean {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
    return false;
  }
  const year = Number(value.slice(0, 4));
  const month = Number(value.slice(5, 7));
  const day = Number(value.slice(8, 10));
  const date = new Date(Date.UTC(year, month - 1, day));
  return date.getUTCFullYear() === year && date.getUTCMonth() === month - 1 && date.getUTCDate() === day;
}

export function validateEpisode(
  draft: EpisodeDraft,
  rows: EmploymentEpisode[],
  companyId: string,
  employeeId: string,
  existing?: EmploymentEpisode,
): { joiningDate: string; lastWorkingDate: string | null; status: EpisodeStatus; errors: EpisodeErrors } {
  const joiningDate = draft.joiningDate.trim();
  const lastWorkingText = draft.lastWorkingDate.trim();
  const statusText = draft.status.trim();
  const errors: EpisodeErrors = {};

  if (joiningDate === '') {
    errors.joining_date = 'Joining date is required.';
  } else if (!isIsoDate(joiningDate)) {
    errors.joining_date = 'The joining date field must be a valid date.';
  }

  if (statusText === '') {
    errors.episode_status = 'Episode status is required.';
  } else if (!episodeStatuses.includes(statusText as EpisodeStatus)) {
    errors.episode_status = 'Episode status must be Open, inactive, or separated.';
  }

  let lastWorkingDate: string | null = null;
  if (lastWorkingText !== '') {
    if (!isIsoDate(lastWorkingText)) {
      errors.last_working_date = 'The last working date field must be a valid date.';
    } else {
      lastWorkingDate = lastWorkingText;
    }
  }

  const status = statusText as EpisodeStatus;
  if (!errors.episode_status && existing && existing.status !== 'Open' && status === 'Open') {
    errors.episode_status = reopenMessage;
  }

  if (!errors.episode_status && status === 'Open' && lastWorkingDate !== null) {
    errors.last_working_date = openLastWorkingDateMessage;
  } else if (!errors.episode_status && status === 'Open') {
    const otherOpen = rows.some(
      (row) =>
        row.companyId === companyId &&
        row.employeeId === employeeId &&
        row.status === 'Open' &&
        row.id !== existing?.id,
    );
    if (otherOpen) {
      errors.episode_status = secondOpenMessage;
    }
  }

  return {
    joiningDate,
    lastWorkingDate,
    status: episodeStatuses.includes(status) ? status : 'Open',
    errors,
  };
}
