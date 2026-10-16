import { useEffect, useRef, useState, type ReactNode } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { SessionLogoutButton, SessionLogoutError, SignedOutScreen, useSessionLogout } from '../components/SessionLogout';
import { emptyDirectoryMessage } from '../lib/directory';
import {
  directoryMessages,
  fetchEmployeeDirectory,
  type DirectoryPage,
  type DirectoryResult,
} from '../lib/employeeDirectoryApi';

function requestedPage(value: string | null): number {
  const page = Number(value ?? '1');
  if (!Number.isFinite(page) || page < 1) {
    return 1;
  }
  return Math.floor(page);
}

function directorySearch(query: string, page: number): string {
  const params = new URLSearchParams();
  if (query !== '') {
    params.set('q', query);
  }
  if (page > 1) {
    params.set('page', String(page));
  }
  const value = params.toString();
  return value === '' ? '' : `?${value}`;
}

export function RealSessionDirectoryPage() {
  const [params, setParams] = useSearchParams();
  const rawQuery = params.get('q') ?? '';
  const page = requestedPage(params.get('page'));
  const [draft, setDraft] = useState(rawQuery);
  const [result, setResult] = useState<DirectoryResult>({ status: 'unauthenticated' });
  const [loading, setLoading] = useState(true);
  const [reloadKey, setReloadKey] = useState(0);
  const logout = useSessionLogout();
  const fetchAbort = useRef<AbortController | null>(null);

  useEffect(() => {
    document.title = 'Laravel session directory — Universal HRMS';
  }, []);

  useEffect(() => {
    setDraft(rawQuery);
  }, [rawQuery]);

  useEffect(() => {
    const controller = new AbortController();
    fetchAbort.current = controller;
    setLoading(true);
    setResult({ status: 'unauthenticated' });
    fetchEmployeeDirectory(rawQuery, page, controller.signal).then((next) => {
      if (controller.signal.aborted || next.status === 'aborted') {
        return;
      }
      setResult(next);
      setLoading(false);
    });
    return () => controller.abort();
  }, [page, rawQuery, reloadKey]);

  useEffect(() => {
    if (logout.state === 'signed_out') {
      fetchAbort.current?.abort();
    }
  }, [logout.state]);

  if (logout.state === 'signed_out') {
    return <SignedOutScreen />;
  }

  return (
    <div className="min-h-screen bg-[#fafaf9] text-stone-900">
      <header className="border-b border-stone-200 bg-white">
        <div className="mx-auto flex w-full max-w-6xl flex-wrap items-center gap-3 px-4 py-4">
          <p className="text-lg font-semibold text-[#111827]">Universal HRMS</p>
          <p className="rounded-full bg-stone-900 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-white">Laravel session</p>
          <nav className="ml-auto flex flex-wrap items-center gap-4 text-sm" aria-label="Session directory">
            <Link className="font-semibold text-indigo-700" to="/session/departments">
              Departments
            </Link>
            <Link className="font-semibold text-indigo-700" to="/session/designations">
              Designations
            </Link>
            <Link className="font-semibold text-indigo-700" to="/">
              Back to preview
            </Link>
            <SessionLogoutButton logout={logout} />
          </nav>
        </div>
      </header>
      <main className="mx-auto w-full max-w-6xl px-4 py-8">
        <SessionLogoutError logout={logout} />
        <p className="text-sm text-stone-500">Laravel session / Employees</p>
        <h1 className="mt-1 text-2xl font-semibold text-stone-900">Employees</h1>
        <p className="mt-3 max-w-3xl text-sm leading-6 text-stone-600">
          This directory reads the Laravel session. A demo persona is not a Laravel user, and this page does not choose a company.
        </p>

        <form
          className="mt-5 rounded-xl border border-stone-200 bg-white p-4 shadow-sm"
          onSubmit={(event) => {
            event.preventDefault();
            const next = new URLSearchParams();
            if (draft !== '') {
              next.set('q', draft);
            }
            setParams(next);
          }}
        >
          <label className="block text-sm font-semibold" htmlFor="session-q">
            Search by employee code or display name
          </label>
          <div className="mt-2 flex flex-col gap-2 sm:flex-row">
            <input
              id="session-q"
              name="q"
              value={draft}
              autoComplete="off"
              onChange={(event) => setDraft(event.target.value)}
              className="w-full rounded-md border border-stone-300 px-3 py-2"
            />
            <button type="submit" className="rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white">
              Search
            </button>
          </div>
        </form>

        <div className="mt-4">
          {loading ? <DirectoryStatus title="Reading the Laravel employee directory." role="status" /> : <DirectoryBody result={result} rawQuery={rawQuery} onRetry={() => setReloadKey((value) => value + 1)} />}
        </div>
      </main>
    </div>
  );
}

