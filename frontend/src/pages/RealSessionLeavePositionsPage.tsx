import { useEffect, useState, type ReactNode } from 'react';
import { Link, useParams } from 'react-router-dom';
import { RealSessionLinks } from '../components/RealSessionLinks';
import { SessionLogoutButton, SessionLogoutError, SignedOutScreen, useSessionLogout } from '../components/SessionLogout';
import { directoryMessages, fetchEmployeeDirectory, type DirectoryEmployee } from '../lib/employeeDirectoryApi';
import { fetchEmployeeProfile } from '../lib/employeeProfileApi';
import {
  createLeaveMovement,
  createLeavePosition,
  fetchLeaveMovements,
  fetchLeavePosition,
  fetchLeavePositions,
  fetchLeaveTypes,
  leaveMessages,
  updateLeavePosition,
  type LeaveMovementInput,
  type LeaveMovementList,
  type LeaveMovementRecord,
  type LeavePositionInput,
  type LeavePositionList,
  type LeavePositionRecord,
  type LeavePositionUpdateInput,
  type LeaveTypeRecord,
  type LeaveWriteResult,
} from '../lib/leavePositionRecordingApi';

export function RealSessionLeavePositionsPage() {
  const { recordId } = useParams();
  const logout = useSessionLogout();

  useEffect(() => {
    document.title = 'Leave positions — Universal HRMS';
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
          <RealSessionLinks label="Leave positions navigation">
            <SessionLogoutButton logout={logout} />
          </RealSessionLinks>
        </div>
      </header>
      <main className="mx-auto w-full max-w-6xl px-4 py-8">
        <SessionLogoutError logout={logout} />
        {recordId ? <PositionRecordView recordId={recordId} /> : <PositionListView />}
      </main>
    </div>
  );
}

function PositionListView() {
  const [result, setResult] = useState<Awaited<ReturnType<typeof fetchLeavePositions>>>({ status: 'unauthenticated' });
  const [typesResult, setTypesResult] = useState<Awaited<ReturnType<typeof fetchLeaveTypes>>>({ status: 'unauthenticated' });
  const [directory, setDirectory] = useState<DirectoryLoad>({ status: 'unauthenticated' });
  const [loading, setLoading] = useState(true);
  const [reloadKey, setReloadKey] = useState(0);

  useEffect(() => {
    const controller = new AbortController();
    setLoading(true);
    Promise.all([
      fetchLeavePositions(controller.signal),
      fetchLeaveTypes(controller.signal),
      loadEmployees(controller.signal),
    ]).then(([positions, leaveTypes, employees]) => {
      if (controller.signal.aborted || positions.status === 'aborted' || leaveTypes.status === 'aborted' || employees.status === 'aborted') {
        return;
      }
      setResult(positions);
      setTypesResult(leaveTypes);
      setDirectory(employees);
      setLoading(false);
    });
    return () => controller.abort();
  }, [reloadKey]);

  return (
    <>
      <p className="text-sm text-stone-500">Laravel session / Leave positions</p>
      <h1 className="mt-1 text-2xl font-semibold text-stone-900">Leave positions</h1>
      <p className="mt-3 max-w-3xl text-sm leading-6 text-stone-600">
        Proposed or excepted leave-position records and their movements. A stored amount is recorded evidence only. It is not an approved or usable leave balance.
      </p>
      <div className="mt-4">
        {loading ? <Status title="Reading leave positions." role="status" /> : (
          <PositionListBody
            result={result}
            typesResult={typesResult}
            directory={directory}
            onCreated={() => setReloadKey((value) => value + 1)}
            onRetry={() => setReloadKey((value) => value + 1)}
          />
        )}
      </div>
    </>
  );
}

function PositionListBody({
  result,
  typesResult,
  directory,
  onCreated,
  onRetry,
}: {
  result: Awaited<ReturnType<typeof fetchLeavePositions>>;
  typesResult: Awaited<ReturnType<typeof fetchLeaveTypes>>;
  directory: DirectoryLoad;
  onCreated: () => void;
  onRetry: () => void;
}) {
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
      <CreatePositionForm typesResult={typesResult} directory={directory} onCreated={onCreated} onRetry={onRetry} />
      <PositionTable page={result.page} types={typesResult.status === 'ok' ? typesResult.page.data : []} employees={directory.status === 'ok' ? directory.employees : []} />
    </>
  );
}

