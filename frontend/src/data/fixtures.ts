export type Company = {
  id: string;
  name: string;
};

export type Employee = {
  id: string;
  companyId: string;
  code: string;
  displayName: string;
};

export type BranchStatus = 'Active' | 'withdrawn';

export type Branch = {
  id: string;
  companyId: string;
  code: string | null;
  name: string;
  status: BranchStatus;
};

export type EpisodeStatus = 'Open' | 'inactive' | 'separated';

export type EmploymentEpisode = {
  id: string;
  companyId: string;
  employeeId: string;
  joiningDate: string;
  lastWorkingDate: string | null;
  status: EpisodeStatus;
};

export type Membership = {
  companyId: string;
  employeeId: string | null;
};

export type PreviewPerson = {
  id: string;
  name: string;
  memberships: Membership[];
};

/** Foundation organization node. No name, code, status, or node type is stored. */
export type OrganizationNode = {
  id: string;
  companyId: string;
  parentId: string | null;
  sourceSystem: string | null;
  sourceKey: string | null;
};

export const companies: Company[] = [
  { id: 'north', name: 'North Workshop' },
  { id: 'south', name: 'South Workshop' },
  { id: 'quiet', name: 'Quiet Workshop' },
];

function regularEmployees(companyId: string, prefix: string, count: number): Employee[] {
  return Array.from({ length: count }, (_, index) => {
    const number = String(index + 1).padStart(3, '0');
    return {
      id: `${companyId}-${number}`,
      companyId,
      code: `SYN-${number}`,
      displayName: `${prefix} Person ${index + 1}`,
    };
  });
}

export const employees: Employee[] = [
  ...regularEmployees('north', 'North', 16),
  {
    id: 'north-percent',
    companyId: 'north',
    code: 'SYN%100',
    displayName: 'North Percent Code',
  },
  {
    id: 'north-percent-decoy',
    companyId: 'north',
    code: 'SYNX100',
    displayName: 'North Percent Decoy',
  },
  {
    id: 'north-underscore',
    companyId: 'north',
    code: 'SYN_100',
    displayName: 'North Underscore Code',
  },
  {
    id: 'north-underscore-decoy',
    companyId: 'north',
    code: 'SYNA100',
    displayName: 'North Underscore Decoy',
  },
  {
    id: 'north-backslash',
    companyId: 'north',
    code: 'SYN\\100',
    displayName: 'North Backslash Code',
  },
  {
    id: 'north-backslash-decoy',
    companyId: 'north',
    code: 'SYN100',
    displayName: 'North Backslash Decoy',
  },
  {
    id: 'north-rupa',
    companyId: 'north',
    code: 'SYN-R01',
    displayName: 'Rupa Ahmed',
  },
  {
    id: 'north-imran',
    companyId: 'north',
    code: 'SYN-R02',
    displayName: 'Imran Hossain',
  },
  {
    id: 'south-001',
    companyId: 'south',
    code: 'SYN-001',
    displayName: 'South Person 1',
  },
  {
    id: 'south-percent',
    companyId: 'south',
    code: 'SYN%100',
    displayName: 'South Percent Code',
  },
  {
    id: 'south-rupa',
    companyId: 'south',
    code: 'SYN-R01',
    displayName: 'Rupa Ahmed',
  },
  {
    id: 'south-lina',
    companyId: 'south',
    code: 'SYN-R03',
    displayName: 'Lina Akter',
  },
];

export const people: PreviewPerson[] = [
  {
    id: 'rupa',
    name: 'Rupa Ahmed',
    memberships: [
      { companyId: 'north', employeeId: 'north-rupa' },
      { companyId: 'south', employeeId: 'south-rupa' },
    ],
  },
  {
    id: 'imran',
    name: 'Imran Hossain',
    memberships: [{ companyId: 'north', employeeId: 'north-imran' }],
  },
  {
    id: 'lina',
    name: 'Lina Akter',
    memberships: [{ companyId: 'south', employeeId: 'south-lina' }],
  },
  {
    id: 'farid',
    name: 'Farid Islam',
    memberships: [{ companyId: 'quiet', employeeId: null }],
  },
];

export function companyById(id: string): Company | undefined {
  return companies.find((company) => company.id === id);
}

export function personById(id: string): PreviewPerson | undefined {
  return people.find((person) => person.id === id);
}

export function employeeById(id: string): Employee | undefined {
  return employees.find((employee) => employee.id === id);
}

/** Synthetic preview history. Most employees have no episodes. */
export const employmentEpisodes: EmploymentEpisode[] = [
  {
    id: 'north-rupa-episode-1',
    companyId: 'north',
    employeeId: 'north-rupa',
    joiningDate: '2024-01-15',
    lastWorkingDate: '2024-11-30',
    status: 'separated',
  },
  {
    id: 'north-rupa-episode-2',
    companyId: 'north',
    employeeId: 'north-rupa',
    joiningDate: '2025-03-01',
    lastWorkingDate: null,
    status: 'Open',
  },
  {
    id: 'south-rupa-episode-1',
    companyId: 'south',
    employeeId: 'south-rupa',
    joiningDate: '2023-06-01',
    lastWorkingDate: '2023-12-31',
    status: 'inactive',
  },
];

/** Synthetic preview branches. Quiet Workshop has none. A branch is not an employee assignment. */
export const branches: Branch[] = [
  {
    id: 'north-gate',
    companyId: 'north',
    code: 'BR-01',
    name: 'North Gate',
    status: 'Active',
  },
  {
    id: 'north-yard',
    companyId: 'north',
    code: null,
    name: 'North Yard',
    status: 'Active',
  },
  {
    id: 'north-closed',
    companyId: 'north',
    code: 'BR-OLD',
    name: 'North Closed Gate',
    status: 'withdrawn',
  },
  {
    id: 'south-gate',
    companyId: 'south',
    code: 'BR-01',
    name: 'South Gate',
    status: 'Active',
  },
  {
    id: 'south-store',
    companyId: 'south',
    code: 'BR-02',
    name: 'South Store',
    status: 'Active',
  },
];

/**
 * Synthetic organization nodes. A parent belongs to the same company.
 * Quiet Workshop has none. These rows are not branches and not employee assignments.
 */
export const organizationNodes: OrganizationNode[] = [
  { id: 'north-org-1', companyId: 'north', parentId: null, sourceSystem: null, sourceKey: null },
  { id: 'north-org-2', companyId: 'north', parentId: 'north-org-1', sourceSystem: 'preview', sourceKey: 'north-scope-2' },
  { id: 'north-org-3', companyId: 'north', parentId: 'north-org-1', sourceSystem: null, sourceKey: null },
  { id: 'north-org-4', companyId: 'north', parentId: 'north-org-3', sourceSystem: 'preview', sourceKey: 'north-scope-4' },
  { id: 'south-org-1', companyId: 'south', parentId: null, sourceSystem: null, sourceKey: null },
  { id: 'south-org-2', companyId: 'south', parentId: 'south-org-1', sourceSystem: 'preview', sourceKey: 'south-scope-2' },
];
