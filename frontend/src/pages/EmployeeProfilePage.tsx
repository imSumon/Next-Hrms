import { useEffect } from 'react';
import { Link, useLocation, useParams } from 'react-router-dom';
import { PreviewSaveNote } from '../components/PreviewSaveNote';
import { episodesForEmployee, pendingEpisodePolicyNote, rejoinGuidance } from '../lib/episodeForm';
import { readPreviewState, withoutNotice, type PreviewLocationState, type PreviewNotice } from '../lib/previewNavigation';
import { useSession } from '../session';
import type { EmploymentEpisode } from '../data/fixtures';

function noticeText(notice: PreviewNotice): string {
  switch (notice) {
    case 'saved':
      return 'Employee saved in this preview.';
    case 'updated':
      return 'Employee updated in this preview.';
    case 'episode-saved':
      return 'Employment episode saved in this preview.';
    case 'episode-updated':
      return 'Employment episode updated in this preview.';
    case 'branch-saved':
      return 'Branch saved in this preview.';
    case 'branch-updated':
      return 'Branch updated in this preview.';
  }
}

export function EmployeeProfilePage() {
  const { company, employees, episodes } = useSession();
  const { employeeId = '' } = useParams();
  const location = useLocation();
  const previewState = readPreviewState(location.state);
  const employee = company ? employees.find((row) => row.id === employeeId && row.companyId === company.id) : undefined;

  useEffect(() => {
    document.title = employee ? `${employee.displayName} — Universal HRMS` : 'Employee — Universal HRMS';
  }, [employee]);

  if (!company) {
    return null;
  }

  const directoryTarget = { pathname: '/admin/employees', search: previewState.directorySearch ?? '' };

  if (!employee) {
    return (
      <div>
        <p className="text-sm text-stone-500">Admin / Employees</p>
        <h1 className="mt-1 text-2xl font-semibold">Employee</h1>
        <p className="mt-4 text-stone-600">This employee is not in the selected company.</p>
        <p className="mt-4">
          <Link className="font-semibold text-indigo-700" to={directoryTarget}>
            Back to directory
          </Link>
        </p>
      </div>
    );
  }

  return (
    <div>
      <p className="text-sm text-stone-500">
        <Link className="text-indigo-700" to={directoryTarget}>
          Employees
        </Link>
        <span> / {employee.displayName}</span>
      </p>
      <div className="mt-1 flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 className="text-2xl font-semibold text-stone-900">{employee.displayName}</h1>
          <p className="mt-1 text-stone-500">
            {company.name} · {employee.code}
          </p>
        </div>
        <Link
          className="inline-flex min-h-10 items-center rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-semibold text-stone-800"
          to={`/admin/employees/${employee.id}/edit`}
          state={withoutNotice(previewState)}
        >
          Edit
        </Link>
      </div>

      {previewState.notice ? (
        <div className="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-950" role="status">
          <p className="font-semibold">{noticeText(previewState.notice)}</p>
          <div className="mt-1">
            <PreviewSaveNote />
          </div>
        </div>
      ) : null}

      <section className="mt-5 rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
        <h2 className="text-lg font-semibold">Identity</h2>
        <p className="mt-2 text-sm text-stone-600">
          The employee code stays with this identity inside {company.name}. It is not an identity shared with another company.
        </p>
        <dl className="mt-4 grid gap-4 sm:grid-cols-2">
          <div>
            <dt className="text-sm font-semibold text-stone-500">Employee code</dt>
            <dd className="mt-1">{employee.code}</dd>
          </div>
          <div>
            <dt className="text-sm font-semibold text-stone-500">Display name</dt>
            <dd className="mt-1">{employee.displayName}</dd>
          </div>
          <div>
            <dt className="text-sm font-semibold text-stone-500">Company</dt>
            <dd className="mt-1">{company.name}</dd>
          </div>
        </dl>
        <p className="mt-4 text-sm text-stone-500">Recorded and updated times are not included in this preview.</p>
      </section>

      <EpisodeHistory companyId={company.id} employeeId={employee.id} episodes={episodes} previewState={previewState} />

      <p className="mt-5">
        <Link className="font-semibold text-indigo-700" to={directoryTarget}>
          Back to directory
        </Link>
      </p>
    </div>
  );
}

