import type { Employee } from '../data/fixtures';

export const PAGE_SIZE = 15;

export function filterEmployees(rows: Employee[], companyId: string, rawQuery: string): Employee[] {
  const scoped = rows
    .filter((row) => row.companyId === companyId)
    .slice()
    .sort((left, right) => left.code.localeCompare(right.code, 'en', { numeric: true }) || left.id.localeCompare(right.id));

  const term = rawQuery.trim().toLocaleLowerCase();
  if (term === '') {
    return scoped;
  }

  return scoped.filter(
    (row) => row.code.toLocaleLowerCase().includes(term) || row.displayName.toLocaleLowerCase().includes(term),
  );
}

export function emptyDirectoryMessage(rawQuery: string): string {
  return rawQuery === '' ? 'No employees in this company.' : 'No employees match this search.';
}

export function pageCount(total: number): number {
  return Math.max(1, Math.ceil(total / PAGE_SIZE));
}

export function pageSlice<T>(rows: T[], page: number): T[] {
  const start = (page - 1) * PAGE_SIZE;
  return rows.slice(start, start + PAGE_SIZE);
}
