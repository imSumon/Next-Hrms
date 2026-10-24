import { useEffect, useState, type ReactNode } from 'react';
import { Link, useParams, useSearchParams } from 'react-router-dom';
import { RealSessionLinks } from '../components/RealSessionLinks';
import { SessionLogoutButton, SessionLogoutError, SignedOutScreen, useSessionLogout } from '../components/SessionLogout';
import {
  createMasterRecord,
  fetchMasterPage,
  fetchMasterRecord,
  masterLabel,
  masterMessages,
  masterPath,
  updateMasterRecord,
  type MasterChange,
  type MasterDetail,
  type MasterKind,
  type MasterListResult,
  type MasterPage,
  type MasterWriteResult,
} from '../lib/organizationMasterApi';

function requestedPage(value: string | null): number {
  const page = Number(value ?? '1');
  if (!Number.isFinite(page) || page < 1) {
    return 1;
  }
  return Math.floor(page);
}

export function RealSessionDepartmentsPage() {
  return <RealSessionMasterPage kind="department" />;
}

export function RealSessionDesignationsPage() {
  return <RealSessionMasterPage kind="designation" />;
}

function RealSessionMasterPage({ kind }: { kind: MasterKind }) {
  const { recordId } = useParams();
  const label = masterLabel(kind);
  const logout = useSessionLogout();

  useEffect(() => {
    document.title = `${label} — Universal HRMS`;
  }, [label]);

  if (logout.state === 'signed_out') {
    return <SignedOutScreen />;
  }

  return (
    <div className="min-h-screen bg-[#fafaf9] text-stone-900">
      <header className="border-b border-stone-200 bg-white">
        <div className="mx-auto flex w-full max-w-6xl flex-wrap items-center gap-3 px-4 py-4">
          <p className="text-lg font-semibold text-[#111827]">Universal HRMS</p>
          <p className="rounded-full bg-stone-900 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-white">Laravel session</p>
          <RealSessionLinks label={`${label} navigation`}>
            <SessionLogoutButton logout={logout} />
          </RealSessionLinks>
        </div>
      </header>
      <main className="mx-auto w-full max-w-6xl px-4 py-8">
        <SessionLogoutError logout={logout} />
        {recordId ? <MasterRecordView kind={kind} recordId={recordId} /> : <MasterListView kind={kind} />}
      </main>
    </div>
  );
}

function MasterListView({ kind }: { kind: MasterKind }) {
  const label = masterLabel(kind);
  const [params, setParams] = useSearchParams();
  const rawQuery = params.get('q') ?? '';
  const page = requestedPage(params.get('page'));
  const [draft, setDraft] = useState(rawQuery);
  const [result, setResult] = useState<MasterListResult>({ status: 'unauthenticated' });
  const [loading, setLoading] = useState(true);
  const [reloadKey, setReloadKey] = useState(0);

  useEffect(() => {
    setDraft(rawQuery);
  }, [rawQuery]);

  useEffect(() => {
    const controller = new AbortController();
    setLoading(true);
    fetchMasterPage(kind, rawQuery, page, controller.signal).then((next) => {
      if (controller.signal.aborted || next.status === 'aborted') {
        return;
      }
      setResult(next);
      setLoading(false);
    });
    return () => controller.abort();
  }, [kind, page, rawQuery, reloadKey]);

  return (
    <>
      <p className="text-sm text-stone-500">Laravel session / {label}</p>
      <h1 className="mt-1 text-2xl font-semibold text-stone-900">{label}</h1>
      <p className="mt-3 max-w-3xl text-sm leading-6 text-stone-600">
        These records belong to the company selected by the Laravel session. They are not assigned to employees on this page.
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
        <label className="block text-sm font-semibold" htmlFor={`${kind}-q`}>Search by code or name</label>
        <div className="mt-2 flex flex-col gap-2 sm:flex-row">
          <input id={`${kind}-q`} value={draft} autoComplete="off" onChange={(event) => setDraft(event.target.value)} className="w-full rounded-md border border-stone-300 px-3 py-2" />
          <button type="submit" className="rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white">Search</button>
        </div>
      </form>
      <div className="mt-4">
        {loading ? <Status title={`Reading ${label.toLowerCase()}.`} role="status" /> : <ListBody kind={kind} result={result} rawQuery={rawQuery} onCreated={() => setReloadKey((value) => value + 1)} onRetry={() => setReloadKey((value) => value + 1)} />}
      </div>
    </>
  );
}

