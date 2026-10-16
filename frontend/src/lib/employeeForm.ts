import type { Employee } from '../data/fixtures';

export type IdentityErrors = {
  employee_code?: string;
  display_name?: string;
};

export const duplicateCodeMessage =
  'This employee code is already used in this company. Separation does not release a code for reuse.';

export function validateNewEmployee(
  codeRaw: string,
  nameRaw: string,
  rows: Employee[],
  companyId: string,
): { code: string; displayName: string; errors: IdentityErrors } {
  const code = codeRaw.trim();
  const displayName = nameRaw.trim();
  const errors: IdentityErrors = {};

  if (code === '') {
    errors.employee_code = 'Employee code is required.';
  }
  if (displayName === '') {
    errors.display_name = 'Display name is required.';
  }
  if (!errors.employee_code && rows.some((row) => row.companyId === companyId && row.code === code)) {
    errors.employee_code = duplicateCodeMessage;
  }

  return { code, displayName, errors };
}

export function validateDisplayName(nameRaw: string): { displayName: string; errors: IdentityErrors } {
  const displayName = nameRaw.trim();
  const errors: IdentityErrors = {};
  if (displayName === '') {
    errors.display_name = 'Display name is required.';
  }
  return { displayName, errors };
}

export function identityErrorList(errors: IdentityErrors): string[] {
  return [errors.employee_code, errors.display_name].filter((error): error is string => Boolean(error));
}
