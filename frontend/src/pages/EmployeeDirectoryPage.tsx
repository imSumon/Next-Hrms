import { useEffect, useState } from 'react';
import { Link, useLocation, useSearchParams } from 'react-router-dom';
import { PAGE_SIZE, emptyDirectoryMessage, filterEmployees, pageCount, pageSlice } from '../lib/directory';
import type { PreviewLocationState } from '../lib/previewNavigation';
import { useSession } from '../session';

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

export function EmployeeDirectoryPage() {
  const { company, employees } = useSession();
  const location = useLocation();
  const [params, setParams] = useSearchParams();
  const rawQuery = params.get('q') ?? '';
  const requestedPage = Number(params.get('page') ?? '1');
  const [draft, setDraft] = useState(rawQuery);
  const directoryState: PreviewLocationState = { directorySearch: location.search };

  useEffect(() => {
    document.title = 'Employees — Universal HRMS';
  }, []);

  useEffect(() => {
    setDraft(rawQuery);
  }, [rawQuery]);

  if (!company) {
    return null;
  }

  const matched = filterEmployees(employees, company.id, rawQuery);
  const pages = matched.length === 0 ? 0 : pageCount(matched.length);
  const page = pages === 0 ? 1 : Math.min(Math.max(1, Number.isFinite(requestedPage) ? requestedPage : 1), pages);
  const visible = pageSlice(matched, page);
  const from = matched.length === 0 ? 0 : (page - 1) * PAGE_SIZE + 1;
  const to = matched.length === 0 ? 0 : from + visible.length - 1;

  return (
    <div>
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <p className="text-sm text-stone-500">Admin / Employees</p>
          <h1 className="mt-1 text-2xl font-semibold text-stone-900">Employees</h1>
          <p className="mt-1 text-stone-500">{company.name}</p>
        </div>
        <Link
          to="/admin/employees/new"
          state={directoryState}
          className="inline-flex min-h-10 items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white"
        >
          Add employee
        </Link>
      </div>

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
        <label className="block text-sm font-semibold" htmlFor="q">
          Search by employee code or display name
        </label>
        <div className="mt-2 flex flex-col gap-2 sm:flex-row">
          <input
            id="q"
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

      <div className="mt-4 overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
        {visible.length === 0 ? (
          <p className="px-4 py-8 text-stone-600">{emptyDirectoryMessage(rawQuery)}</p>
        ) : (
          <>
            <ul className="divide-y divide-stone-100 md:hidden">
              {visible.map((employee) => (
                <li key={employee.id} className="px-4 py-4">
                  <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">Employee code</p>
                  <p className="mt-1 font-medium">{employee.code}</p>
                  <p className="mt-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Display name</p>
                  <p className="mt-1">{employee.displayName}</p>
                  <div className="mt-3 flex gap-4">
                    <Link className="font-semibold text-indigo-700" to={`/admin/employees/${employee.id}`} state={directoryState}>
                      View
                    </Link>
                    <Link className="font-semibold text-indigo-700" to={`/admin/employees/${employee.id}/edit`} state={directoryState}>
                      Edit
                    </Link>
                  </div>
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
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody>
                {visible.map((employee) => (
                  <tr key={employee.id} className="border-b border-stone-100 last:border-0">
                    <td className="px-4 py-3 font-medium">{employee.code}</td>
                    <td className="px-4 py-3">{employee.displayName}</td>
                    <td className="px-4 py-3">
                      <div className="flex gap-4">
                        <Link className="font-semibold text-indigo-700" to={`/admin/employees/${employee.id}`} state={directoryState}>
                          View
                        </Link>
                        <Link className="font-semibold text-indigo-700" to={`/admin/employees/${employee.id}/edit`} state={directoryState}>
                          Edit
                        </Link>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </>
        )}
      </div>

      {pages > 1 ? (
        <nav className="mt-4 flex flex-wrap items-center justify-between gap-3" aria-label="Pagination">
          <p className="text-sm text-stone-600">
            Showing {from} to {to} of {matched.length}
          </p>
          <div className="flex flex-wrap items-center gap-2">
            {page <= 1 ? (
              <span className="rounded-md border border-stone-200 px-3 py-1.5 text-sm text-stone-400" aria-disabled="true">
                Previous
              </span>
            ) : (
              <Link className="rounded-md border border-stone-300 bg-white px-3 py-1.5 text-sm font-semibold" rel="prev" to={{ pathname: '/admin/employees', search: directorySearch(rawQuery, page - 1) }}>
                Previous
              </Link>
            )}
            {Array.from({ length: pages }, (_, index) => index + 1).map((number) =>
              number === page ? (
                <span key={number} className="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white" aria-current="page">
                  {number}
                </span>
              ) : (
                <Link
                  key={number}
                  className="rounded-md border border-stone-300 bg-white px-3 py-1.5 text-sm"
                  aria-label={`Go to page ${number}`}
                  to={{ pathname: '/admin/employees', search: directorySearch(rawQuery, number) }}
                >
                  {number}
                </Link>
              ),
            )}
            {page >= pages ? (
              <span className="rounded-md border border-stone-200 px-3 py-1.5 text-sm text-stone-400" aria-disabled="true">
                Next
              </span>
            ) : (
              <Link className="rounded-md border border-stone-300 bg-white px-3 py-1.5 text-sm font-semibold" rel="next" to={{ pathname: '/admin/employees', search: directorySearch(rawQuery, page + 1) }}>
                Next
              </Link>
            )}
          </div>
        </nav>
      ) : null}
    </div>
  );
}