function EpisodeHistory({
  companyId,
  employeeId,
  episodes,
  previewState,
}: {
  companyId: string;
  employeeId: string;
  episodes: EmploymentEpisode[];
  previewState: PreviewLocationState;
}) {
  const history = episodesForEmployee(episodes, companyId, employeeId);

  return (
    <section className="mt-4 rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <h2 className="text-lg font-semibold">Employment episodes</h2>
        <Link
          className="inline-flex min-h-10 items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white"
          to={`/admin/employees/${employeeId}/episodes/new`}
          state={withoutNotice(previewState)}
        >
          Add employment episode
        </Link>
      </div>
      <p className="mt-2 text-sm text-stone-600">
        Employee identity is separate from employment episodes. Joining date, last working date, and episode status belong to an employment episode, not to this identity.
      </p>
      <p className="mt-2 text-sm text-stone-600">{pendingEpisodePolicyNote}</p>
      <p className="mt-2 text-sm text-stone-600">{rejoinGuidance}</p>
      <p className="mt-2 text-sm text-stone-500">Episode rows in this preview are synthetic and last only for this visit.</p>
      {history.length === 0 ? (
        <p className="mt-4 text-stone-600">No employment episodes.</p>
      ) : (
        <>
          <ul className="mt-4 divide-y divide-stone-100 md:hidden">
            {history.map((episode) => (
              <li key={episode.id} className="py-4">
                <EpisodeFacts episode={episode} />
                <Link className="mt-3 inline-flex font-semibold text-indigo-700" to={`/admin/employees/${employeeId}/episodes/${episode.id}/edit`} state={withoutNotice(previewState)}>
                  Edit
                </Link>
              </li>
            ))}
          </ul>
          <div className="mt-4 hidden overflow-x-auto md:block">
            <table className="w-full border-collapse text-left">
              <thead>
                <tr className="border-b border-stone-200 text-sm text-stone-500">
                  <th className="py-3 pr-4 font-semibold" scope="col">
                    Joining date
                  </th>
                  <th className="py-3 pr-4 font-semibold" scope="col">
                    Last working date
                  </th>
                  <th className="py-3 pr-4 font-semibold" scope="col">
                    Status
                  </th>
                  <th className="py-3 font-semibold" scope="col">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody>
                {history.map((episode) => (
                  <tr key={episode.id} className="border-b border-stone-100 last:border-0">
                    <td className="py-3 pr-4">{episode.joiningDate}</td>
                    <td className="py-3 pr-4">{episode.lastWorkingDate ?? '—'}</td>
                    <td className="py-3 pr-4">
                      <StatusText status={episode.status} />
                    </td>
                    <td className="py-3">
                      <Link className="font-semibold text-indigo-700" to={`/admin/employees/${employeeId}/episodes/${episode.id}/edit`} state={withoutNotice(previewState)}>
                        Edit
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </>
      )}
    </section>
  );
}

function EpisodeFacts({ episode }: { episode: EmploymentEpisode }) {
  return (
    <dl className="grid gap-3">
      <div>
        <dt className="text-xs font-semibold uppercase tracking-wide text-stone-500">Joining date</dt>
        <dd className="mt-1">{episode.joiningDate}</dd>
      </div>
      <div>
        <dt className="text-xs font-semibold uppercase tracking-wide text-stone-500">Last working date</dt>
        <dd className="mt-1">{episode.lastWorkingDate ?? '—'}</dd>
      </div>
      <div>
        <dt className="text-xs font-semibold uppercase tracking-wide text-stone-500">Status</dt>
        <dd className="mt-1">
          <StatusText status={episode.status} />
        </dd>
      </div>
    </dl>
  );
}

function StatusText({ status }: { status: EmploymentEpisode['status'] }) {
  return <span className="inline-flex rounded-full bg-stone-100 px-2 py-0.5 text-sm font-semibold text-stone-800">{status}</span>;
}
