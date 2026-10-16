import type { Branch } from '../data/fixtures';

export function filterBranches(rows: Branch[], companyId: string, rawQuery: string): Branch[] {
  const scoped = rows
    .filter((row) => row.companyId === companyId)
    .slice()
    .sort((left, right) => left.name.localeCompare(right.name, 'en', { numeric: true }) || left.id.localeCompare(right.id));

  const term = rawQuery.trim().toLocaleLowerCase();
  if (term === '') {
    return scoped;
  }

  return scoped.filter((row) => {
    const code = row.code?.toLocaleLowerCase() ?? '';
    return row.name.toLocaleLowerCase().includes(term) || (code !== '' && code.includes(term));
  });
}

export function emptyBranchDirectoryMessage(rawQuery: string): string {
  return rawQuery === '' ? 'No branches in this company.' : 'No branches match this search.';
}
