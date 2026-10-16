import { useEffect, useRef, useState, type ReactNode } from 'react';
import { Link, useLocation, useParams } from 'react-router-dom';
import { SessionLogoutButton, SessionLogoutError, SignedOutScreen, useSessionLogout } from '../components/SessionLogout';
import {
  fetchEmployeeProfile,
  profileMessages,
  updateEmployeeDisplayName,
  type DisplayNameChange,
  type EmployeeProfile,
  type ProfileResult,
  type ProfileUpdateResult,
} from '../lib/employeeProfileApi';

type ProfileLocationState = {
  directorySearch?: string;
};

function directoryTarget(state: unknown): { pathname: string; search: string } {
  const search = state && typeof state === 'object' && 'directorySearch' in state && typeof state.directorySearch === 'string'
    ? state.directorySearch
    : '';
  return { pathname: '/session/employees', search };
}

export function RealSessionProfilePage() {
  const { employeeId = '' } = useParams();
  const location = useLocation();
  const backTarget = directoryTarget(location.state as ProfileLocationState | null);
  const [result, setResult] = useState<ProfileResult>({ status: 'unauthenticated' });
  const [loading, setLoading] = useState(true);
  const [reloadKey, setReloadKey] = useState(0);
  const logout = useSessionLogout();
  const fetchAbort = useRef<AbortController | null>(null);

  useEffect(() => {
    document.title = 'Laravel session profile — Universal HRMS';
  }, []);

  useEffect(() => {
    const controller = new AbortController();
    fetchAbort.current = controller;
    setLoading(true);
    setResult({ status: 'unauthenticated' });
    fetchEmployeeProfile(employeeId, controller.signal).then((next) => {
      if (controller.signal.aborted || next.status === 'aborted') {
        return;
      }
      setResult(next);
      setLoading(false);
    });
    return () => controller.abort();
  }, [employeeId, reloadKey]);

  useEffect(() => {
    if (logout.state === 'signed_out') {
      fetchAbort.current?.abort();
    }
  }, [logout.state]);

  useEffect(() => {
    if (result.status === 'ok') {
      const name = result.profile.display_name === '' ? result.profile.employee_code : result.profile.display_name;
      document.title = `${name} — Universal HRMS`;
    }
  }, [result]);

  if (logout.state === 'signed_out') {
    return <SignedOutScreen />;
  }

  return (
    <div className="min-h-screen bg-[#fafaf9] text-stone-900">
      <header className="border-b border-stone-200 bg-white">
        <div className="mx-auto flex w-full max-w-6xl flex-wrap items-center gap-3 px-4 py-4">
          <p className="text-lg font-semibold text-[#111827]">Universal HRMS</p>
          <p className="rounded-full bg-stone-900 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-white">Laravel session</p>
          <nav className="ml-auto flex flex-wrap items-center gap-4 text-sm" aria-label="Session profile">
            <Link className="font-semibold text-indigo-700" to={backTarget}>
              Back to directory
            </Link>
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
        <p className="text-sm text-stone-500">Laravel session / Employees / Profile</p>
        <h1 className="mt-1 text-2xl font-semibold text-stone-900">Employee profile</h1>
        <p className="mt-3 max-w-3xl text-sm leading-6 text-stone-600">
          This profile reads the Laravel session for the active company. It does not use the synthetic preview.
        </p>
        <div className="mt-4">
          {loading ? <ProfileStatus title="Reading the Laravel employee profile." role="status" /> : <ProfileBody result={result} onUpdated={(profile) => setResult({ status: 'ok', profile })} onRetry={() => setReloadKey((value) => value + 1)} />}
        </div>
      </main>
    </div>
  );
}

function ProfileBody({ result, onUpdated, onRetry }: { result: ProfileResult; onUpdated: (profile: EmployeeProfile) => void; onRetry: () => void }) {
  if (result.status === 'unauthenticated') {
    return (
      <ProfileStatus title={profileMessages.unauthenticated}>
        <p>Use the Laravel login page. It is separate from the synthetic preview. After Laravel signs you in, return to this profile.</p>
        <p className="mt-3">
          <a className="font-semibold text-indigo-700" href="/login">
            Open Laravel login
          </a>
        </p>
      </ProfileStatus>
    );
  }

  if (result.status === 'forbidden') {
    return (
      <ProfileStatus title={profileMessages.forbidden}>
        {result.message !== profileMessages.forbidden ? <p>{result.message}</p> : null}
        <p className={result.message !== profileMessages.forbidden ? 'mt-3' : undefined}>
          Company selection stays on the Laravel company page.
        </p>
        <p className="mt-3">
          <a className="font-semibold text-indigo-700" href="/company">
            Open Laravel company selection
          </a>
        </p>
      </ProfileStatus>
    );
  }

  if (result.status === 'not_found') {
    return <ProfileStatus title={profileMessages.notFound} />;
  }

  if (result.status === 'error' || result.status === 'aborted') {
    return (
      <ProfileStatus title={profileMessages.error}>
        <p className="mt-3">
          <button type="button" className="rounded-md border border-stone-300 bg-white px-3 py-1.5 text-sm font-semibold" onClick={onRetry}>
            Try again
          </button>
        </p>
      </ProfileStatus>
    );
  }

  return <ProfileRecord profile={result.profile} onUpdated={onUpdated} />;
}