function ListBody({ kind, result, rawQuery, onCreated, onRetry }: { kind: MasterKind; result: MasterListResult; rawQuery: string; onCreated: () => void; onRetry: () => void }) {
  if (result.status === 'unauthenticated') {
    return <Status title={masterMessages.unauthenticated}><a className="font-semibold text-indigo-700" href="/login">Open Laravel login</a></Status>;
  }
  if (result.status === 'forbidden') {
    return <Status title={masterMessages.forbidden}>{result.message !== masterMessages.forbidden ? <p>{result.message}</p> : null}</Status>;
  }
  if (result.status === 'error' || result.status === 'aborted') {
    return <Status title={masterMessages.error}><button type="button" className="rounded-md border border-stone-300 bg-white px-3 py-1.5 text-sm font-semibold" onClick={onRetry}>Try again</button></Status>;
  }
  return (
    <>
      <section className="mb-4 rounded-xl border border-stone-200 bg-white px-4 py-3 shadow-sm" aria-label="Active company">
        <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">Active company</p>
        <p className="mt-1 text-lg font-semibold">{result.page.company_name === '' ? '—' : result.page.company_name}</p>
      </section>
      {result.page.can_manage_organization_masters ? <CreateForm kind={kind} onCreated={onCreated} /> : null}
      <MasterTable kind={kind} page={result.page} rawQuery={rawQuery} />
    </>
  );
}

