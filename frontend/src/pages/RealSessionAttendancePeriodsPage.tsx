import { useEffect, useState, type FormEvent } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { RealSessionLinks } from '../components/RealSessionLinks';
import { SessionLogoutButton, SessionLogoutError, SignedOutScreen, useSessionLogout } from '../components/SessionLogout';
import {
  attendanceMessages,
  exportPayrollHandoff,
  fetchAttendancePeriod,
  fetchAttendancePeriods,
  fetchAttendanceReport,
  fetchPayrollHandoff,
  fetchPayrollIntake,
  fetchPayrollRun,
  finalizeAttendancePeriod,
  openAttendancePeriod,
  openPayrollPeriod,
  reopenAttendancePeriod,
  startPayrollRun,
  takePayrollAttendance,
  type AttendancePeriodDetail,
  type AttendancePeriodRow,
  type AttendanceReadResult,
  type AttendanceReport,
  type PayrollHandoff,
  type PayrollIntakeIndex,
  type PayrollRunDetail,
} from '../lib/attendanceWorkflowApi';

export function RealSessionAttendancePeriodsPage() {
  const logout = useSessionLogout();

  useEffect(() => {
    document.title = 'Attendance periods — Universal HRMS';
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
          <RealSessionLinks label="Attendance period navigation">
            <a className="font-semibold text-indigo-700" href="/company">Company</a>
            <SessionLogoutButton logout={logout} />
          </RealSessionLinks>
        </div>
      </header>
      <main className="mx-auto w-full max-w-6xl space-y-8 px-4 py-8">
        <SessionLogoutError logout={logout} />
        <p className="text-sm"><Link className="font-semibold text-indigo-700" to="/session/attendance">Back to punches and day results</Link></p>
        <PeriodWorkspace />
      </main>
    </div>
  );
}

function PeriodWorkspace() {
  const [periods, setPeriods] = useState<AttendanceReadResult<{ company_name: string; data: AttendancePeriodRow[] }>>({ status: 'unauthenticated' });
  const [loading, setLoading] = useState(true);
  const [reloadKey, setReloadKey] = useState(0);
  const [detail, setDetail] = useState<AttendancePeriodDetail | null>(null);
  const [handoff, setHandoff] = useState<PayrollHandoff | null>(null);
  const [notice, setNotice] = useState('');

  useEffect(() => {
    const controller = new AbortController();
    fetchAttendancePeriods(controller.signal).then((result) => {
      if (controller.signal.aborted || result.status === 'aborted') {
        return;
      }
      setPeriods(result);
      setLoading(false);
    });
    return () => controller.abort();
  }, [reloadKey]);

  async function open(periodId: string, keepHandoff = false) {
    const result = await fetchAttendancePeriod(periodId);
    if (result.status === 'ok') {
      setDetail(result.body);
      if (!keepHandoff) {
        setHandoff(null);
      }
      return;
    }
    setNotice(result.status === 'forbidden' || result.status === 'error' ? result.message : attendanceMessages.unauthenticated);
  }

  if (loading) {
    return <p className="text-stone-600">Loading attendance periods.</p>;
  }
  if (periods.status !== 'ok') {
    if (periods.status === 'forbidden' && periods.message === 'An active company must be selected.') {
      return (
        <section className="rounded-xl border border-stone-200 bg-white px-4 py-6 shadow-sm">
          <h1 className="text-xl font-semibold">An active company must be selected.</h1>
          <p className="mt-3"><a className="font-semibold text-indigo-700" href="/company">Choose a company</a></p>
        </section>
      );
    }
    const message = periods.status === 'unauthenticated' ? attendanceMessages.unauthenticated : periods.status === 'forbidden' || periods.status === 'error' ? periods.message : attendanceMessages.error;
    return (
      <section className="rounded-xl border border-stone-200 bg-white px-4 py-6 shadow-sm">
        <h1 className="text-xl font-semibold">{message}</h1>
        {periods.status === 'unauthenticated' ? <p className="mt-3"><a className="font-semibold text-indigo-700" href="/login">Open Laravel login</a></p> : null}
      </section>
    );
  }

  return (
    <div className="space-y-8">
      <section className="rounded-xl border border-stone-200 bg-white px-4 py-3 shadow-sm" aria-label="Active company">
        <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">Active company</p>
        <p className="mt-1 text-lg font-semibold text-stone-900">{periods.body.company_name === '' ? '—' : periods.body.company_name}</p>
        <p className="mt-1 text-sm text-stone-600">A period can be finalized only when every expected employee-day has a result, no correction is waiting, and no punch day is unprocessed. A finalized period refuses processing and corrections until it is reopened with a reason. Payroll receives each finalized period once.</p>
      </section>
      {notice !== '' ? <p className="rounded-xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-700">{notice}</p> : null}
      <RangeReport />
      <OpenPeriodForm
        onOpened={(opened) => {
          setNotice('The attendance period was opened.');
          setDetail(opened);
          setReloadKey((value) => value + 1);
        }}
        onNotice={setNotice}
      />
      <section aria-label="Attendance periods">
        <h2 className="text-lg font-semibold">Attendance periods</h2>
        <div className="mt-3 overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
          {periods.body.data.length === 0 ? <p className="px-4 py-8 text-stone-600">No attendance period has been opened for this company.</p> : (
            <table className="w-full border-collapse text-left text-sm">
              <thead>
                <tr className="border-b border-stone-200 text-stone-500">
                  <th className="px-4 py-3 font-semibold" scope="col">From</th>
                  <th className="px-4 py-3 font-semibold" scope="col">To</th>
                  <th className="px-4 py-3 font-semibold" scope="col">State</th>
                  <th className="px-4 py-3 font-semibold" scope="col"><span className="sr-only">Open period</span></th>
                </tr>
              </thead>
              <tbody>
                {periods.body.data.map((period) => (
                  <tr key={period.id} className="border-b border-stone-100">
                    <td className="px-4 py-3">{period.period_start}</td>
                    <td className="px-4 py-3">{period.period_end}</td>
                    <td className="px-4 py-3">{period.period_state}</td>
                    <td className="px-4 py-3"><button className="font-semibold text-indigo-700" type="button" onClick={() => void open(period.id)}>Review period</button></td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </section>
      {detail ? (
        <PeriodDetail
          detail={detail}
          onChanged={(next, message) => {
            setDetail(next);
            setNotice(message);
            setReloadKey((value) => value + 1);
          }}
          onHandoff={(next, message) => {
            setHandoff(next);
            setNotice(message);
            void open(detail.period.id, true);
          }}
          onShowHandoff={async (handoffId) => {
            const result = await fetchPayrollHandoff(handoffId);
            if (result.status === 'ok') {
              setHandoff(result.body);
            }
          }}
          onNotice={setNotice}
        />
      ) : null}
      {handoff ? <HandoffDetail handoff={handoff} /> : null}
      <PayrollIntake reloadKey={reloadKey} />
    </div>
  );
}

function PayrollIntake({ reloadKey }: { reloadKey: number }) {
  const [index, setIndex] = useState<AttendanceReadResult<PayrollIntakeIndex>>({ status: 'unauthenticated' });
  const [refresh, setRefresh] = useState(0);
  const [periodStart, setPeriodStart] = useState('');
  const [periodEnd, setPeriodEnd] = useState('');
  const [label, setLabel] = useState('');
  const [keys, setKeys] = useState<string[]>([]);
  const [handoffId, setHandoffId] = useState('');
  const [run, setRun] = useState<PayrollRunDetail | null>(null);
  const [message, setMessage] = useState('');

  useEffect(() => {
    const controller = new AbortController();
    fetchPayrollIntake(controller.signal).then((result) => {
      if (!controller.signal.aborted && result.status !== 'aborted') {
        setIndex(result);
      }
    });
    return () => controller.abort();
  }, [reloadKey, refresh]);

  if (index.status !== 'ok') {
    return (
      <section className="rounded-xl border border-stone-200 bg-white px-4 py-4 shadow-sm" aria-label="Payroll intake">
        <h2 className="text-lg font-semibold">Payroll intake</h2>
        <p className="mt-1 text-sm text-stone-600">{index.status === 'forbidden' || index.status === 'error' ? index.message : attendanceMessages.unauthenticated}</p>
      </section>
    );
  }

  function failed(result: { status: string; message?: string }): string {
    return result.status === 'unauthenticated' ? attendanceMessages.unauthenticated : result.message ?? attendanceMessages.saveError;
  }

  async function onOpen(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const result = await openPayrollPeriod(periodStart, periodEnd, label);
    setMessage(result.status === 'ok' ? 'The payroll period was opened.' : failed(result));
    setRefresh((value) => value + 1);
  }

  async function onStart(payrollPeriodId: string) {
    const result = await startPayrollRun(payrollPeriodId, keys);
    if (result.status === 'ok') {
      setRun(result.body);
      setMessage(`Payroll run ${result.body.run_sequence} started with ${result.body.declared_inputs.length} declared attendance inputs.`);
    } else {
      setMessage(failed(result));
    }
    setRefresh((value) => value + 1);
  }

  async function onTake(payrollRunId: string) {
    const result = await takePayrollAttendance(payrollRunId, handoffId);
    if (result.status === 'ok') {
      setRun(result.body);
      setMessage(result.body.created ? 'Payroll copied the attendance handoff. Counts reconciled with the handoff totals.' : 'This run already took this handoff. Nothing was copied again.');
    } else {
      setMessage(failed(result));
    }
    setRefresh((value) => value + 1);
  }

  async function onShow(payrollRunId: string) {
    const result = await fetchPayrollRun(payrollRunId);
    if (result.status === 'ok') {
      setRun(result.body);
    }
  }

  return (
    <section className="rounded-xl border border-stone-200 bg-white px-4 py-4 shadow-sm" aria-label="Payroll intake">
      <h2 className="text-lg font-semibold">Payroll intake</h2>
      <p className="mt-1 text-sm text-stone-600">Payroll copies declared attendance counts from one exported handoff whose attendance period covers the same dates. No money is calculated here. Salary structure, heads, leave and overtime pay are not configured.</p>
      {message !== '' ? <p className="mt-3 text-sm text-stone-700">{message}</p> : null}
      <form className="mt-3 grid gap-3 md:grid-cols-4" onSubmit={onOpen} aria-label="Open a payroll period">
        <label className="text-sm"><span className="font-semibold text-stone-600">Payroll start</span><input className="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" type="date" value={periodStart} onChange={(event) => setPeriodStart(event.target.value)} required /></label>
        <label className="text-sm"><span className="font-semibold text-stone-600">Payroll end</span><input className="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" type="date" value={periodEnd} onChange={(event) => setPeriodEnd(event.target.value)} required /></label>
        <label className="text-sm"><span className="font-semibold text-stone-600">Payroll label</span><input className="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" value={label} onChange={(event) => setLabel(event.target.value)} required /></label>
        <button className="self-end rounded-lg bg-stone-900 px-4 py-2 text-sm font-semibold text-white" type="submit">Open payroll period</button>
      </form>
      <fieldset className="mt-4 text-sm">
        <legend className="font-semibold text-stone-600">Attendance inputs this run declares</legend>
        <div className="mt-1 flex flex-wrap gap-4">
          {Object.entries(index.body.input_keys).map(([key, text]) => (
            <label key={key} className="flex items-center gap-1">
              <input type="checkbox" checked={keys.includes(key)} onChange={(event) => setKeys((current) => (event.target.checked ? [...current, key] : current.filter((item) => item !== key)))} />
              {text}
            </label>
          ))}
        </div>
      </fieldset>
      <label className="mt-3 block text-sm">
        <span className="font-semibold text-stone-600">Exported attendance handoff</span>
        <select className="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" value={handoffId} onChange={(event) => setHandoffId(event.target.value)}>
          <option value="">Choose a handoff</option>
          {index.body.exported_handoffs.map((item) => (
            <option key={item.id} value={item.id}>{item.id} — {item.employee_count} employees, {item.line_count} days</option>
          ))}
        </select>
      </label>
      <ul className="mt-4 space-y-3 text-sm">
        {index.body.data.map((period) => (
          <li key={period.id}>
            <p className="font-semibold">{period.label}: {period.period_start} to {period.period_end}</p>
            <button className="mt-1 font-semibold text-indigo-700" type="button" onClick={() => void onStart(period.id)}>Start payroll run</button>
            <ul className="mt-1 list-disc pl-5 text-stone-600">
              {period.runs.map((item) => (
                <li key={item.id}>
                  Run {item.run_sequence} ({item.run_state}, {item.attendance_usage}){item.attendance_payroll_handoff_id ? ` took ${item.attendance_payroll_handoff_id}` : ''}{' '}
                  <button className="font-semibold text-indigo-700" type="button" disabled={handoffId === ''} onClick={() => void onTake(item.id)}>Take attendance</button>{' '}
                  <button className="font-semibold text-indigo-700" type="button" onClick={() => void onShow(item.id)}>Show inputs</button>
                </li>
              ))}
            </ul>
          </li>
        ))}
      </ul>
      {run ? (
        <div className="mt-4 text-sm" aria-label="Payroll run inputs">
          <h3 className="font-semibold">Run {run.run_sequence} copied inputs ({run.run_state})</h3>
          <table className="mt-2 w-full border-collapse text-left">
            <thead>
              <tr className="border-b border-stone-200 text-stone-500">
                <th className="px-2 py-2 font-semibold" scope="col">Employee</th>
                <th className="px-2 py-2 font-semibold" scope="col">Input</th>
                <th className="px-2 py-2 font-semibold" scope="col">Copied value</th>
              </tr>
            </thead>
            <tbody>
              {run.attendance_inputs.map((input) => (
                <tr key={`${input.employee_id}-${input.input_key}`} className="border-b border-stone-100">
                  <td className="px-2 py-2">{input.employee_id}</td>
                  <td className="px-2 py-2">{index.body.input_keys[input.input_key] ?? input.input_key}</td>
                  <td className="px-2 py-2">{input.copied_value}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      ) : null}
    </section>
  );
}

function RangeReport() {
  const [params] = useSearchParams();
  const [from, setFrom] = useState(params.get('from') ?? '');
  const [to, setTo] = useState(params.get('to') ?? '');
  const [report, setReport] = useState<AttendanceReport | null>(null);
  const [message, setMessage] = useState('');

  useEffect(() => {
    const linkedFrom = params.get('from');
    const linkedTo = params.get('to');
    if (linkedFrom && linkedTo) {
      void build(linkedFrom, linkedTo);
    }
  }, [params]);

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    await build(from, to);
  }

  async function build(rangeFrom: string, rangeTo: string) {
    const result = await fetchAttendanceReport(rangeFrom, rangeTo);
    if (result.status === 'ok') {
      setReport(result.body);
      setMessage('');
      return;
    }
    setReport(null);
    setMessage(result.status === 'forbidden' || result.status === 'error' ? result.message : result.status === 'unauthenticated' ? attendanceMessages.unauthenticated : attendanceMessages.error);
  }

  return (
    <section className="rounded-xl border border-stone-200 bg-white px-4 py-4 shadow-sm" aria-label="Attendance report">
      <form onSubmit={onSubmit}>
        <h2 className="text-lg font-semibold">Attendance summary and exceptions</h2>
        <div className="mt-3 grid gap-3 md:grid-cols-2">
          <label className="text-sm">
            <span className="font-semibold text-stone-600">From</span>
            <input className="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" type="date" value={from} onChange={(event) => setFrom(event.target.value)} required />
          </label>
          <label className="text-sm">
            <span className="font-semibold text-stone-600">To</span>
            <input className="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" type="date" value={to} onChange={(event) => setTo(event.target.value)} required />
          </label>
        </div>
        <button className="mt-4 rounded-lg bg-stone-900 px-4 py-2 text-sm font-semibold text-white" type="submit">Build report</button>
      </form>
      {message !== '' ? <p className="mt-3 text-sm text-stone-700">{message}</p> : null}
      {report ? <ReportBody report={report} /> : null}
    </section>
  );
}

function ReportBody({ report }: { report: AttendanceReport }) {
  return (
    <div className="mt-4 space-y-4 text-sm">
      <p>
        Expected {report.totals.expected} · processed {report.totals.processed} · present {report.totals.present} · absent or incomplete {report.totals.incomplete} · non-working {report.totals.non_working} · other {report.totals.other} · late seconds {report.totals.late_seconds} · {report.reconciled ? 'buckets reconcile' : 'buckets do not reconcile'}
      </p>
      <p>Raw punches {report.raw_event_count} · selected as in or out {report.selected_event_count}</p>
      <table className="w-full border-collapse text-left">
        <thead>
          <tr className="border-b border-stone-200 text-stone-500">
            <th className="px-2 py-2 font-semibold" scope="col">Employee</th>
            <th className="px-2 py-2 font-semibold" scope="col">Expected</th>
            <th className="px-2 py-2 font-semibold" scope="col">Processed</th>
            <th className="px-2 py-2 font-semibold" scope="col">Present</th>
            <th className="px-2 py-2 font-semibold" scope="col">Absent or incomplete</th>
            <th className="px-2 py-2 font-semibold" scope="col">Non-working</th>
            <th className="px-2 py-2 font-semibold" scope="col">Other</th>
            <th className="px-2 py-2 font-semibold" scope="col">Late seconds</th>
          </tr>
        </thead>
        <tbody>
          {report.employees.map((row) => (
            <tr key={row.employee_id} className="border-b border-stone-100">
              <td className="px-2 py-2">{row.employee_code ?? row.employee_id} {row.display_name ?? ''}</td>
              <td className="px-2 py-2">{row.expected}</td>
              <td className="px-2 py-2">{row.processed}</td>
              <td className="px-2 py-2">{row.present}</td>
              <td className="px-2 py-2">{row.incomplete}</td>
              <td className="px-2 py-2">{row.non_working}</td>
              <td className="px-2 py-2">{row.other}</td>
              <td className="px-2 py-2">{row.late_seconds}</td>
            </tr>
          ))}
        </tbody>
      </table>
      <ExceptionList title="Absent or incomplete days" items={report.exceptions.map((item) => `${item.employee_code ?? item.employee_id} ${item.work_date}: ${item.status_code ?? '—'}${item.incomplete ? `, missing ${item.missing.replace('_', ' ')}` : ''}`)} />
      <ExceptionList title="Expected days without a result" items={report.missing_results.map((item) => `${item.employee_code ?? item.employee_id} ${item.work_date}`)} />
      <ExceptionList title="Corrections waiting for a decision" items={report.pending_corrections.map((item) => `${item.employee_id} ${item.work_date}`)} />
      <ExceptionList title="Missing-punch requests waiting for review" items={report.pending_missing_punch_requests.map((item) => `${item.employee_id} ${item.work_date}: ${item.punch_side}`)} />
      <ExceptionList title="Approved missing-punch times now meeting a recorded punch" items={report.missing_punch_conflicts.map((item) => `${item.employee_id} ${item.work_date}: ${item.punch_sides.join(', ')}`)} />
      <ExceptionList title="Punch days without a result" items={report.unprocessed_punches.map((item) => `${item.employee_id} ${item.work_date}: ${item.event_count} punches`)} />
    </div>
  );
}

function ExceptionList({ title, items }: { title: string; items: string[] }) {
  return (
    <div>
      <h3 className="font-semibold">{title} ({items.length})</h3>
      {items.length > 0 ? (
        <ul className="mt-1 max-h-40 list-disc overflow-y-auto pl-5 text-stone-600">
          {items.map((item) => <li key={item}>{item}</li>)}
        </ul>
      ) : null}
    </div>
  );
}

function OpenPeriodForm({ onOpened, onNotice }: { onOpened: (detail: AttendancePeriodDetail) => void; onNotice: (message: string) => void }) {
  const [periodStart, setPeriodStart] = useState('');
  const [periodEnd, setPeriodEnd] = useState('');

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const result = await openAttendancePeriod(periodStart, periodEnd);
    if (result.status === 'ok') {
      onOpened(result.body);
      return;
    }
    onNotice(result.status === 'unauthenticated' ? attendanceMessages.unauthenticated : result.message);
  }

  return (
    <form className="rounded-xl border border-stone-200 bg-white px-4 py-4 shadow-sm" onSubmit={onSubmit} aria-label="Open an attendance period">
      <h2 className="text-lg font-semibold">Open an attendance period</h2>
      <div className="mt-3 grid gap-3 md:grid-cols-2">
        <label className="text-sm">
          <span className="font-semibold text-stone-600">Period start</span>
          <input className="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" type="date" value={periodStart} onChange={(event) => setPeriodStart(event.target.value)} required />
        </label>
        <label className="text-sm">
          <span className="font-semibold text-stone-600">Period end</span>
          <input className="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" type="date" value={periodEnd} onChange={(event) => setPeriodEnd(event.target.value)} required />
        </label>
      </div>
      <button className="mt-4 rounded-lg bg-stone-900 px-4 py-2 text-sm font-semibold text-white" type="submit">Open period</button>
    </form>
  );
}

function PeriodDetail({ detail, onChanged, onHandoff, onShowHandoff, onNotice }: {
  detail: AttendancePeriodDetail;
  onChanged: (detail: AttendancePeriodDetail, message: string) => void;
  onHandoff: (handoff: PayrollHandoff, message: string) => void;
  onShowHandoff: (handoffId: string) => Promise<void>;
  onNotice: (message: string) => void;
}) {
  const [reason, setReason] = useState('');
  const [pending, setPending] = useState(false);
  const periodId = detail.period.id;

  async function finalize() {
    setPending(true);
    const result = await finalizeAttendancePeriod(periodId);
    setPending(false);
    if (result.status === 'ok') {
      onChanged(result.body, 'The period was finalized. Processing and corrections are now refused for these dates.');
      return;
    }
    onNotice(result.status === 'unauthenticated' ? attendanceMessages.unauthenticated : `The period was not finalized. ${result.message}`);
  }

  async function reopen(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setPending(true);
    const result = await reopenAttendancePeriod(periodId, reason);
    setPending(false);
    if (result.status === 'ok') {
      setReason('');
      onChanged(result.body, 'The period was reopened. Any exported payroll handoff was superseded.');
      return;
    }
    onNotice(result.status === 'unauthenticated' ? attendanceMessages.unauthenticated : result.message);
  }

  async function handOff() {
    setPending(true);
    const result = await exportPayrollHandoff(periodId);
    setPending(false);
    if (result.status === 'ok') {
      onHandoff(result.body, result.body.created ? 'The finalized period was handed to payroll.' : 'This period was already handed to payroll. The existing handoff is shown.');
      return;
    }
    onNotice(result.status === 'unauthenticated' ? attendanceMessages.unauthenticated : result.message);
  }

  return (
    <section className="rounded-xl border border-stone-200 bg-white px-4 py-4 shadow-sm" aria-label="Attendance period detail">
      <h2 className="text-lg font-semibold">Period {detail.period.period_start} to {detail.period.period_end} ({detail.period.period_state})</h2>
      <div className="mt-3 flex flex-wrap gap-3">
        <button className="rounded-lg bg-stone-900 px-4 py-2 text-sm font-semibold text-white" type="button" disabled={pending || detail.period.period_state !== 'open'} onClick={() => void finalize()}>Finalize period</button>
        <button className="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold" type="button" disabled={pending || detail.period.period_state !== 'finalized'} onClick={() => void handOff()}>Hand to payroll</button>
      </div>
      <form className="mt-3 flex flex-wrap items-end gap-3" onSubmit={reopen} aria-label="Reopen period">
        <label className="text-sm">
          <span className="font-semibold text-stone-600">Reopen reason</span>
          <input className="mt-1 w-72 rounded-lg border border-stone-300 px-3 py-2" value={reason} onChange={(event) => setReason(event.target.value)} required />
        </label>
        <button className="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold" type="submit" disabled={pending || detail.period.period_state !== 'finalized'}>Reopen period</button>
      </form>
      <h3 className="mt-4 font-semibold">Period history</h3>
      <ul className="mt-1 list-disc pl-5 text-sm text-stone-600">
        {detail.events.map((event, index) => (
          <li key={`${event.event_kind}-${index}`}>{event.event_kind} at {event.occurred_at ?? '—'}{event.reason ? `: ${event.reason}` : ''}</li>
        ))}
      </ul>
      <h3 className="mt-4 font-semibold">Payroll handoffs</h3>
      {detail.handoffs.length === 0 ? <p className="mt-1 text-sm text-stone-600">None yet.</p> : (
        <ul className="mt-1 space-y-1 text-sm">
          {detail.handoffs.map((handoff) => (
            <li key={handoff.id}>
              {handoff.handoff_state}: {handoff.employee_count} employees, {handoff.line_count} days{' '}
              <button className="font-semibold text-indigo-700" type="button" onClick={() => void onShowHandoff(handoff.id)}>Show lines</button>
            </li>
          ))}
        </ul>
      )}
      <ReportBody report={detail.report} />
    </section>
  );
}

function HandoffDetail({ handoff }: { handoff: PayrollHandoff }) {
  return (
    <section className="rounded-xl border border-stone-200 bg-white px-4 py-4 shadow-sm" aria-label="Payroll handoff">
      <h2 className="text-lg font-semibold">Payroll handoff ({handoff.handoff_state})</h2>
      <p className="mt-1 text-sm text-stone-600">
        Employees {handoff.employee_count} · days {handoff.line_count} · present {handoff.present_count} · absent or incomplete {handoff.incomplete_count} · non-working {handoff.non_working_count} · other {handoff.other_status_count} · late seconds {handoff.late_seconds_total}
      </p>
      <table className="mt-3 w-full border-collapse text-left text-sm">
        <thead>
          <tr className="border-b border-stone-200 text-stone-500">
            <th className="px-2 py-2 font-semibold" scope="col">Employee</th>
            <th className="px-2 py-2 font-semibold" scope="col">Work date</th>
            <th className="px-2 py-2 font-semibold" scope="col">Class</th>
            <th className="px-2 py-2 font-semibold" scope="col">Status</th>
            <th className="px-2 py-2 font-semibold" scope="col">Late seconds</th>
            <th className="px-2 py-2 font-semibold" scope="col">Result version</th>
          </tr>
        </thead>
        <tbody>
          {handoff.lines.map((line) => (
            <tr key={`${line.employee_id}-${line.work_date}`} className="border-b border-stone-100">
              <td className="px-2 py-2">{line.employee_id}</td>
              <td className="px-2 py-2">{line.work_date}</td>
              <td className="px-2 py-2">{line.day_class}</td>
              <td className="px-2 py-2">{line.status_code ?? '—'}</td>
              <td className="px-2 py-2">{line.late_seconds ?? '—'}</td>
              <td className="px-2 py-2">{line.result_version_id}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </section>
  );
}
