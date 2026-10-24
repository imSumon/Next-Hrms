import { useEffect, useState, type ReactNode } from 'react';
import { Link, useParams } from 'react-router-dom';
import { RealSessionLinks } from '../components/RealSessionLinks';
import { SessionLogoutButton, SessionLogoutError, SignedOutScreen, useSessionLogout } from '../components/SessionLogout';
import {
  createLeaveType,
  fetchLeaveType,
  fetchLeaveTypes,
  leaveMessages,
  updateLeaveType,
  type LeaveTypeChange,
  type LeaveTypeDetail,
  type LeaveTypeList,
  type LeaveTypeRecord,
  type LeaveTypeStatus,
  type LeaveWriteResult,
} from '../lib/leavePositionRecordingApi';

export function RealSessionLeaveTypesPage() {
  const { recordId } = useParams();
  const logout = useSessionLogout();

  useEffect(() => {
    document.title = 'Leave types — Universal HRMS';
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
          <RealSessionLinks label="Leave types navigation">
            <SessionLogoutButton logout={logout} />
          </RealSessionLinks>
        </div>
      </header>
      <main className="mx-auto w-full max-w-6xl px-4 py-8">
        <SessionLogoutError logout={logout} />
        {recordId ? <TypeRecordView recordId={recordId} /> : <TypeListView />}
      </main>
    </div>
  );
}

function TypeListView() {
  const [result, setResult] = useState<Awaited<ReturnType<typeof fetchLeaveTypes>>>({ status: 'unauthenticated' });
  const [loading, setLoading] = useState(true);
  const [reloadKey, setReloadKey] = useState(0);

  useEffect(() => {
    const controller = new AbortController();
    setLoading(true);
    fetchLeaveTypes(controller.signal).then((next) => {
      if (controller.signal.aborted || next.status === 'aborted') {
        return;
      }
      setResult(next);
      setLoading(false);
    });
    return () => controller.abort();
  }, [reloadKey]);

  return (
    <>
      <p className="text-sm text-stone-500">Laravel session / Leave types</p>
      <h1 className="mt-1 text-2xl font-semibold text-stone-900">Leave types</h1>
      <p className="mt-3 max-w-3xl text-sm leading-6 text-stone-600">
        Company-scoped leave-type names and status only. These records belong to the Laravel session company. They do not create an approved or usable leave balance.
      </p>
      <div className="mt-4">
        {loading ? <Status title="Reading leave types." role="status" /> : <ListBody result={result} onCreated={() => setReloadKey((value) => value + 1)} onRetry={() => setReloadKey((value) => value + 1)} />}
      </div>
    </>
  );
}

function ListBody({ result, onCreated, onRetry }: { result: Awaited<ReturnType<typeof fetchLeaveTypes>>; onCreated: () => void; onRetry: () => void }) {
  if (result.status === 'unauthenticated') {
    return <Status title={leaveMessages.unauthenticated}><a className="font-semibold text-indigo-700" href="/login">Open Laravel login</a></Status>;
  }
  if (result.status === 'forbidden') {
    return <Status title={leaveMessages.forbidden}>{result.message !== leaveMessages.forbidden ? <p>{result.message}</p> : null}</Status>;
  }
  if (result.status === 'error' || result.status === 'aborted') {
    return <Status title={leaveMessages.error}><button type="button" className="rounded-md border border-stone-300 bg-white px-3 py-1.5 text-sm font-semibold" onClick={onRetry}>Try again</button></Status>;
  }
  return (
    <>
      <CompanyBanner name={result.page.company_name} />
      <CreateTypeForm onCreated={onCreated} />
      <TypeTable page={result.page} />
    </>
  );
}

function TypeTable({ page }: { page: LeaveTypeList }) {
  return (
    <div className="mt-4 overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
      {page.data.length === 0 ? (
        <p className="px-4 py-8 text-stone-600">No leave types in this company.</p>
      ) : (
        <table className="w-full border-collapse text-left">
          <thead>
            <tr className="border-b border-stone-200 text-sm text-stone-500">
              <th className="px-4 py-3 font-semibold" scope="col">Name</th>
              <th className="px-4 py-3 font-semibold" scope="col">Status</th>
              <th className="px-4 py-3 font-semibold" scope="col"><span className="sr-only">Open</span></th>
            </tr>
          </thead>
          <tbody>
            {page.data.map((record) => (
              <TypeRow key={record.id} record={record} />
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
}

function TypeRow({ record }: { record: LeaveTypeRecord }) {
  return (
    <tr className="border-b border-stone-100 last:border-0">
      <td className="px-4 py-3">{record.name}</td>
      <td className="px-4 py-3">{record.status}</td>
      <td className="px-4 py-3">
        <Link className="font-semibold text-indigo-700" to={`/session/leave-types/${record.id}`}>Open</Link>
      </td>
    </tr>
  );
}

function CreateTypeForm({ onCreated }: { onCreated: () => void }) {
  const [name, setName] = useState('');
  const [status, setStatus] = useState('');
  const [write, setWrite] = useState<LeaveWriteResult<LeaveTypeDetail> | null>(null);
  const [saving, setSaving] = useState(false);
  const errors = write?.status === 'invalid' ? Object.values(write.errors).flat() : [];

  return (
    <form
      className="rounded-xl border border-stone-200 bg-white p-4 shadow-sm"
      onSubmit={(event) => {
        event.preventDefault();
        setSaving(true);
        setWrite(null);
        createLeaveType({ name, status }).then((next) => {
          setSaving(false);
          setWrite(next);
          if (next.status === 'ok') {
            setName('');
            setStatus('');
            onCreated();
          }
        });
      }}
    >
      <h2 className="text-lg font-semibold">Add leave type</h2>
      <p className="mt-1 text-sm text-stone-600">Only name and status are recorded.</p>
      <div className="mt-3 grid gap-3 sm:grid-cols-2">
        <label className="block text-sm font-semibold">Name
          <input value={name} onChange={(event) => setName(event.target.value)} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal" aria-invalid={errors.length > 0} />
        </label>
        <label className="block text-sm font-semibold">Status
          <select value={status} onChange={(event) => setStatus(event.target.value)} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal">
            <option value="">Select status</option>
            <option value="Active">Active</option>
            <option value="withdrawn">withdrawn</option>
          </select>
        </label>
      </div>
      <WriteFeedback saving={saving} write={write} errors={errors} />
      <button type="submit" disabled={saving} className="mt-3 rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">Save</button>
    </form>
  );
}

function TypeRecordView({ recordId }: { recordId: string }) {
  const [record, setRecord] = useState<LeaveTypeDetail | null>(null);
  const [state, setState] = useState<'loading' | 'ready' | 'unauthenticated' | 'forbidden' | 'not_found' | 'error'>('loading');
  const [message, setMessage] = useState('');
  const [reloadKey, setReloadKey] = useState(0);

  useEffect(() => {
    const controller = new AbortController();
    setState('loading');
    fetchLeaveType(recordId, controller.signal).then((next) => {
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
      setMessage(next.status === 'forbidden' ? next.message : leaveMessages.error);
    });
    return () => controller.abort();
  }, [recordId, reloadKey]);

  if (state === 'loading') {
    return <Status title="Reading the record." role="status" />;
  }
  if (state === 'unauthenticated') {
    return <Status title={leaveMessages.unauthenticated}><a className="font-semibold text-indigo-700" href="/login">Open Laravel login</a></Status>;
  }
  if (state === 'forbidden') {
    return <Status title={leaveMessages.forbidden}><p>{message}</p></Status>;
  }
  if (state === 'not_found') {
    return <Status title={leaveMessages.notFound} />;
  }
  if (state !== 'ready' || record === null) {
    return <Status title={leaveMessages.error}><button type="button" className="rounded-md border border-stone-300 bg-white px-3 py-1.5 text-sm font-semibold" onClick={() => setReloadKey((value) => value + 1)}>Try again</button></Status>;
  }

  return (
    <>
      <p className="text-sm text-stone-500">
        <Link className="text-indigo-700" to="/session/leave-types">Leave types</Link>
        <span> / {record.name}</span>
      </p>
      <h1 className="mt-1 text-2xl font-semibold">{record.name}</h1>
      <p className="mt-1 text-stone-500">{record.company_name} · {record.status}</p>
      <EditTypeForm record={record} onUpdated={setRecord} />
      <ChangeHistory changes={record.changes} />
    </>
  );
}

function EditTypeForm({ record, onUpdated }: { record: LeaveTypeDetail; onUpdated: (record: LeaveTypeDetail) => void }) {
  const [name, setName] = useState(record.name);
  const [status, setStatus] = useState<LeaveTypeStatus>(record.status);
  const [write, setWrite] = useState<LeaveWriteResult<LeaveTypeDetail> | null>(null);
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
        updateLeaveType(record.id, { name, status }).then((next) => {
          setSaving(false);
          setWrite(next);
          if (next.status === 'ok') {
            onUpdated(next.record);
          }
        });
      }}
    >
      <h2 className="text-lg font-semibold">Rename or change status</h2>
      <label className="mt-3 block text-sm font-semibold">Name
        <input value={name} onChange={(event) => setName(event.target.value)} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal" />
      </label>
      <label className="mt-3 block text-sm font-semibold">Status
        <select value={status} onChange={(event) => {
          if (event.target.value === 'Active' || event.target.value === 'withdrawn') {
            setStatus(event.target.value);
          }
        }} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal">
          <option value="Active">Active</option>
          <option value="withdrawn">withdrawn</option>
        </select>
      </label>
      <WriteFeedback saving={saving} write={write} errors={errors} />
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

function ChangeHistory({ changes }: { changes: LeaveTypeChange[] }) {
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

function WriteFeedback({ saving, write, errors }: { saving: boolean; write: LeaveWriteResult<unknown> | null; errors: string[] }) {
  return (
    <>
      {saving ? <p className="mt-3 text-sm text-stone-600">Saving.</p> : null}
      {write?.status === 'ok' ? <p className="mt-3 text-sm font-semibold" role="status">{leaveMessages.saved}</p> : null}
      {errors.length > 0 ? <ul className="mt-3 list-disc pl-5 text-sm" role="alert">{errors.map((message) => <li key={message}>{message}</li>)}</ul> : null}
      {write?.status === 'forbidden' || write?.status === 'error' ? <p className="mt-3 text-sm" role="alert">{write.message}</p> : null}
      {write?.status === 'unauthenticated' ? <p className="mt-3 text-sm" role="alert">{leaveMessages.unauthenticated}</p> : null}
      {write?.status === 'not_found' ? <p className="mt-3 text-sm" role="alert">{leaveMessages.notFound}</p> : null}
    </>
  );
}

function CompanyBanner({ name }: { name: string }) {
  return (
    <section className="mb-4 rounded-xl border border-stone-200 bg-white px-4 py-3 shadow-sm" aria-label="Active company">
      <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">Active company</p>
      <p className="mt-1 text-lg font-semibold">{name === '' ? '—' : name}</p>
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