function ProfileRecord({ profile, onUpdated }: { profile: EmployeeProfile; onUpdated: (profile: EmployeeProfile) => void }) {
  const heading = profile.display_name === '' ? profile.employee_code : profile.display_name;

  return (
    <div className="space-y-4">
      <section className="rounded-xl border border-stone-200 bg-white p-4 shadow-sm">
        <h2 className="text-xl font-semibold text-stone-900">{heading}</h2>
        <p className="mt-1 text-stone-500">
          {profile.company_name} · {profile.employee_code}
        </p>
        <dl className="mt-4 grid gap-3 sm:grid-cols-2">
          <div>
            <dt className="text-xs font-semibold uppercase tracking-wide text-stone-500">Employee code</dt>
            <dd className="mt-1 font-medium">{profile.employee_code}</dd>
          </div>
          <div>
            <dt className="text-xs font-semibold uppercase tracking-wide text-stone-500">Display name</dt>
            <dd className="mt-1">{profile.display_name === '' ? '—' : profile.display_name}</dd>
          </div>
        </dl>
        {profile.can_manage_employees ? <DisplayNameEditor profile={profile} onUpdated={onUpdated} /> : null}
      </section>
      <NameHistory changes={profile.display_name_changes} />
      <section className="overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
        <h2 className="border-b border-stone-200 px-4 py-3 text-lg font-semibold">Employment episodes</h2>
        <p className="border-b border-stone-100 px-4 py-3 text-sm text-stone-600">{profileMessages.episodesReadOnly}</p>
        {profile.episodes.length === 0 ? (
          <p className="px-4 py-8 text-stone-600">{profileMessages.emptyEpisodes}</p>
        ) : (
          <>
            <ul className="divide-y divide-stone-100 md:hidden">
              {profile.episodes.map((episode) => (
                <li key={episode.id} className="px-4 py-4">
                  <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">Joining date</p>
                  <p className="mt-1">{episode.joining_date}</p>
                  <p className="mt-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Last working date</p>
                  <p className="mt-1">{episode.last_working_date ?? '—'}</p>
                  <p className="mt-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Status</p>
                  <p className="mt-1">{episode.episode_status}</p>
                </li>
              ))}
            </ul>
            <table className="hidden w-full border-collapse text-left md:table">
              <thead>
                <tr className="border-b border-stone-200 text-sm text-stone-500">
                  <th className="px-4 py-3 font-semibold" scope="col">Joining date</th>
                  <th className="px-4 py-3 font-semibold" scope="col">Last working date</th>
                  <th className="px-4 py-3 font-semibold" scope="col">Status</th>
                </tr>
              </thead>
              <tbody>
                {profile.episodes.map((episode) => (
                  <tr key={episode.id} className="border-b border-stone-100 last:border-0">
                    <td className="px-4 py-3">{episode.joining_date}</td>
                    <td className="px-4 py-3">{episode.last_working_date ?? '—'}</td>
                    <td className="px-4 py-3">{episode.episode_status}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </>
        )}
      </section>
    </div>
  );
}

function NameHistory({ changes }: { changes: DisplayNameChange[] }) {
  return (
    <section className="overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
      <h2 className="border-b border-stone-200 px-4 py-3 text-lg font-semibold">Display-name change history</h2>
      {changes.length === 0 ? (
        <p className="px-4 py-8 text-stone-600">{profileMessages.emptyNameHistory}</p>
      ) : (
        <>
          <ul className="divide-y divide-stone-100 md:hidden">
            {changes.map((change, index) => (
              <li key={`${change.acted_at ?? 'undated'}-${index}`} className="px-4 py-4">
                <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">Previous display name</p>
                <p className="mt-1">{change.previous_display_name || '—'}</p>
                <p className="mt-3 text-xs font-semibold uppercase tracking-wide text-stone-500">New display name</p>
                <p className="mt-1">{change.new_display_name || '—'}</p>
                <p className="mt-3 text-xs font-semibold uppercase tracking-wide text-stone-500">Actor</p>
                <p className="mt-1">{change.actor_name || '—'}</p>
                <p className="mt-3 text-xs font-semibold uppercase tracking-wide text-stone-500">When</p>
                <p className="mt-1">{change.acted_at || '—'}</p>
              </li>
            ))}
          </ul>
          <table className="hidden w-full border-collapse text-left md:table">
            <thead>
              <tr className="border-b border-stone-200 text-sm text-stone-500">
                <th className="px-4 py-3 font-semibold" scope="col">Previous display name</th>
                <th className="px-4 py-3 font-semibold" scope="col">New display name</th>
                <th className="px-4 py-3 font-semibold" scope="col">Actor</th>
                <th className="px-4 py-3 font-semibold" scope="col">When</th>
              </tr>
            </thead>
            <tbody>
              {changes.map((change, index) => (
                <tr key={`${change.acted_at ?? 'undated'}-${index}`} className="border-b border-stone-100 last:border-0">
                  <td className="px-4 py-3">{change.previous_display_name || '—'}</td>
                  <td className="px-4 py-3">{change.new_display_name || '—'}</td>
                  <td className="px-4 py-3">{change.actor_name || '—'}</td>
                  <td className="px-4 py-3">{change.acted_at || '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </>
      )}
    </section>
  );
}

function DisplayNameEditor({ profile, onUpdated }: { profile: EmployeeProfile; onUpdated: (profile: EmployeeProfile) => void }) {
  const [displayName, setDisplayName] = useState(profile.display_name);
  const [saving, setSaving] = useState(false);
  const [update, setUpdate] = useState<ProfileUpdateResult | null>(null);

  useEffect(() => {
    setDisplayName(profile.display_name);
  }, [profile.id, profile.display_name]);

  const fieldErrors = update?.status === 'invalid' ? Object.values(update.errors).flat() : [];

  return (
    <form
      className="mt-4 max-w-xl space-y-3 border-t border-stone-100 pt-4"
      noValidate
      onSubmit={(event) => {
        event.preventDefault();
        const controller = new AbortController();
        setSaving(true);
        setUpdate(null);
        updateEmployeeDisplayName(profile.id, displayName, controller.signal).then((next) => {
          if (controller.signal.aborted || next.status === 'aborted') {
            return;
          }
          setSaving(false);
          setUpdate(next);
          if (next.status === 'ok') {
            onUpdated(next.profile);
          }
        });
      }}
    >
      <div>
        <label className="text-xs font-semibold uppercase tracking-wide text-stone-500" htmlFor="session-display-name">
          Edit display name
        </label>
        <input
          id="session-display-name"
          className="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2"
          value={displayName}
          onChange={(event) => setDisplayName(event.target.value)}
          autoComplete="off"
          aria-invalid={fieldErrors.length > 0}
          aria-describedby={fieldErrors.length > 0 ? 'session-display-name-error' : undefined}
        />
        <p className="mt-2 text-sm text-stone-600">The employee code stays on this record.</p>
      </div>
      {saving ? <p className="text-sm text-stone-600">{profileMessages.saving}</p> : null}
      {update?.status === 'ok' ? <p className="text-sm font-semibold text-stone-900" role="status">{profileMessages.saved}</p> : null}
      {fieldErrors.length > 0 ? (
        <ul id="session-display-name-error" className="list-disc pl-5 text-sm text-stone-700" role="alert">
          {fieldErrors.map((message) => <li key={message}>{message}</li>)}
        </ul>
      ) : null}
      {update?.status === 'unauthenticated' ? <p className="text-sm text-stone-700" role="alert">{profileMessages.unauthenticated}</p> : null}
      {update?.status === 'forbidden' ? <p className="text-sm text-stone-700" role="alert">{update.message}</p> : null}
      {update?.status === 'not_found' ? <p className="text-sm text-stone-700" role="alert">{profileMessages.notFound}</p> : null}
      {update?.status === 'error' ? <p className="text-sm text-stone-700" role="alert">{update.message}</p> : null}
      <div className="flex flex-wrap gap-2">
        <button className="rounded-lg bg-indigo-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60" type="submit" disabled={saving}>
          Save display name
        </button>
        {displayName !== profile.display_name ? (
          <button
            className="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-semibold disabled:opacity-60"
            type="button"
            disabled={saving}
            onClick={() => {
              setDisplayName(profile.display_name);
              setUpdate(null);
            }}
          >
            Cancel
          </button>
        ) : null}
      </div>
    </form>
  );
}

function ProfileStatus({ title, children, role = 'alert' }: { title: string; children?: ReactNode; role?: 'alert' | 'status' }) {
  return (
    <div className="rounded-xl border border-stone-200 bg-white px-4 py-8 shadow-sm" role={role}>
      <p className="font-semibold text-stone-900">{title}</p>
      {children ? <div className="mt-2 max-w-2xl text-sm leading-6 text-stone-600">{children}</div> : null}
    </div>
  );
}