function PositionTable({ page, types, employees }: { page: LeavePositionList; types: LeaveTypeRecord[]; employees: DirectoryEmployee[] }) {
  return (
    <div className="mt-4 overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
      {page.data.length === 0 ? (
        <p className="px-4 py-8 text-stone-600">No leave positions in this company.</p>
      ) : (
        <table className="w-full border-collapse text-left">
          <thead>
            <tr className="border-b border-stone-200 text-sm text-stone-500">
              <th className="px-4 py-3 font-semibold" scope="col">Employee</th>
              <th className="px-4 py-3 font-semibold" scope="col">Leave type</th>
              <th className="px-4 py-3 font-semibold" scope="col">Proposed amount</th>
              <th className="px-4 py-3 font-semibold" scope="col">Status</th>
              <th className="px-4 py-3 font-semibold" scope="col">Period evidence</th>
              <th className="px-4 py-3 font-semibold" scope="col">Usable as approved balance</th>
              <th className="px-4 py-3 font-semibold" scope="col"><span className="sr-only">Open</span></th>
            </tr>
          </thead>
          <tbody>
            {page.data.map((record) => (
              <tr key={record.id} className="border-b border-stone-100 last:border-0">
                <td className="px-4 py-3">{employeeLabel(employees, record.employee_id)}</td>
                <td className="px-4 py-3">{typeLabel(types, record.leave_type_id)}</td>
                <td className="px-4 py-3">{record.proposed_amount}</td>
                <td className="px-4 py-3">{record.status}</td>
                <td className="px-4 py-3">{periodLabel(record)}</td>
                <td className="px-4 py-3">Not usable</td>
                <td className="px-4 py-3">
                  <Link className="font-semibold text-indigo-700" to={`/session/leave-positions/${record.id}`}>Open</Link>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
}

function CreatePositionForm({
  typesResult,
  directory,
  onCreated,
  onRetry,
}: {
  typesResult: Awaited<ReturnType<typeof fetchLeaveTypes>>;
  directory: DirectoryLoad;
  onCreated: () => void;
  onRetry: () => void;
}) {
  const types = typesResult.status === 'ok' ? typesResult.page.data : [];
  const employees = directory.status === 'ok' ? directory.employees : [];
  const [input, setInput] = useState(emptyPositionInput());
  const [write, setWrite] = useState<LeaveWriteResult<LeavePositionRecord> | null>(null);
  const [saving, setSaving] = useState(false);
  const errors = write?.status === 'invalid' ? Object.values(write.errors).flat() : [];

  return (
    <form
      className="rounded-xl border border-stone-200 bg-white p-4 shadow-sm"
      onSubmit={(event) => {
        event.preventDefault();
        setSaving(true);
        setWrite(null);
        createLeavePosition(input).then((next) => {
          setSaving(false);
          setWrite(next);
          if (next.status === 'ok') {
            setInput(emptyPositionInput());
            onCreated();
          }
        });
      }}
    >
      <h2 className="text-lg font-semibold">Add leave position</h2>
      <p className="mt-1 text-sm text-stone-600">Status may be proposed or excepted only. Confirmation and approved-balance use are not available.</p>
      {typesResult.status === 'ok' && types.length === 0 ? (
        <p className="mt-3 text-sm text-stone-600">
          Add a leave type first. <Link className="font-semibold text-indigo-700" to="/session/leave-types">Open leave types</Link>
        </p>
      ) : null}
      {typesResult.status === 'error' ? (
        <p className="mt-3 text-sm text-stone-600" role="alert">
          The leave-type list could not be read. <button type="button" className="font-semibold text-indigo-700" onClick={onRetry}>Try again</button>
        </p>
      ) : null}
      {typesResult.status === 'forbidden' ? <p className="mt-3 text-sm" role="alert">{typesResult.message}</p> : null}
      <div className="mt-3 grid gap-3 sm:grid-cols-2">
        <label className="block text-sm font-semibold">Employee
          {directory.status === 'ok' && employees.length > 0 ? (
            <>
              <select value={input.employee_id} onChange={(event) => setInput({ ...input, employee_id: event.target.value })} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal">
                <option value="">Select employee</option>
                {employees.map((employee) => (
                  <option key={employee.id} value={employee.id}>{employee.display_name} ({employee.employee_code})</option>
                ))}
              </select>
              {directory.truncated ? <p className="mt-1 text-sm font-normal text-stone-600">The employee list shows the first 750 records in this company.</p> : null}
            </>
          ) : (
            <DirectoryNotice directory={directory} onRetry={onRetry} />
          )}
        </label>
        <label className="block text-sm font-semibold">Leave type
          <select value={input.leave_type_id} onChange={(event) => setInput({ ...input, leave_type_id: event.target.value })} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal">
            <option value="">Select leave type</option>
            {types.map((type) => (
              <option key={type.id} value={type.id}>{type.name} ({type.status})</option>
            ))}
          </select>
        </label>
        <label className="block text-sm font-semibold">Proposed amount
          <input value={input.proposed_amount} onChange={(event) => setInput({ ...input, proposed_amount: event.target.value })} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal" />
        </label>
        <label className="block text-sm font-semibold">Status
          <select value={input.status} onChange={(event) => {
            if (event.target.value === 'proposed' || event.target.value === 'excepted') {
              setInput({ ...input, status: event.target.value });
            }
          }} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal">
            <option value="proposed">proposed</option>
            <option value="excepted">excepted</option>
          </select>
        </label>
        <label className="block text-sm font-semibold">Period label
          <input value={input.period_label} onChange={(event) => setInput({ ...input, period_label: event.target.value })} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal" />
        </label>
        <label className="block text-sm font-semibold">Period start
          <input type="date" value={input.period_start} onChange={(event) => setInput({ ...input, period_start: event.target.value })} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal" />
        </label>
        <label className="block text-sm font-semibold">Period end
          <input type="date" value={input.period_end} onChange={(event) => setInput({ ...input, period_end: event.target.value })} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal" />
        </label>
        <label className="block text-sm font-semibold sm:col-span-2">Source evidence
          <input value={input.source_evidence} onChange={(event) => setInput({ ...input, source_evidence: event.target.value })} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal" />
        </label>
        <label className="block text-sm font-semibold sm:col-span-2">Stated basis
          <input value={input.stated_basis} onChange={(event) => setInput({ ...input, stated_basis: event.target.value })} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal" />
        </label>
      </div>
      <WriteFeedback saving={saving} write={write} errors={errors} />
      <button type="submit" disabled={saving} className="mt-3 rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">Save</button>
    </form>
  );
}

function PositionRecordView({ recordId }: { recordId: string }) {
  const [record, setRecord] = useState<LeavePositionRecord | null>(null);
  const [movements, setMovements] = useState<LeaveMovementList | null>(null);
  const [typeName, setTypeName] = useState('');
  const [companyName, setCompanyName] = useState('');
  const [employeeName, setEmployeeName] = useState('');
  const [state, setState] = useState<'loading' | 'ready' | 'unauthenticated' | 'forbidden' | 'not_found' | 'error'>('loading');
  const [message, setMessage] = useState('');
  const [reloadKey, setReloadKey] = useState(0);

  useEffect(() => {
    const controller = new AbortController();
    setState('loading');
    Promise.all([
      fetchLeavePosition(recordId, controller.signal),
      fetchLeaveMovements(recordId, controller.signal),
      fetchLeaveTypes(controller.signal),
    ]).then(async ([position, movementList, types]) => {
      if (controller.signal.aborted || position.status === 'aborted') {
        return;
      }
      if (position.status !== 'ok') {
        setRecord(null);
        setMovements(null);
        setState(position.status === 'forbidden' ? 'forbidden' : position.status === 'unauthenticated' ? 'unauthenticated' : position.status === 'not_found' ? 'not_found' : 'error');
        setMessage(position.status === 'forbidden' ? position.message : leaveMessages.error);
        return;
      }
      if (movementList.status !== 'ok') {
        setRecord(null);
        setMovements(null);
        setState(movementList.status === 'forbidden' ? 'forbidden' : movementList.status === 'unauthenticated' ? 'unauthenticated' : 'error');
        setMessage(movementList.status === 'forbidden' ? movementList.message : leaveMessages.error);
        return;
      }
      setRecord(position.record);
      setMovements(movementList.page);
      setCompanyName(types.status === 'ok' ? types.page.company_name : '');
      setTypeName(types.status === 'ok' ? typeLabel(types.page.data, position.record.leave_type_id) : position.record.leave_type_id);
      const profile = await fetchEmployeeProfile(position.record.employee_id, controller.signal);
      if (controller.signal.aborted) {
        return;
      }
      setEmployeeName(profile.status === 'ok' ? `${profile.profile.display_name} (${profile.profile.employee_code})` : position.record.employee_id);
      setState('ready');
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
  if (state !== 'ready' || record === null || movements === null) {
    return <Status title={leaveMessages.error}><button type="button" className="rounded-md border border-stone-300 bg-white px-3 py-1.5 text-sm font-semibold" onClick={() => setReloadKey((value) => value + 1)}>Try again</button></Status>;
  }

  return (
    <>
      <p className="text-sm text-stone-500">
        <Link className="text-indigo-700" to="/session/leave-positions">Leave positions</Link>
        <span> / {employeeName}</span>
      </p>
      <h1 className="mt-1 text-2xl font-semibold">Leave position</h1>
      {companyName !== '' ? <div className="mt-4"><CompanyBanner name={companyName} /></div> : null}
      <p className="mt-1 text-stone-500">{employeeName} · {typeName} · {record.status}</p>
      <p className="mt-2 max-w-3xl text-sm text-stone-600">This record is not usable as an approved leave balance. Confirmation is not recorded.</p>
      <EditPositionForm record={record} onUpdated={setRecord} />
      <MovementSection positionId={record.id} movements={movements} onReload={() => setReloadKey((value) => value + 1)} />
    </>
  );
}

function EditPositionForm({ record, onUpdated }: { record: LeavePositionRecord; onUpdated: (record: LeavePositionRecord) => void }) {
  const [input, setInput] = useState(updateInputFrom(record));
  const [write, setWrite] = useState<LeaveWriteResult<LeavePositionRecord> | null>(null);
  const [saving, setSaving] = useState(false);
  const errors = write?.status === 'invalid' ? Object.values(write.errors).flat() : [];

  useEffect(() => {
    setInput(updateInputFrom(record));
  }, [record]);

  return (
    <form
      className="mt-4 rounded-xl border border-stone-200 bg-white p-4 shadow-sm"
      onSubmit={(event) => {
        event.preventDefault();
        setSaving(true);
        setWrite(null);
        updateLeavePosition(record.id, input).then((next) => {
          setSaving(false);
          setWrite(next);
          if (next.status === 'ok') {
            onUpdated(next.record);
          }
        });
      }}
    >
      <h2 className="text-lg font-semibold">Update recorded position</h2>
      <p className="mt-1 text-sm text-stone-600">Employee and leave type stay on this record. Confirmation fields are not accepted.</p>
      <div className="mt-3 grid gap-3 sm:grid-cols-2">
        <label className="block text-sm font-semibold">Proposed amount
          <input value={input.proposed_amount} onChange={(event) => setInput({ ...input, proposed_amount: event.target.value })} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal" />
        </label>
        <label className="block text-sm font-semibold">Status
          <select value={input.status} onChange={(event) => {
            if (event.target.value === 'proposed' || event.target.value === 'excepted') {
              setInput({ ...input, status: event.target.value });
            }
          }} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal">
            <option value="proposed">proposed</option>
            <option value="excepted">excepted</option>
          </select>
        </label>
        <label className="block text-sm font-semibold">Period label
          <input value={input.period_label} onChange={(event) => setInput({ ...input, period_label: event.target.value })} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal" />
        </label>
        <label className="block text-sm font-semibold">Period start
          <input type="date" value={input.period_start} onChange={(event) => setInput({ ...input, period_start: event.target.value })} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal" />
        </label>
        <label className="block text-sm font-semibold">Period end
          <input type="date" value={input.period_end} onChange={(event) => setInput({ ...input, period_end: event.target.value })} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal" />
        </label>
        <label className="block text-sm font-semibold sm:col-span-2">Source evidence
          <input value={input.source_evidence} onChange={(event) => setInput({ ...input, source_evidence: event.target.value })} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal" />
        </label>
        <label className="block text-sm font-semibold sm:col-span-2">Stated basis
          <input value={input.stated_basis} onChange={(event) => setInput({ ...input, stated_basis: event.target.value })} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal" />
        </label>
      </div>
      <WriteFeedback saving={saving} write={write} errors={errors} />
      <div className="mt-3 flex gap-2">
        <button type="submit" disabled={saving} className="rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">Save</button>
        {positionDirty(record, input) ? (
          <button
            type="button"
            disabled={saving}
            className="rounded-md border border-stone-300 bg-white px-4 py-2 text-sm font-semibold"
            onClick={() => {
              setInput(updateInputFrom(record));
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

function MovementSection({ positionId, movements, onReload }: { positionId: string; movements: LeaveMovementList; onReload: () => void }) {
  return (
    <section className="mt-4">
      <h2 className="text-lg font-semibold">Movements</h2>
      <p className="mt-1 text-sm text-stone-600">A reversal adds a new row. The original movement is not rewritten. These rows are not usable as an approved balance.</p>
      <CreateMovementForm positionId={positionId} movements={movements.data} onCreated={onReload} />
      <div className="mt-4 overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
        {movements.data.length === 0 ? (
          <p className="px-4 py-8 text-stone-600">No movements recorded for this position.</p>
        ) : (
          <table className="w-full border-collapse text-left">
            <thead>
              <tr className="border-b border-stone-200 text-sm text-stone-500">
                <th className="px-4 py-3 font-semibold" scope="col">Recorded at</th>
                <th className="px-4 py-3 font-semibold" scope="col">Direction</th>
                <th className="px-4 py-3 font-semibold" scope="col">Amount</th>
                <th className="px-4 py-3 font-semibold" scope="col">Related reference</th>
                <th className="px-4 py-3 font-semibold" scope="col">Reverses</th>
                <th className="px-4 py-3 font-semibold" scope="col">Actor</th>
                <th className="px-4 py-3 font-semibold" scope="col">Usable as approved balance</th>
              </tr>
            </thead>
            <tbody>
              {movements.data.map((movement) => (
                <tr key={movement.id} className="border-b border-stone-100 last:border-0">
                  <td className="px-4 py-3">{movement.recorded_at ?? '—'}</td>
                  <td className="px-4 py-3">{movement.direction}</td>
                  <td className="px-4 py-3">{movement.amount}</td>
                  <td className="px-4 py-3">{movement.related_reference ?? '—'}</td>
                  <td className="px-4 py-3">{movement.reverses_movement_id ?? '—'}</td>
                  <td className="px-4 py-3">{movement.acting_user_id}</td>
                  <td className="px-4 py-3">Not usable</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </section>
  );
}

function CreateMovementForm({ positionId, movements, onCreated }: { positionId: string; movements: LeaveMovementRecord[]; onCreated: () => void }) {
  const [input, setInput] = useState(emptyMovementInput());
  const [write, setWrite] = useState<LeaveWriteResult<LeaveMovementRecord> | null>(null);
  const [saving, setSaving] = useState(false);
  const errors = write?.status === 'invalid' ? Object.values(write.errors).flat() : [];

  return (
    <form
      className="mt-3 rounded-xl border border-stone-200 bg-white p-4 shadow-sm"
      onSubmit={(event) => {
        event.preventDefault();
        setSaving(true);
        setWrite(null);
        createLeaveMovement(positionId, input).then((next) => {
          setSaving(false);
          setWrite(next);
          if (next.status === 'ok') {
            setInput(emptyMovementInput());
            onCreated();
          }
        });
      }}
    >
      <h3 className="text-base font-semibold">Record a movement</h3>
      <div className="mt-3 grid gap-3 sm:grid-cols-2">
        <label className="block text-sm font-semibold">Amount
          <input value={input.amount} onChange={(event) => setInput({ ...input, amount: event.target.value })} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal" />
        </label>
        <label className="block text-sm font-semibold">Direction
          <input value={input.direction} onChange={(event) => setInput({ ...input, direction: event.target.value })} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal" />
        </label>
        <label className="block text-sm font-semibold">Related reference
          <input value={input.related_reference} onChange={(event) => setInput({ ...input, related_reference: event.target.value })} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal" />
        </label>
        <label className="block text-sm font-semibold">Reverses existing movement
          <select value={input.reverses_movement_id} onChange={(event) => setInput({ ...input, reverses_movement_id: event.target.value })} className="mt-1 w-full rounded-md border border-stone-300 px-3 py-2 font-normal">
            <option value="">None — record a new row</option>
            {movements.map((movement) => (
              <option key={movement.id} value={movement.id}>{movement.direction} · {movement.amount} · {movement.id}</option>
            ))}
          </select>
        </label>
      </div>
      <p className="mt-2 text-sm text-stone-600">Selecting a movement to reverse still posts a new row. The selected movement is not edited.</p>
      <WriteFeedback saving={saving} write={write} errors={errors} />
      <button type="submit" disabled={saving} className="mt-3 rounded-md bg-indigo-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">Save</button>
    </form>
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

function emptyPositionInput(): LeavePositionInput {
  return {
    employee_id: '',
    leave_type_id: '',
    proposed_amount: '',
    period_label: '',
    period_start: '',
    period_end: '',
    source_evidence: '',
    stated_basis: '',
    status: 'proposed',
  };
}

function positionDirty(record: LeavePositionRecord, input: LeavePositionUpdateInput): boolean {
  const current = updateInputFrom(record);
  return (Object.keys(current) as (keyof LeavePositionUpdateInput)[]).some((key) => current[key] !== input[key]);
}

function updateInputFrom(record: LeavePositionRecord): LeavePositionUpdateInput {
  return {
    proposed_amount: record.proposed_amount,
    period_label: record.period_label ?? '',
    period_start: record.period_start ?? '',
    period_end: record.period_end ?? '',
    source_evidence: record.source_evidence,
    stated_basis: record.stated_basis,
    status: record.status,
  };
}

function emptyMovementInput(): LeaveMovementInput {
  return {
    amount: '',
    direction: '',
    related_reference: '',
    reverses_movement_id: '',
  };
}

function periodLabel(record: LeavePositionRecord): string {
  const parts = [record.period_label, record.period_start, record.period_end].filter((value): value is string => value !== null && value !== '');
  return parts.length === 0 ? '—' : parts.join(' · ');
}

function typeLabel(types: LeaveTypeRecord[], id: string): string {
  return types.find((type) => type.id === id)?.name ?? id;
}

function employeeLabel(employees: DirectoryEmployee[], id: string): string {
  const employee = employees.find((row) => row.id === id);
  return employee === undefined ? id : `${employee.display_name} (${employee.employee_code})`;
}

const directoryPageLimit = 50;

type DirectoryLoad =
  | { status: 'ok'; employees: DirectoryEmployee[]; truncated: boolean }
  | { status: 'unauthenticated' }
  | { status: 'forbidden'; message: string }
  | { status: 'error' }
  | { status: 'aborted' };

function DirectoryNotice({ directory, onRetry }: { directory: DirectoryLoad; onRetry: () => void }) {
  if (directory.status === 'ok') {
    return <p className="mt-1 text-sm font-normal text-stone-600">No employees in this company.</p>;
  }
  if (directory.status === 'forbidden') {
    return <p className="mt-1 text-sm font-normal" role="alert">{directory.message}</p>;
  }
  if (directory.status === 'unauthenticated') {
    return <p className="mt-1 text-sm font-normal" role="alert">{directoryMessages.unauthenticated}</p>;
  }
  return (
    <p className="mt-1 text-sm font-normal text-stone-600" role="alert">
      {directoryMessages.error} <button type="button" className="font-semibold text-indigo-700" onClick={onRetry}>Try again</button>
    </p>
  );
}

async function loadEmployees(signal: AbortSignal): Promise<DirectoryLoad> {
  const collected: DirectoryEmployee[] = [];
  let page = 1;
  let lastPage = 1;
  do {
    const result = await fetchEmployeeDirectory('', page, signal);
    if (result.status === 'aborted') {
      return { status: 'aborted' };
    }
    if (result.status === 'unauthenticated' || result.status === 'forbidden') {
      return result;
    }
    if (result.status !== 'ok') {
      return { status: 'error' };
    }
    collected.push(...result.page.data);
    lastPage = result.page.last_page;
    page += 1;
  } while (page <= lastPage && page <= directoryPageLimit && !signal.aborted);
  if (signal.aborted) {
    return { status: 'aborted' };
  }
  return { status: 'ok', employees: collected, truncated: lastPage > directoryPageLimit };
}