function MasterTable({ kind, page, rawQuery }: { kind: MasterKind; page: MasterPage; rawQuery: string }) {
  const search = rawQuery === '' ? '' : `?q=${encodeURIComponent(rawQuery)}`;
  return (
    <div className="mt-4 overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
      {page.data.length === 0 ? (
        <p className="px-4 py-8 text-stone-600">{rawQuery === '' ? `No ${masterLabel(kind).toLowerCase()} in this company.` : `No ${masterLabel(kind).toLowerCase()} match this search.`}</p>
      ) : (
        <table className="w-full border-collapse text-left">
          <thead>
            <tr className="border-b border-stone-200 text-sm text-stone-500">
              <th className="px-4 py-3 font-semibold" scope="col">Code</th>
              <th className="px-4 py-3 font-semibold" scope="col">Name</th>
              <th className="px-4 py-3 font-semibold" scope="col">Status</th>
              <th className="px-4 py-3 font-semibold" scope="col"><span className="sr-only">Open</span></th>
            </tr>
          </thead>
          <tbody>
            {page.data.map((record) => (
              <tr key={record.id} className="border-b border-stone-100 last:border-0">
                <td className="px-4 py-3">{record.code ?? '—'}</td>
                <td className="px-4 py-3">{record.name}</td>
                <td className="px-4 py-3">{record.status}</td>
                <td className="px-4 py-3">
                  <Link className="font-semibold text-indigo-700" to={`/session/${masterPath(kind)}/${record.id}${search}`}>Open</Link>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
      {page.last_page > 1 ? <p className="border-t border-stone-200 px-4 py-3 text-sm text-stone-600">Showing {page.from ?? 0} to {page.to ?? 0} of {page.total}</p> : null}
    </div>
  );
}

function CreateForm({ kind, onCreated }: { kind: MasterKind; onCreated: () => void }) {
  const [name, setName] = useState('');
  const [code, setCode] = useState('');
  const [status, setStatus] = useState('');
  const [write, setWrite] = useState<MasterWriteResult | null>(null);
  const [saving, setSaving] = useState(false);
  const errors = write?.status === 'invalid' ? Object.values(write.errors).flat() : [];

  return (
    <form
      className="rounded-xl border border-stone-200 bg-white p-4 shadow-sm"
      onSubmit={(event) => {
        event.preventDefault();
        setSaving(true);
        setWrite(null);
        createMasterRecord(kind, { name, code, status }).then((next) => {
          setSaving(false);
          setWrite(next);
          if (next.status === 'ok') {
            setName('');
            setCode('');
            setStatus('');
            onCreated();
          }
        });
      }}
    >
      <h2 className="text-lg font-semibold">Add {kind}</h2>
      <div className="mt-3 grid gap-3 sm:grid-cols-3">
        <label className="block text-sm font-semibold">Name
          <input value={name} onChange={(event) => setName(event.target.value)} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal" aria-invalid={errors.length > 0} />
        </label>
        <label className="block text-sm font-semibold">Code
          <input value={code} onChange={(event) => setCode(event.target.value)} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal" />
        </label>
        <label className="block text-sm font-semibold">Status
          <select value={status} onChange={(event) => setStatus(event.target.value)} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal">
            <option value="">Select status</option>
            <option value="Active">Active</option>
            <option value="withdrawn">withdrawn</option>
          </select>
        </label>
      </div>
      {saving ? <p className="mt-3 text-sm text-stone-600">Saving.</p> : null}
      {write?.status === 'ok' ? <p className="mt-3 text-sm font-semibold" role="status">{masterMessages.saved}</p> : null}
      {errors.length > 0 ? <ul className="mt-3 list-disc pl-5 text-sm" role="alert">{errors.map((message) => <li key={message}>{message}</li>)}</ul> : null}
      {write?.status === 'forbidden' ? <p className="mt-3 text-sm" role="alert">{write.message}</p> : null}
      {write?.status === 'error' ? <p className="mt-3 text-sm" role="alert">{write.message}</p> : null}
      <button type="submit" disabled={saving} className="mt-3 rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">Save</button>
    </form>
  );
}

function MasterRecordView({ kind, recordId }: { kind: MasterKind; recordId: string }) {
  const [record, setRecord] = useState<MasterDetail | null>(null);
  const [state, setState] = useState<'loading' | 'ready' | 'unauthenticated' | 'forbidden' | 'not_found' | 'error'>('loading');
  const [message, setMessage] = useState('');
  const [reloadKey, setReloadKey] = useState(0);

  useEffect(() => {
    const controller = new AbortController();
    setState('loading');
    fetchMasterRecord(kind, recordId, controller.signal).then((next) => {
      if (controller.signal.aborted || next.status === 'aborted') {
        return;
      }
      if (next.status === 'ok') {
        setRecord(next.record);
        setState('ready');
        return;
      }
      setRecord(null);
      setState(next.status === 'forbidden' ? 'forbidden' : next.status === 'unauthenticated' ? 'unauthenticated' : next.status === 'not_found' ? 'not_found' : 'error');
      setMessage(next.status === 'forbidden' ? next.message : masterMessages.error);
    });
    return () => controller.abort();
  }, [kind, recordId, reloadKey]);

  if (state === 'loading') {
    return <Status title="Reading the record." role="status" />;
  }
  if (state === 'unauthenticated') {
    return <Status title={masterMessages.unauthenticated} />;
  }
  if (state === 'forbidden') {
    return <Status title={masterMessages.forbidden}><p>{message}</p></Status>;
  }
  if (state === 'not_found') {
    return <Status title={masterMessages.notFound} />;
  }
  if (state !== 'ready' || record === null) {
    return <Status title={masterMessages.error}><button type="button" className="rounded-md border border-stone-300 bg-white px-3 py-1.5 text-sm font-semibold" onClick={() => setReloadKey((value) => value + 1)}>Try again</button></Status>;
  }

  return (
    <>
      <p className="text-sm text-stone-500">
        <Link className="text-indigo-700" to={`/session/${masterPath(kind)}`}>{masterLabel(kind)}</Link>
        <span> / {record.name}</span>
      </p>
      <h1 className="mt-1 text-2xl font-semibold">{record.name}</h1>
      <p className="mt-1 text-stone-500">{record.company_name} · {record.code ?? '—'} · {record.status}</p>
      {record.can_manage_organization_masters ? <EditForm kind={kind} record={record} onUpdated={setRecord} /> : null}
      <ChangeHistory changes={record.changes} />
    </>
  );
}

function EditForm({ kind, record, onUpdated }: { kind: MasterKind; record: MasterDetail; onUpdated: (record: MasterDetail) => void }) {
  const [name, setName] = useState(record.name);
  const [status, setStatus] = useState(record.status);
  const [write, setWrite] = useState<MasterWriteResult | null>(null);
  const [saving, setSaving] = useState(false);
  const errors = write?.status === 'invalid' ? Object.values(write.errors).flat() : [];

  useEffect(() => {
    setName(record.name);
    setStatus(record.status);
  }, [record.id, record.name, record.status]);

  return (
    <form
      className="mt-4 rounded-xl border border-stone-200 bg-white p-4 shadow-sm"
      onSubmit={(event) => {
        event.preventDefault();
        setSaving(true);
        setWrite(null);
        updateMasterRecord(kind, record.id, { name, status }).then((next) => {
          setSaving(false);
          setWrite(next);
          if (next.status === 'ok') {
            onUpdated(next.record);
          }
        });
      }}
    >
      <label className="block text-sm font-semibold">Name
        <input value={name} onChange={(event) => setName(event.target.value)} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal" />
      </label>
      <p className="mt-3 text-sm text-stone-600">Code: {record.code ?? '—'}. The code stays on this record.</p>
      <label className="mt-3 block text-sm font-semibold">Status
        <select value={status} onChange={(event) => {
          const value = event.target.value;
          if (value === 'Active' || value === 'withdrawn') {
            setStatus(value);
          }
        }} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal">
          <option value="Active">Active</option>
          <option value="withdrawn">withdrawn</option>
        </select>
      </label>
      {saving ? <p className="mt-3 text-sm text-stone-600">Saving.</p> : null}
      {write?.status === 'ok' ? <p className="mt-3 text-sm font-semibold" role="status">{masterMessages.saved}</p> : null}
      {errors.length > 0 ? <ul className="mt-3 list-disc pl-5 text-sm" role="alert">{errors.map((message) => <li key={message}>{message}</li>)}</ul> : null}
      {write?.status === 'forbidden' || write?.status === 'error' ? <p className="mt-3 text-sm" role="alert">{write.message}</p> : null}
      {write?.status === 'unauthenticated' ? <p className="mt-3 text-sm" role="alert">{masterMessages.unauthenticated}</p> : null}
      {write?.status === 'not_found' ? <p className="mt-3 text-sm" role="alert">{masterMessages.notFound}</p> : null}
      <div className="mt-3 flex gap-2">
        <button type="submit" disabled={saving} className="rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">Save</button>
        {name !== record.name || status !== record.status ? (
          <button
            type="button"
            disabled={saving}
            className="rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-semibold"
            onClick={() => {
              setName(record.name);
              setStatus(record.status);
              setWrite(null);
            }}
          >
            Cancel
          </button>
        ) : null}
      </div>
    </form>
  );
}

function ChangeHistory({ changes }: { changes: MasterChange[] }) {
  return (
    <section className="mt-4 overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
      <h2 className="border-b border-stone-200 px-4 py-3 text-lg font-semibold">Name and status history</h2>
      {changes.length === 0 ? <p className="px-4 py-8 text-stone-600">No name or status changes.</p> : (
        <ul className="divide-y divide-stone-100">
          {changes.map((change, index) => (
            <li key={`${change.acted_at ?? 'undated'}-${index}`} className="px-4 py-4 text-sm">
              <p>{change.previous_name === null ? change.previous_status : change.previous_name} → {change.new_name === null ? change.new_status : change.new_name}</p>
              <p className="mt-1 text-stone-500">{change.actor_name || '—'} · {change.acted_at || '—'}</p>
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}

function Status({ title, children, role = 'alert' }: { title: string; children?: ReactNode; role?: 'alert' | 'status' }) {
  return (
    <div className="rounded-xl border border-stone-200 bg-white px-4 py-8 shadow-sm" role={role}>
      <p className="font-semibold">{title}</p>
      {children ? <div className="mt-2 text-sm text-stone-600">{children}</div> : null}
    </div>
  );
}
