import { useEffect, useState, type FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { RealSessionLinks } from '../components/RealSessionLinks';
import { SessionLogoutButton, SessionLogoutError, SignedOutScreen, useSessionLogout } from '../components/SessionLogout';
import {
  attendanceMessages,
  calculateAttendanceDay,
  decideAttendanceCorrection,
  decideMissingPunch,
  submitMissingPunch,
  fetchAttendanceDay,
  fetchAttendanceDays,
  fetchAttendancePunches,
  importPunchLog,
  submitAttendanceCorrection,
  type AttendanceDayDetail,
  type AttendanceDaySummary,
  type AttendancePunch,
  type AttendanceReadResult,
  type PunchLogReport,
  type PunchLogRow,
} from '../lib/attendanceWorkflowApi';

export function RealSessionAttendancePage() {
  const logout = useSessionLogout();

  useEffect(() => {
    document.title = 'Attendance — Universal HRMS';
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
          <RealSessionLinks label="Attendance navigation">
            <Link className="font-semibold text-indigo-700" to="/session/attendance/periods">Periods and reports</Link>
            <a className="font-semibold text-indigo-700" href="/company">Company</a>
            <SessionLogoutButton logout={logout} />
          </RealSessionLinks>
        </div>
      </header>
      <main className="mx-auto w-full max-w-6xl px-4 py-8">
        <SessionLogoutError logout={logout} />
        <AttendanceWorkspace />
      </main>
    </div>
  );
}

function AttendanceWorkspace() {
  const [punches, setPunches] = useState<AttendanceReadResult<{ company_name: string; data: AttendancePunch[] }>>({ status: 'unauthenticated' });
  const [days, setDays] = useState<AttendanceReadResult<{ company_name: string; data: AttendanceDaySummary[] }>>({ status: 'unauthenticated' });
  const [detail, setDetail] = useState<AttendanceDayDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [reloadKey, setReloadKey] = useState(0);
  const [notice, setNotice] = useState('');

  useEffect(() => {
    const controller = new AbortController();
    Promise.all([fetchAttendancePunches(controller.signal), fetchAttendanceDays(controller.signal)]).then(([punchResult, dayResult]) => {
      if (controller.signal.aborted || punchResult.status === 'aborted' || dayResult.status === 'aborted') {
        return;
      }
      setPunches(punchResult);
      setDays(dayResult);
      setLoading(false);
    });
    return () => controller.abort();
  }, [reloadKey]);

  if (loading) {
    return <p className="text-stone-600">Loading attendance records.</p>;
  }

  const blocked = punches.status === 'unauthenticated' || punches.status === 'forbidden' || punches.status === 'error'
    ? punches
    : days.status === 'unauthenticated' || days.status === 'forbidden' || days.status === 'error'
      ? days
      : null;
  if (blocked) {
    return <Blocked result={blocked} />;
  }
  if (punches.status !== 'ok' || days.status !== 'ok') {
    return <p className="text-stone-600">{attendanceMessages.error}</p>;
  }

  return (
    <div className="space-y-8">
      <section className="rounded-xl border border-stone-200 bg-white px-4 py-3 shadow-sm" aria-label="Active company">
        <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">Active company</p>
        <p className="mt-1 text-lg font-semibold text-stone-900">{days.body.company_name === '' ? '—' : days.body.company_name}</p>
        <p className="mt-1 text-sm text-stone-600">Raw punches stay unchanged. A repeat stores a new version only when the result changes. Versions are listed by their predecessor chain. A legacy next-day value of 2 keeps the shift end on the work date and does not open a next-day punch window.</p>
      </section>
      {notice !== '' ? <p className="rounded-xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-700">{notice}</p> : null}
      <ProcessForm
        onProcessed={async (employeeId, workDate, versionAdded) => {
          setNotice(processedNotice(versionAdded));
          setReloadKey((value) => value + 1);
          const next = await fetchAttendanceDay(employeeId, workDate);
          setDetail(next.status === 'ok' ? next.body : null);
        }}
        onNotice={setNotice}
      />
      <PunchLogUpload onImported={() => setReloadKey((value) => value + 1)} />
      <PunchTable punches={punches.body.data} />
      <DayTable
        days={days.body.data}
        onOpen={async (day) => {
          const next = await fetchAttendanceDay(day.employee_id, day.work_date);
          if (next.status === 'ok') {
            setDetail(next.body);
            setNotice('');
          } else if (next.status === 'forbidden' || next.status === 'error') {
            setNotice(next.message);
          }
        }}
        onProcess={async (day) => {
          const result = await calculateAttendanceDay(day.employee_id, day.work_date);
          if (result.status !== 'ok') {
            setNotice(result.status === 'invalid' || result.status === 'forbidden' || result.status === 'error' ? result.message : attendanceMessages.unauthenticated);
            return;
          }
          setNotice(processedNotice(result.body.version_added !== false));
          setDetail(result.body);
          setReloadKey((value) => value + 1);
        }}
      />
      {detail ? (
        <DayDetail
          detail={detail}
          onChanged={async () => {
            const next = await fetchAttendanceDay(detail.employee_id, detail.work_date);
            if (next.status === 'ok') {
              setDetail(next.body);
            }
            setReloadKey((value) => value + 1);
          }}
          onNotice={setNotice}
        />
      ) : null}
    </div>
  );
}

function Blocked({ result }: { result: { status: string; message?: string } }) {
  if (result.status === 'unauthenticated') {
    return (
      <section className="rounded-xl border border-stone-200 bg-white px-4 py-6 shadow-sm">
        <h1 className="text-xl font-semibold">{attendanceMessages.unauthenticated}</h1>
        <p className="mt-3">
          <a className="font-semibold text-indigo-700" href="/login">Open Laravel login</a>
        </p>
      </section>
    );
  }
  if (result.status === 'forbidden' && result.message === 'An active company must be selected.') {
    return (
      <section className="rounded-xl border border-stone-200 bg-white px-4 py-6 shadow-sm">
        <h1 className="text-xl font-semibold">An active company must be selected.</h1>
        <p className="mt-3">
          <a className="font-semibold text-indigo-700" href="/company">Choose a company</a>
        </p>
      </section>
    );
  }
  if (result.status === 'forbidden') {
    return (
      <section className="rounded-xl border border-stone-200 bg-white px-4 py-6 shadow-sm">
        <h1 className="text-xl font-semibold">{attendanceMessages.forbidden}</h1>
        {result.message && result.message !== attendanceMessages.forbidden ? <p className="mt-3">{result.message}</p> : null}
      </section>
    );
  }
  return (
    <section className="rounded-xl border border-stone-200 bg-white px-4 py-6 shadow-sm">
      <h1 className="text-xl font-semibold">{attendanceMessages.error}</h1>
    </section>
  );
}

function processedNotice(versionAdded: boolean): string {
  return versionAdded
    ? 'The day was processed. Earlier versions and raw punches were left in place.'
    : 'This day already matches the current version. No new version was stored.';
}

function ProcessForm({ onProcessed, onNotice }: { onProcessed: (employeeId: string, workDate: string, versionAdded: boolean) => Promise<void>; onNotice: (message: string) => void }) {
  const [employeeId, setEmployeeId] = useState('');
  const [workDate, setWorkDate] = useState('');
  const [pending, setPending] = useState(false);

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setPending(true);
    const result = await calculateAttendanceDay(employeeId.trim(), workDate);
    setPending(false);
    if (result.status === 'ok') {
      await onProcessed(result.body.employee_id, result.body.work_date, result.body.version_added !== false);
      return;
    }
    onNotice(result.status === 'invalid' || result.status === 'forbidden' || result.status === 'error' ? result.message : attendanceMessages.unauthenticated);
  }

  return (
    <form className="rounded-xl border border-stone-200 bg-white px-4 py-4 shadow-sm" onSubmit={onSubmit} aria-label="Process an attendance day">
      <h2 className="text-lg font-semibold">Process one day</h2>
      <div className="mt-3 grid gap-3 md:grid-cols-2">
        <label className="text-sm">
          <span className="font-semibold text-stone-600">Employee id</span>
          <input className="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" value={employeeId} onChange={(event) => setEmployeeId(event.target.value)} required />
        </label>
        <label className="text-sm">
          <span className="font-semibold text-stone-600">Work date</span>
          <input className="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" type="date" value={workDate} onChange={(event) => setWorkDate(event.target.value)} required />
        </label>
      </div>
      <button className="mt-4 rounded-lg bg-stone-900 px-4 py-2 text-sm font-semibold text-white" type="submit" disabled={pending}>
        {pending ? 'Processing' : 'Run processing'}
      </button>
    </form>
  );
}

function parsePunchLog(text: string): PunchLogRow[] {
  return text
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter((line) => line !== '')
    .map((line) => line.split(',').map((cell) => cell.trim()))
    .filter((cells) => cells[0] !== 'employee_code')
    .map((cells) => ({
      employee_code: cells[0] ?? '',
      identity_value: cells[1] ?? '',
      event_at: cells[2] ?? '',
      source_event_id: cells[3] && cells[3] !== '' ? cells[3] : null,
    }));
}

function PunchLogUpload({ onImported }: { onImported: () => void }) {
  const [sourceName, setSourceName] = useState('');
  const [text, setText] = useState('');
  const [report, setReport] = useState<PunchLogReport | null>(null);
  const [message, setMessage] = useState('');
  const [pending, setPending] = useState(false);

  async function run(mode: 'preview' | 'commit') {
    setPending(true);
    const result = await importPunchLog(sourceName.trim(), mode, parsePunchLog(text));
    setPending(false);
    if (result.status !== 'ok') {
      setReport(null);
      setMessage(result.status === 'unauthenticated' ? attendanceMessages.unauthenticated : result.message);
      return;
    }
    setReport(result.body);
    if (result.body.committed) {
      setMessage(`${result.body.accepted_count} punch events were stored.`);
      onImported();
    } else if (result.body.rejected_count > 0 || result.body.duplicate_count > 0) {
      setMessage('Nothing was stored. Fix or remove the rejected and duplicate rows, then import again.');
    } else {
      setMessage('Preview only. Nothing was stored.');
    }
  }

  return (
    <section className="rounded-xl border border-stone-200 bg-white px-4 py-4 shadow-sm" aria-label="Upload punch log">
      <h2 className="text-lg font-semibold">Upload punch log</h2>
      <p className="mt-1 text-sm text-stone-600">One row per event: employee_code, identity_value, event_at with UTC offset, source_event_id. The whole file is stored only when every row is accepted.</p>
      <label className="mt-3 block text-sm">
        <span className="font-semibold text-stone-600">Source name</span>
        <input className="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" value={sourceName} onChange={(event) => setSourceName(event.target.value)} />
      </label>
      <label className="mt-3 block text-sm">
        <span className="font-semibold text-stone-600">Punch log rows</span>
        <textarea className="mt-1 h-28 w-full rounded-lg border border-stone-300 px-3 py-2 font-mono text-xs" value={text} onChange={(event) => setText(event.target.value)} />
      </label>
      <div className="mt-3 flex flex-wrap gap-3">
        <button className="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold" type="button" disabled={pending} onClick={() => void run('preview')}>Preview</button>
        <button className="rounded-lg bg-stone-900 px-4 py-2 text-sm font-semibold text-white" type="button" disabled={pending} onClick={() => void run('commit')}>Import</button>
      </div>
      {message !== '' ? <p className="mt-3 text-sm text-stone-700">{message}</p> : null}
      {report ? (
        <ul className="mt-3 space-y-1 text-sm" aria-label="Punch log rows checked">
          {report.rows.map((row) => (
            <li key={row.line}>
              Line {row.line}: {row.outcome} — {row.employee_code} {row.event_at}{row.message ? ` (${row.message})` : ''}
            </li>
          ))}
        </ul>
      ) : null}
    </section>
  );
}

function PunchTable({ punches }: { punches: AttendancePunch[] }) {
  return (
    <section aria-label="Raw punches">
      <h2 className="text-lg font-semibold">Raw punches</h2>
      <div className="mt-3 overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
        {punches.length === 0 ? <p className="px-4 py-8 text-stone-600">No punch events are stored for this company.</p> : (
          <table className="w-full border-collapse text-left text-sm">
            <thead>
              <tr className="border-b border-stone-200 text-stone-500">
                <th className="px-4 py-3 font-semibold" scope="col">Event time</th>
                <th className="px-4 py-3 font-semibold" scope="col">Identity</th>
                <th className="px-4 py-3 font-semibold" scope="col">Source</th>
                <th className="px-4 py-3 font-semibold" scope="col">Source event</th>
              </tr>
            </thead>
            <tbody>
              {punches.map((punch) => (
                <tr key={punch.id} className="border-b border-stone-100">
                  <td className="px-4 py-3">{punch.event_at ?? '—'}</td>
                  <td className="px-4 py-3">{punch.identity_value ?? '—'}</td>
                  <td className="px-4 py-3">{punch.source_name ?? '—'}</td>
                  <td className="px-4 py-3">{punch.source_event_id ?? '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </section>
  );
}

function DayTable({ days, onOpen, onProcess }: { days: AttendanceDaySummary[]; onOpen: (day: AttendanceDaySummary) => Promise<void>; onProcess: (day: AttendanceDaySummary) => Promise<void> }) {
  return (
    <section aria-label="Day results">
      <h2 className="text-lg font-semibold">Day results</h2>
      <div className="mt-3 overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
        {days.length === 0 ? <p className="px-4 py-8 text-stone-600">No day results have been processed for this company.</p> : (
          <table className="w-full border-collapse text-left text-sm">
            <thead>
              <tr className="border-b border-stone-200 text-stone-500">
                <th className="px-4 py-3 font-semibold" scope="col">Employee</th>
                <th className="px-4 py-3 font-semibold" scope="col">Work date</th>
                <th className="px-4 py-3 font-semibold" scope="col">Status</th>
                <th className="px-4 py-3 font-semibold" scope="col">Late seconds</th>
                <th className="px-4 py-3 font-semibold" scope="col">Versions</th>
                <th className="px-4 py-3 font-semibold" scope="col"><span className="sr-only">Versions detail</span></th>
              </tr>
            </thead>
            <tbody>
              {days.map((day) => (
                <tr key={`${day.employee_id}-${day.work_date}`} className="border-b border-stone-100">
                  <td className="px-4 py-3">
                    <p>{day.employee_code ?? '—'} {day.display_name ?? ''}</p>
                    <p className="mt-1 text-xs text-stone-500">{day.employee_id}</p>
                  </td>
                  <td className="px-4 py-3">{day.work_date}</td>
                  <td className="px-4 py-3">{day.incomplete ? `${day.status_code ?? '—'} incomplete` : (day.status_code ?? '—')}</td>
                  <td className="px-4 py-3">{day.late_seconds ?? '—'}</td>
                  <td className="px-4 py-3">{day.version_count}</td>
                  <td className="px-4 py-3">
                    <button className="font-semibold text-indigo-700" type="button" onClick={() => void onOpen(day)}>View versions</button>
                    <button className="ml-4 font-semibold text-indigo-700" type="button" onClick={() => void onProcess(day)}>Process this day again</button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </section>
  );
}

function DayDetail({ detail, onChanged, onNotice }: { detail: AttendanceDayDetail; onChanged: () => Promise<void>; onNotice: (message: string) => void }) {
  return (
    <section className="rounded-xl border border-stone-200 bg-white px-4 py-4 shadow-sm" aria-label="Day result versions">
      <h2 className="text-lg font-semibold">Versions for {detail.work_date}</h2>
      <ul className="mt-3 divide-y divide-stone-100 text-sm">
        {detail.versions.map((version) => (
          <li key={version.id} className="py-3">
            <p className="font-semibold">{version.generation_kind} {version.id === detail.current_version_id ? '(current)' : ''}</p>
            <p className="mt-1 text-stone-600">Status {version.status_code ?? '—'}, late {version.late_seconds ?? '—'}, incomplete {version.incomplete ? 'yes' : 'no'}</p>
            <p className="mt-1 text-stone-600">In {version.selected_in_event_id ?? '—'} · Out {version.selected_out_event_id ?? '—'}</p>
            {version.override_in_at || version.override_out_at ? (
              <p className="mt-1 text-stone-600">
                Approved missing-punch time: {version.override_in_at ? `in ${version.override_in_at}` : ''}{version.override_in_at && version.override_out_at ? ' · ' : ''}{version.override_out_at ? `out ${version.override_out_at}` : ''}
              </p>
            ) : null}
            <p className="mt-1 text-stone-600">Previous version {version.previous_version_id ?? '—'}</p>
          </li>
        ))}
      </ul>
      <MissingPunchForm detail={detail} onChanged={onChanged} onNotice={onNotice} />
      <CorrectionForm detail={detail} onChanged={onChanged} onNotice={onNotice} />
    </section>
  );
}

function MissingPunchForm({ detail, onChanged, onNotice }: { detail: AttendanceDayDetail; onChanged: () => Promise<void>; onNotice: (message: string) => void }) {
  const [side, setSide] = useState<'in' | 'out'>('out');
  const [requestedAt, setRequestedAt] = useState('');
  const [reason, setReason] = useState('');
  const [pending, setPending] = useState(false);
  const submitted = detail.missing_punch_requests.find((request) => request.request_state === 'submitted');

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setPending(true);
    const result = await submitMissingPunch(detail.employee_id, detail.work_date, side, requestedAt.trim(), reason);
    setPending(false);
    if (result.status === 'ok') {
      onNotice('The missing-punch request was submitted for review. No punch was recorded.');
      await onChanged();
      return;
    }
    onNotice(result.status === 'unauthenticated' ? attendanceMessages.unauthenticated : result.message);
  }

  async function decide(decision: 'approved' | 'rejected') {
    if (!submitted) {
      return;
    }
    setPending(true);
    const result = await decideMissingPunch(submitted.id, decision);
    setPending(false);
    if (result.status === 'ok') {
      onNotice(decision === 'approved'
        ? 'The missing-punch time was approved. A new version was calculated and the raw punches were left unchanged.'
        : 'The missing-punch request was rejected. The current version stayed in place.');
      await onChanged();
      return;
    }
    onNotice(result.status === 'unauthenticated' ? attendanceMessages.unauthenticated : result.message);
  }

  return (
    <form className="mt-4 border-t border-stone-100 pt-4" onSubmit={onSubmit} aria-label="Missing punch request">
      <h3 className="font-semibold">Missing punch request</h3>
      <p className="mt-1 text-sm text-stone-600">Only a side with no recorded punch can be requested. Give the time with its UTC offset, for example 2026-09-13T21:00:00+06:00. A blank time is not filled from the shift. A reviewer with the review grant decides.</p>
      <div className="mt-3 grid gap-3 md:grid-cols-3">
        <label className="text-sm">
          <span className="font-semibold text-stone-600">Side</span>
          <select className="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" value={side} onChange={(event) => setSide(event.target.value === 'in' ? 'in' : 'out')}>
            <option value="in">in</option>
            <option value="out">out</option>
          </select>
        </label>
        <label className="text-sm">
          <span className="font-semibold text-stone-600">Requested time</span>
          <input className="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" value={requestedAt} onChange={(event) => setRequestedAt(event.target.value)} required />
        </label>
        <label className="text-sm">
          <span className="font-semibold text-stone-600">Missing punch reason</span>
          <input className="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" value={reason} onChange={(event) => setReason(event.target.value)} required />
        </label>
      </div>
      <div className="mt-4 flex flex-wrap gap-3">
        <button className="rounded-lg bg-stone-900 px-4 py-2 text-sm font-semibold text-white" type="submit" disabled={pending}>Submit missing punch</button>
        <button className="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold" type="button" disabled={pending || !submitted} onClick={() => void decide('approved')}>Approve missing punch</button>
        <button className="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold" type="button" disabled={pending || !submitted} onClick={() => void decide('rejected')}>Reject missing punch</button>
      </div>
      {detail.missing_punch_requests.length > 0 ? (
        <ul className="mt-4 space-y-2 text-sm text-stone-600" aria-label="Missing punch requests">
          {detail.missing_punch_requests.map((request) => (
            <li key={request.id}>{request.request_state}: {request.punch_side} {request.requested_event_at ?? '—'} — {request.reason}</li>
          ))}
        </ul>
      ) : null}
    </form>
  );
}

function CorrectionForm({ detail, onChanged, onNotice }: { detail: AttendanceDayDetail; onChanged: () => Promise<void>; onNotice: (message: string) => void }) {
  const [statusCode, setStatusCode] = useState('');
  const [reason, setReason] = useState('');
  const [pending, setPending] = useState(false);
  const submitted = detail.corrections.find((correction) => correction.correction_state === 'submitted');

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setPending(true);
    const result = await submitAttendanceCorrection(detail.employee_id, detail.work_date, statusCode, reason);
    setPending(false);
    if (result.status === 'ok') {
      onNotice('The correction was submitted.');
      await onChanged();
      return;
    }
    onNotice(result.status === 'invalid' || result.status === 'forbidden' || result.status === 'error' ? result.message : attendanceMessages.unauthenticated);
  }

  async function decide(decision: 'approved' | 'rejected') {
    if (!submitted) {
      return;
    }
    setPending(true);
    const result = await decideAttendanceCorrection(submitted.id, decision);
    setPending(false);
    if (result.status === 'ok') {
      onNotice(decision === 'approved' ? 'The correction was approved as a new version.' : 'The correction was rejected. The current version stayed in place.');
      await onChanged();
      return;
    }
    onNotice(result.status === 'invalid' || result.status === 'forbidden' || result.status === 'error' ? result.message : attendanceMessages.unauthenticated);
  }

  return (
    <form className="mt-4 border-t border-stone-100 pt-4" onSubmit={onSubmit} aria-label="Status correction">
      <h3 className="font-semibold">Status correction</h3>
      <p className="mt-1 text-sm text-stone-600">A company that has not allowed status corrections refuses this form. Approval writes a new version and leaves the raw punches unchanged.</p>
      <div className="mt-3 grid gap-3 md:grid-cols-2">
        <label className="text-sm">
          <span className="font-semibold text-stone-600">Requested status</span>
          <input className="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" value={statusCode} onChange={(event) => setStatusCode(event.target.value)} required />
        </label>
        <label className="text-sm">
          <span className="font-semibold text-stone-600">Reason</span>
          <input className="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" value={reason} onChange={(event) => setReason(event.target.value)} required />
        </label>
      </div>
      <div className="mt-4 flex flex-wrap gap-3">
        <button className="rounded-lg bg-stone-900 px-4 py-2 text-sm font-semibold text-white" type="submit" disabled={pending}>Submit correction</button>
        <button className="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold" type="button" disabled={pending || !submitted} onClick={() => void decide('approved')}>Approve</button>
        <button className="rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold" type="button" disabled={pending || !submitted} onClick={() => void decide('rejected')}>Reject</button>
      </div>
      {detail.corrections.length > 0 ? (
        <ul className="mt-4 space-y-2 text-sm text-stone-600">
          {detail.corrections.map((correction) => (
            <li key={correction.id}>{correction.correction_state}: {correction.reason}</li>
          ))}
        </ul>
      ) : null}
    </form>
  );
}
