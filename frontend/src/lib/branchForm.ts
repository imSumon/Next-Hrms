import type { Branch, BranchStatus } from '../data/fixtures';

export const branchStatuses: BranchStatus[] = ['Active', 'withdrawn'];

export const duplicateBranchCodeMessage = 'This branch code is already used in this company.';

export type BranchErrors = {
  name?: string;
  code?: string;
  status?: string;
};

export function branchErrorList(errors: BranchErrors): string[] {
  return [errors.name, errors.code, errors.status].filter((error): error is string => Boolean(error));
}

export function validateNewBranch(
  nameRaw: string,
  codeRaw: string,
  rows: Branch[],
  companyId: string,
): { name: string; code: string | null; errors: BranchErrors } {
  const name = nameRaw.trim();
  const code = codeRaw.trim();
  const errors: BranchErrors = {};

  if (name === '') {
    errors.name = 'Branch name is required.';
  }
  if (code !== '' && rows.some((row) => row.companyId === companyId && row.code === code)) {
    errors.code = duplicateBranchCodeMessage;
  }

  return { name, code: code === '' ? null : code, errors };
}

export function validateBranchEdit(
  nameRaw: string,
  statusRaw: string,
): { name: string; status: BranchStatus; errors: BranchErrors } {
  const name = nameRaw.trim();
  const errors: BranchErrors = {};

  if (name === '') {
    errors.name = 'Branch name is required.';
  }
  const status = branchStatuses.find((value) => value === statusRaw);
  if (!status) {
    errors.status = 'Branch status must be Active or withdrawn.';
  }

  return { name, status: status ?? 'Active', errors };
}
