import { useEffect, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { RealSessionLinks } from '../components/RealSessionLinks';
import { SessionLogoutButton, SessionLogoutError, SignedOutScreen, useSessionLogout } from '../components/SessionLogout';
import {
  attendanceMessages,
  endPunchIdentity,
  fetchPunchIdentities,
  fetchPunchIdentity,
  fetchPunchIdentityConflicts,
  registerPunchIdentity,
  type AttendanceReadResult,
  type PunchIdentityConflicts,
  type PunchIdentityDetail,
  type PunchIdentityRow,
} from '../lib/attendanceWorkflowApi';
import { fetchEmployeeDirectory, type DirectoryEmployee } from '../lib/employeeDirectoryApi';

export function RealSessionPunchIdentitiesPage() {
  const logout = useSessionLogout();

  useEffect(() => {
    document.title = 'Card identities — Universal HRMS';
  }, []);

  if (logout.state === 'signed_out') {
    return <SignedOutScreen />;
  }

  return (
    <div className="min-h-screen bg-[#fafaf9] text-stone-900">
      <header className="border-b border-stone-200 bg-white">
        <div className="mx-auto flex w-full max-w-6xl flex-wrap items-center gap-3 px-4 py-4">
          <p className="text-lg font-semibold text-[#111827]">Universal HRMS</p>
          <p className="rounded-full bg-stone-900 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-white">Laravel session</p>
          <RealSessionLinks label="Card identity navigation">
            <a className="font-semibold text-indigo-700" href="/company">Company</a>
            <SessionLogoutButton logout={logout} />
          </RealSessionLinks>
        </div>
      </header>
      <main className="mx-auto w-full max-w-6xl space-y-8 px-4 py-8">
        <SessionLogoutError logout={logout} />
        <p className="text-sm"><Link className="font-semibold text-indigo-700" to="/session/attendance/dashboard">Back to the attendance dashboard</Link></p>
        <IdentityWorkspace />
      </main>
    </div>
  );
}

function IdentityWorkspace() {
  const [query, setQuery] = useState('');
  const [search, setSearch] = useState('');
  const [list, setList] = useState<AttendanceReadResult<{ company_name: string; data: PunchIdentityRow[] }> | null>(null);
  const [conflicts, setConflicts] = useState<PunchIdentityConflicts | null>(null);
  const [detail, setDetail] = useState<PunchIdentityDetail | null>(null);
  const [notice, setNotice] = useState('');
  const [reloadKey, setReloadKey] = useState(0);

  useEffect(() => {
    const controller = new AbortController();
    Promise.all([fetchPunchIdentities(search, controller.signal), fetchPunchIdentityConflicts(controller.signal)]).then(([rows, overlap]) => {
      if (controller.signal.aborted || rows.status === 'aborted') {
        return;
      }
      setList(rows);
      setConflicts(overlap.status === 'ok' ? overlap.body : null);
    });
    return () => controller.abort();
  }, [search, reloadKey]);

  async function open(identityId: string) {
    const result = await fetchPunchIdentity(identityId);
    if (result.status === 'ok') {
      setDetail(result.body);
    }
  }

  if (list === null) {
    return <p className="text-stone-600">Loading card identities.</p>;
  }
  if (list.status === 'aborted') {
    return <p className="text-stone-600">Loading card identities.</p>;
  }
  if (list.status !== 'ok') {
    return (
      <section className="rounded-xl border border-stone-200 bg-white px-4 py-6 shadow-sm" role="alert">
        <h1 className="text-xl font-semibold">{list.status === 'unauthenticated' ? attendanceMessages.unauthenticated : list.message}</h1>
        {list.status === 'unauthenticated' ? <p className="mt-3"><a className="font-semibold text-indigo-700" href="/login">Open Laravel login</a></p> : null}
        {list.status === 'forbidden' && list.message === 'An active company must be selected.' ? <p className="mt-3"><a className="font-semibold text-indigo-700" href="/company">Choose a company</a></p> : null}
      </section>
    );
  }

  return (
    <div className="space-y-8">
      <section className="rounded-xl border border-stone-200 bg-white px-4 py-3 shadow-sm" aria-label="Active company">
        <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">Active company</p>
        <p className="mt-1 text-lg font-semibold text-stone-900">{list.body.company_name}</p>
        <p className="mt-1 text-sm text-stone-600">
          A card number maps to one existing employee of this company for a stated period. A card cannot be active for two employees at once unless a documented overlap covers the shared period. A mapping is ended, never moved to another employee. Registering a card records no punch and creates no employee.
        </p>
      </section>
      {notice !== '' ? <p className="rounded-xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-700" role="status">{notice}</p> : null}
      <RegisterForm
        onRegistered={(row) => {
          setNotice(row.warning ?? `Card ${row.identity_value} was registered for ${row.employee_code ?? row.employee_id}.`);
          setReloadKey((value) => value + 1);
          void open(row.id);
        }}
        onNotice={setNotice}
      />
      <section aria-label="Registered card identities">
        <h2 className="text-lg font-semibold">Registered cards</h2>
        <form
          className="mt-2 flex flex-wrap items-end gap-3"
          onSubmit={(event) => {
            event.preventDefault();
            setSearch(query.trim());
          }}
        >
          <label className="text-sm">
            <span className="font-semibold text-stone-600">Search card, code or name</span>
            <input className="mt-1 rounded-lg border border-stone-300 px-3 py-2" value={query} onChange={(event) => setQuery(event.target.value)} />
          </label>
          <button className="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold" type="submit">Search</button>
        </form>
        <div className="mt-3 overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
          {list.body.data.length === 0 ? <p className="px-4 py-8 text-stone-600">No card is registered for this company{search !== '' ? ' that matches this search' : ''}.</p> : (
            <table className="w-full border-collapse text-left text-sm">
              <thead>
                <tr className="border-b border-stone-200 text-stone-500">
                  <th className="px-4 py-3 font-semibold" scope="col">Card</th>
                  <th className="px-4 py-3 font-semibold" scope="col">Employee</th>
                  <th className="px-4 py-3 font-semibold" scope="col">From</th>
                  <th className="px-4 py-3 font-semibold" scope="col">To</th>
                  <th className="px-4 py-3 font-semibold" scope="col">Status</th>
                  <th className="px-4 py-3 font-semibold" scope="col"><span className="sr-only">Open</span></th>
                </tr>
              </thead>
              <tbody>
                {list.body.data.map((row) => (
                  <tr key={row.id} className="border-b border-stone-100">
                    <td className="px-4 py-3">{row.identity_value}{row.registered_in_another_company ? <span className="block text-xs text-amber-700">Also registered in another company</span> : null}</td>
                    <td className="px-4 py-3">{row.employee_code ?? '—'} {row.display_name ?? ''}</td>
                    <td className="px-4 py-3">{row.effective_from ?? '—'}</td>
                    <td className="px-4 py-3">{row.effective_to ?? 'open'}</td>
                    <td className="px-4 py-3">{row.status}</td>
                    <td className="px-4 py-3"><button className="font-semibold text-indigo-700" type="button" onClick={() => void open(row.id)}>History</button></td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </section>
      {conflicts ? <ConflictPanel conflicts={conflicts} rows={list.body.data} onOpen={(identityId) => void open(identityId)} /> : null}
      {detail ? (
        <DetailPanel
          detail={detail}
          onEnded={(message) => {
            setNotice(message);
            setReloadKey((value) => value + 1);
            void open(detail.id);
          }}
          onNotice={setNotice}
        />
      ) : null}
    </div>
  );
}

function RegisterForm({ onRegistered, onNotice }: { onRegistered: (row: PunchIdentityRow) => void; onNotice: (message: string) => void }) {
  const [employeeQuery, setEmployeeQuery] = useState('');
  const [employees, setEmployees] = useState<DirectoryEmployee[]>([]);
  const [employeeId, setEmployeeId] = useState('');
  const [card, setCard] = useState('');
  const [from, setFrom] = useState('');
  const [to, setTo] = useState('');
  const [reference, setReference] = useState('');
  const [pending, setPending] = useState(false);

  async function findEmployees() {
    const result = await fetchEmployeeDirectory(employeeQuery.trim(), 1);
    if (result.status === 'ok') {
      setEmployees(result.page.data);
      setEmployeeId(result.page.data[0]?.id ?? '');
      if (result.page.data.length === 0) {
        onNotice('No employee of this company matches that search.');
      }
      return;
    }
    onNotice(result.status === 'unauthenticated' ? attendanceMessages.unauthenticated : result.status === 'aborted' ? '' : result.message);
  }

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setPending(true);
    const result = await registerPunchIdentity(employeeId, card, from.trim(), to.trim(), reference);
    setPending(false);
    if (result.status === 'ok') {
      setCard('');
      setTo('');
      setReference('');
      onRegistered(result.body);
      return;
    }
    onNotice(result.status === 'unauthenticated' ? attendanceMessages.unauthenticated : result.message);
  }

  return (
    <form className="rounded-xl border border-stone-200 bg-white px-4 py-4 shadow-sm" onSubmit={onSubmit} aria-label="Register a card">
      <h2 className="text-lg font-semibold">Register a card</h2>
      <div className="mt-3 flex flex-wrap items-end gap-3">
        <label className="text-sm">
          <span className="font-semibold text-stone-600">Find employee</span>
          <input className="mt-1 rounded-lg border border-stone-300 px-3 py-2" value={employeeQuery} onChange={(event) => setEmployeeQuery(event.target.value)} />
        </label>
        <button className="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold" type="button" onClick={() => void findEmployees()}>Search employees</button>
      </div>
      <div className="mt-3 grid gap-3 md:grid-cols-2">
        <label className="text-sm">
          <span className="font-semibold text-stone-600">Select employee</span>
          <select className="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" value={employeeId} onChange={(event) => setEmployeeId(event.target.value)} required>
            <option value="">Select employee</option>
            {employees.map((employee) => <option key={employee.id} value={employee.id}>{employee.employee_code} {employee.display_name}</option>)}
          </select>
        </label>
        <label className="text-sm">
          <span className="font-semibold text-stone-600">Card number</span>
          <input className="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" value={card} onChange={(event) => setCard(event.target.value)} required />
        </label>
        <label className="text-sm">
          <span className="font-semibold text-stone-600">Effective from (with UTC offset)</span>
          <input className="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" placeholder="2026-01-01T00:00:00+06:00" value={from} onChange={(event) => setFrom(event.target.value)} required />
        </label>
        <label className="text-sm">
          <span className="font-semibold text-stone-600">Effective to (optional)</span>
          <input className="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" value={to} onChange={(event) => setTo(event.target.value)} />
        </label>
        <label className="text-sm md:col-span-2">
          <span className="font-semibold text-stone-600">Evidence reference (optional, kept in the audit history)</span>
          <input className="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" value={reference} onChange={(event) => setReference(event.target.value)} />
        </label>
      </div>
      <button className="mt-4 rounded-lg bg-stone-900 px-4 py-2 text-sm font-semibold text-white" type="submit" disabled={pending || employeeId === ''}>Register card</button>
    </form>
  );
}

function ConflictPanel({ conflicts, rows, onOpen }: { conflicts: PunchIdentityConflicts; rows: PunchIdentityRow[]; onOpen: (identityId: string) => void }) {
  const byId = new Map(rows.map((row) => [row.id, row]));
  const empty = conflicts.in_company.length === 0 && conflicts.other_company.length === 0;

  return (
    <section className="rounded-xl border border-stone-200 bg-white px-4 py-4 shadow-sm" aria-label="Ambiguous card mappings">
      <h2 className="text-lg font-semibold">Ambiguous mappings</h2>
      <p className="mt-1 text-sm text-stone-600">Resolve a shared card by ending the mapping that no longer applies. Documented overlaps are listed for reference; they are not recorded from this screen.</p>
      {empty ? <p className="mt-2 text-sm">No card is shared between employees, or with another company, for an overlapping period.</p> : null}
      {conflicts.in_company.length > 0 ? (
        <ul className="mt-2 space-y-1 text-sm">
          {conflicts.in_company.map((item) => (
            <li key={item.identity_ids.join('-')}>
              Card {item.identity_value}: {item.employee_codes.join(' and ')} — {item.documented ? 'documented overlap' : 'not documented'}{' '}
              {item.identity_ids.map((identityId) => (
                <button key={identityId} className="ml-2 font-semibold text-indigo-700" type="button" onClick={() => onOpen(identityId)}>Open {byId.get(identityId)?.employee_code ?? 'mapping'}</button>
              ))}
            </li>
          ))}
        </ul>
      ) : null}
      {conflicts.other_company.length > 0 ? (
        <ul className="mt-2 space-y-1 text-sm">
          {conflicts.other_company.map((item) => (
            <li key={item.identity_value}>
              Card {item.identity_value} is also registered in another company. Device-log rows for it stay ambiguous from{' '}
              {item.shared_periods.map((period) => `${period.from} to ${period.to ?? 'open'}`).join(', ')}.{' '}
              {item.identity_ids.map((identityId) => (
                <button key={identityId} className="ml-2 font-semibold text-indigo-700" type="button" onClick={() => onOpen(identityId)}>Open mapping</button>
              ))}
            </li>
          ))}
        </ul>
      ) : null}
      {conflicts.documented_overlaps.length > 0 ? (
        <ul className="mt-2 list-disc pl-5 text-sm text-stone-600" aria-label="Documented overlaps">
          {conflicts.documented_overlaps.map((overlap) => <li key={`${overlap.identity_value}-${overlap.effective_from}`}>{overlap.identity_value}: {overlap.effective_from} to {overlap.effective_to} — {overlap.reason}</li>)}
        </ul>
      ) : null}
    </section>
  );
}

function DetailPanel({ detail, onEnded, onNotice }: { detail: PunchIdentityDetail; onEnded: (message: string) => void; onNotice: (message: string) => void }) {
  const [effectiveTo, setEffectiveTo] = useState('');
  const [reason, setReason] = useState('');
  const [pending, setPending] = useState(false);

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setPending(true);
    const result = await endPunchIdentity(detail.id, effectiveTo.trim(), reason);
    setPending(false);
    if (result.status === 'ok') {
      setEffectiveTo('');
      setReason('');
      onEnded(`The mapping for card ${result.body.identity_value} now ends at ${result.body.effective_to ?? '—'}.`);
      return;
    }
    onNotice(result.status === 'unauthenticated' ? attendanceMessages.unauthenticated : result.message);
  }

  return (
    <section className="rounded-xl border border-stone-200 bg-white px-4 py-4 shadow-sm" aria-label="Card mapping history">
      <h2 className="text-lg font-semibold">Card {detail.identity_value} — {detail.employee_code ?? detail.employee_id}</h2>
      <p className="mt-1 text-sm text-stone-600">{detail.effective_from ?? '—'} to {detail.effective_to ?? 'open'} · {detail.status} · {detail.punch_event_count} recorded punches</p>
      {detail.warning ? <p className="mt-2 text-sm text-amber-700">{detail.warning}</p> : null}
      <h3 className="mt-4 font-semibold">History</h3>
      <ul className="mt-1 space-y-1 text-sm text-stone-600">
        {detail.history.map((event, index) => (
          <li key={`${event.action_kind}-${index}`}>
            {event.acted_at ?? '—'} · {event.action_kind.replaceAll('_', ' ')} by {event.acting_user_name ?? event.acting_user_id ?? '—'}{event.reason ? ` — ${event.reason}` : ''}
          </li>
        ))}
      </ul>
      <form className="mt-4 flex flex-wrap items-end gap-3 border-t border-stone-100 pt-4" onSubmit={onSubmit} aria-label="End this mapping">
        <label className="text-sm">
          <span className="font-semibold text-stone-600">End at (with UTC offset)</span>
          <input className="mt-1 rounded-lg border border-stone-300 px-3 py-2" value={effectiveTo} onChange={(event) => setEffectiveTo(event.target.value)} required />
        </label>
        <label className="text-sm">
          <span className="font-semibold text-stone-600">Reason for ending</span>
          <input className="mt-1 w-72 rounded-lg border border-stone-300 px-3 py-2" value={reason} onChange={(event) => setReason(event.target.value)} required />
        </label>
        <button className="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold" type="submit" disabled={pending}>End mapping</button>
      </form>
    </section>
  );
}
