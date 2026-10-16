import type { OrganizationNode } from '../data/fixtures';

export function nodesForCompany(rows: OrganizationNode[], companyId: string): OrganizationNode[] {
  return rows
    .filter((row) => row.companyId === companyId)
    .slice()
    .sort((left, right) => left.id.localeCompare(right.id));
}

export function filterOrganizationNodes(rows: OrganizationNode[], rawQuery: string): OrganizationNode[] {
  const term = rawQuery.trim().toLocaleLowerCase();
  if (term === '') {
    return rows;
  }
  return rows.filter((row) =>
    [row.id, row.parentId, row.sourceSystem, row.sourceKey].some(
      (value) => value !== null && value.toLocaleLowerCase().includes(term),
    ),
  );
}

export function emptyOrganizationMessage(rawQuery: string): string {
  return rawQuery === '' ? 'No organization nodes in this company.' : 'No organization nodes match this search.';
}

export function childNodes(rows: OrganizationNode[], parentId: string): OrganizationNode[] {
  return rows.filter((row) => row.parentId === parentId);
}

export function rootNodes(rows: OrganizationNode[]): OrganizationNode[] {
  return rows.filter((row) => row.parentId === null);
}

export function detachedNodes(rows: OrganizationNode[]): OrganizationNode[] {
  const ids = new Set(rows.map((row) => row.id));
  return rows.filter((row) => row.parentId !== null && !ids.has(row.parentId));
}