function DirectoryBody({ result, rawQuery, onRetry }: { result: DirectoryResult; rawQuery: string; onRetry: () => void }) {
  if (result.status === 'unauthenticated') {
    return (
      <DirectoryStatus title={directoryMessages.unauthenticated}>
        <p>Use the Laravel login page. It is separate from the synthetic preview. After Laravel signs you in, return to this directory.</p>
        <p className="mt-3">
          <a className="font-semibold text-indigo-700" href="/login">
            Open Laravel login
          </a>
        </p>
      </DirectoryStatus>
    );
  }

  if (result.status === 'forbidden') {
    return (
      <DirectoryStatus title={directoryMessages.forbidden}>
        {result.message !== directoryMessages.forbidden ? <p>{result.message}</p> : null}
        <p className={result.message !== directoryMessages.forbidden ? 'mt-3' : undefined}>
          Company selection stays on the Laravel company page.
        </p>
        <p className="mt-3">
          <a className="font-semibold text-indigo-700" href="/company">
            Open Laravel company selection
          </a>
        </p>
      </DirectoryStatus>
    );
  }

  if (result.status === 'error' || result.status === 'aborted') {
    return (
      <DirectoryStatus title={directoryMessages.error}>
        <RetryButton onRetry={onRetry} />
      </DirectoryStatus>
    );
  }

  return <DirectoryTable page={result.page} rawQuery={rawQuery} />;
}

function DirectoryTable({ page, rawQuery }: { page: DirectoryPage; rawQuery: string }) {
  return (
    <>
      <section className="mb-4 rounded-xl border border-stone-200 bg-white px-4 py-3 shadow-sm" aria-label="Active company">
        <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">Active company</p>
        <p className="mt-1 text-lg font-semibold text-stone-900">{page.company_name === '' ? '—' : page.company_name}</p>
        <p className="mt-1 text-sm text-stone-600">This name comes from the Laravel session. This page does not change the company.</p>
      </section>
      <div className="overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
        {page.data.length === 0 ? (
          <p className="px-4 py-8 text-stone-600">{emptyDirectoryMessage(rawQuery)}</p>
        ) : (
          <>
            <ul className="divide-y divide-stone-100 md:hidden">
              {page.data.map((employee) => (
                <li key={employee.id} className="px-4 py-4">
                  <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">Employee code</p>
                  <p className="mt-1 font-medium">{employee.employee_code}</p>
                  <p className="mt-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Display name</p>
                  <p className="mt-1">{employee.display_name === '' ? '—' : employee.display_name}</p>
                  <p className="mt-3">
                    <Link className="font-semibold text-indigo-700" to={`/session/employees/${employee.id}`} state={{ directorySearch: directorySearch(rawQuery, page.current_page) }}>
                      View profile
                    </Link>
                  </p>
                </li>
              ))}
            </ul>
            <table className="hidden w-full border-collapse text-left md:table">
              <thead>
                <tr className="border-b border-stone-200 text-sm text-stone-500">
                  <th className="px-4 py-3 font-semibold" scope="col">
                    Employee code
                  </th>
                  <th className="px-4 py-3 font-semibold" scope="col">
                    Display name
                  </th>
                  <th className="px-4 py-3 font-semibold" scope="col">
                    <span className="sr-only">Profile</span>
                  </th>
                </tr>
              </thead>
              <tbody>
                {page.data.map((employee) => (
                  <tr key={employee.id} className="border-b border-stone-100 last:border-0">
                    <td className="px-4 py-3 font-medium">{employee.employee_code}</td>
                    <td className="px-4 py-3">{employee.display_name === '' ? '—' : employee.display_name}</td>
                    <td className="px-4 py-3">
                      <Link className="font-semibold text-indigo-700" to={`/session/employees/${employee.id}`} state={{ directorySearch: directorySearch(rawQuery, page.current_page) }}>
                        View profile
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </>
        )}
      </div>
      {page.last_page > 1 ? (
        <nav className="mt-4 flex flex-wrap items-center justify-between gap-3" aria-label="Pagination">
          <p className="text-sm text-stone-600">
            Showing {page.from ?? 0} to {page.to ?? 0} of {page.total}
          </p>
          <div className="flex flex-wrap items-center gap-2">
            {page.current_page <= 1 ? (
              <span className="rounded-md border border-stone-200 px-3 py-1.5 text-sm text-stone-400" aria-disabled="true">
                Previous
              </span>
            ) : (
              <Link className="rounded-md border border-stone-300 bg-white px-3 py-1.5 text-sm font-semibold" rel="prev" to={{ pathname: '/session/employees', search: directorySearch(rawQuery, page.current_page - 1) }}>
                Previous
              </Link>
            )}
            <span className="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white" aria-current="page">
              {page.current_page}
            </span>
            {page.current_page >= page.last_page ? (
              <span className="rounded-md border border-stone-200 px-3 py-1.5 text-sm text-stone-400" aria-disabled="true">
                Next
              </span>
            ) : (
              <Link className="rounded-md border border-stone-300 bg-white px-3 py-1.5 text-sm font-semibold" rel="next" to={{ pathname: '/session/employees', search: directorySearch(rawQuery, page.current_page + 1) }}>
                Next
              </Link>
            )}
          </div>
        </nav>
      ) : null}
    </>
  );
}

function RetryButton({ onRetry }: { onRetry: () => void }) {
  return (
    <p className="mt-3">
      <button type="button" className="rounded-md border border-stone-300 bg-white px-3 py-1.5 text-sm font-semibold" onClick={onRetry}>
        Try again
      </button>
    </p>
  );
}

function DirectoryStatus({ title, children, role = 'alert' }: { title: string; children?: ReactNode; role?: 'alert' | 'status' }) {
  return (
    <div className="rounded-xl border border-stone-200 bg-white px-4 py-8 shadow-sm" role={role}>
      <p className="font-semibold text-stone-900">{title}</p>
      {children ? <div className="mt-2 max-w-2xl text-sm leading-6 text-stone-600">{children}</div> : null}
    </div>
  );
}
