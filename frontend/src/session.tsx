import { createContext, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import {
  branches as fixtureBranches,
  companyById,
  employees as fixtureEmployees,
  employmentEpisodes as fixtureEpisodes,
  personById,
  type Branch,
  type Company,
  type Employee,
  type EmploymentEpisode,
  type PreviewPerson,
} from './data/fixtures';
import { branchErrorList, validateBranchEdit, validateNewBranch, type BranchErrors } from './lib/branchForm';
import { episodeErrorList, validateEpisode, type EpisodeDraft, type EpisodeErrors } from './lib/episodeForm';
import { identityErrorList, validateDisplayName, validateNewEmployee, type IdentityErrors } from './lib/employeeForm';

const personKey = 'hrms-preview-person';
const companyKey = 'hrms-preview-company';

type EmployeeResult = { ok: true; employee: Employee } | { ok: false; errors: IdentityErrors };
type EpisodeResult = { ok: true; episode: EmploymentEpisode } | { ok: false; errors: EpisodeErrors };
type BranchResult = { ok: true; branch: Branch } | { ok: false; errors: BranchErrors };

type SessionValue = {
  person: PreviewPerson | null;
  company: Company | null;
  employees: Employee[];
  episodes: EmploymentEpisode[];
  branches: Branch[];
  selectPerson: (personId: string) => 'directory' | 'choose-company';
  selectCompany: (companyId: string) => void;
  leavePreview: () => void;
  createEmployee: (code: string, displayName: string) => EmployeeResult;
  updateEmployeeName: (employeeId: string, displayName: string) => EmployeeResult;
  createEpisode: (employeeId: string, draft: EpisodeDraft) => EpisodeResult;
  updateEpisode: (employeeId: string, episodeId: string, draft: EpisodeDraft) => EpisodeResult;
  createBranch: (name: string, code: string) => BranchResult;
  updateBranch: (branchId: string, name: string, status: string) => BranchResult;
};

function cloneEmployees(): Employee[] {
  return fixtureEmployees.map((employee) => ({ ...employee }));
}

function cloneEpisodes(): EmploymentEpisode[] {
  return fixtureEpisodes.map((episode) => ({ ...episode }));
}

function cloneBranches(): Branch[] {
  return fixtureBranches.map((branch) => ({ ...branch }));
}

const SessionContext = createContext<SessionValue | null>(null);

function readStored(key: string): string | null {
  try {
    return sessionStorage.getItem(key);
  } catch {
    return null;
  }
}

export function SessionProvider({ children }: { children: ReactNode }) {
  const [personId, setPersonId] = useState<string | null>(() => readStored(personKey));
  const [companyId, setCompanyId] = useState<string | null>(() => readStored(companyKey));
  const [employees, setEmployees] = useState<Employee[]>(cloneEmployees);
  const [episodes, setEpisodes] = useState<EmploymentEpisode[]>(cloneEpisodes);
  const [branches, setBranches] = useState<Branch[]>(cloneBranches);

  const person = personId ? personById(personId) ?? null : null;
  const membershipIds = person?.memberships.map((membership) => membership.companyId) ?? [];
  const company = person && companyId && membershipIds.includes(companyId) ? companyById(companyId) ?? null : null;

  useEffect(() => {
    try {
      if (person) {
        sessionStorage.setItem(personKey, person.id);
      } else {
        sessionStorage.removeItem(personKey);
      }
      if (company) {
        sessionStorage.setItem(companyKey, company.id);
      } else {
        sessionStorage.removeItem(companyKey);
      }
    } catch {
      // Preview state is optional. The screen still works for this visit.
    }
  }, [person, company]);

  const value = useMemo<SessionValue>(
    () => ({
      person,
      company,
      employees,
      episodes,
      branches,
      selectPerson: (nextPersonId: string) => {
        const next = personById(nextPersonId);
        if (!next) {
          return 'choose-company';
        }
        setEmployees(cloneEmployees());
        setEpisodes(cloneEpisodes());
        setBranches(cloneBranches());
        setPersonId(next.id);
        if (next.memberships.length === 1) {
          setCompanyId(next.memberships[0].companyId);
          return 'directory';
        }
        setCompanyId(null);
        return 'choose-company';
      },
      selectCompany: (nextCompanyId: string) => {
        setCompanyId(nextCompanyId);
      },
      leavePreview: () => {
        setEmployees(cloneEmployees());
        setEpisodes(cloneEpisodes());
        setBranches(cloneBranches());
        setPersonId(null);
        setCompanyId(null);
      },
      createEmployee: (codeRaw: string, nameRaw: string) => {
        if (!company) {
          return { ok: false, errors: { employee_code: 'Employee code is required.' } };
        }
        const result = validateNewEmployee(codeRaw, nameRaw, employees, company.id);
        if (identityErrorList(result.errors).length > 0) {
          return { ok: false, errors: result.errors };
        }
        const employee: Employee = {
          id: `preview-${company.id}-${crypto.randomUUID()}`,
          companyId: company.id,
          code: result.code,
          displayName: result.displayName,
        };
        setEmployees((current) => [...current, employee]);
        return { ok: true, employee };
      },
      updateEmployeeName: (employeeId: string, nameRaw: string) => {
        if (!company) {
          return { ok: false, errors: { display_name: 'Display name is required.' } };
        }
        const existing = employees.find((row) => row.id === employeeId && row.companyId === company.id);
        if (!existing) {
          return { ok: false, errors: { display_name: 'Display name is required.' } };
        }
        const result = validateDisplayName(nameRaw);
        if (identityErrorList(result.errors).length > 0) {
          return { ok: false, errors: result.errors };
        }
        const employee: Employee = { ...existing, displayName: result.displayName };
        setEmployees((current) => current.map((row) => (row.id === employee.id ? employee : row)));
        return { ok: true, employee };
      },
      createEpisode: (employeeId: string, draft: EpisodeDraft) => {
        if (!company) {
          return { ok: false, errors: { joining_date: 'Joining date is required.' } };
        }
        const employee = employees.find((row) => row.id === employeeId && row.companyId === company.id);
        if (!employee) {
          return { ok: false, errors: { joining_date: 'Joining date is required.' } };
        }
        const result = validateEpisode(draft, episodes, company.id, employee.id);
        if (episodeErrorList(result.errors).length > 0) {
          return { ok: false, errors: result.errors };
        }
        const episode: EmploymentEpisode = {
          id: `preview-episode-${crypto.randomUUID()}`,
          companyId: company.id,
          employeeId: employee.id,
          joiningDate: result.joiningDate,
          lastWorkingDate: result.lastWorkingDate,
          status: result.status,
        };
        setEpisodes((current) => [...current, episode]);
        return { ok: true, episode };
      },
      updateEpisode: (employeeId: string, episodeId: string, draft: EpisodeDraft) => {
        if (!company) {
          return { ok: false, errors: { joining_date: 'Joining date is required.' } };
        }
        const existing = episodes.find(
          (row) => row.id === episodeId && row.employeeId === employeeId && row.companyId === company.id,
        );
        if (!existing) {
          return { ok: false, errors: { joining_date: 'Joining date is required.' } };
        }
        const result = validateEpisode(draft, episodes, company.id, employeeId, existing);
        if (episodeErrorList(result.errors).length > 0) {
          return { ok: false, errors: result.errors };
        }
        const episode: EmploymentEpisode = {
          ...existing,
          joiningDate: result.joiningDate,
          lastWorkingDate: result.lastWorkingDate,
          status: result.status,
        };
        setEpisodes((current) => current.map((row) => (row.id === episode.id ? episode : row)));
        return { ok: true, episode };
      },
      createBranch: (nameRaw: string, codeRaw: string) => {
        if (!company) {
          return { ok: false, errors: { name: 'Branch name is required.' } };
        }
        const result = validateNewBranch(nameRaw, codeRaw, branches, company.id);
        if (branchErrorList(result.errors).length > 0) {
          return { ok: false, errors: result.errors };
        }
        const branch: Branch = {
          id: `preview-branch-${crypto.randomUUID()}`,
          companyId: company.id,
          code: result.code,
          name: result.name,
          status: 'Active',
        };
        setBranches((current) => [...current, branch]);
        return { ok: true, branch };
      },
      updateBranch: (branchId: string, nameRaw: string, statusRaw: string) => {
        if (!company) {
          return { ok: false, errors: { name: 'Branch name is required.' } };
        }
        const existing = branches.find((row) => row.id === branchId && row.companyId === company.id);
        if (!existing) {
          return { ok: false, errors: { name: 'Branch name is required.' } };
        }
        const result = validateBranchEdit(nameRaw, statusRaw);
        if (branchErrorList(result.errors).length > 0) {
          return { ok: false, errors: result.errors };
        }
        const branch: Branch = {
          ...existing,
          name: result.name,
          status: result.status,
        };
        setBranches((current) => current.map((row) => (row.id === branch.id ? branch : row)));
        return { ok: true, branch };
      },
    }),
    [person, company, employees, episodes, branches],
  );

  return <SessionContext.Provider value={value}>{children}</SessionContext.Provider>;
}

export function useSession(): SessionValue {
  const value = useContext(SessionContext);
  if (!value) {
    throw new Error('Preview session is unavailable.');
  }
  return value;
}
