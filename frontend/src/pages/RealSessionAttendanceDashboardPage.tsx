import { useEffect, useState, type FormEvent, type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { RealSessionLinks } from '../components/RealSessionLinks';
import { SessionLogoutButton, SessionLogoutError, SignedOutScreen, useSessionLogout } from '../components/SessionLogout';
import {
  attendanceMessages,
  fetchAttendanceDashboard,
  fetchDeviceLogPreview,
  type AttendanceDashboard,
  type AttendanceReadResult,
  type DeviceLogPreview,
} from '../lib/attendanceWorkflowApi';

export function RealSessionAttendanceDashboardPage() {
  const logout = useSessionLogout();

  useEffect(() => {
    document.title = 'Attendance dashboard — Universal HRMS';
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
          <RealSessionLinks label="Attendance dashboard navigation">
            <a className="font-semibold text-indigo-700" href="/company">Company</a>
            <SessionLogoutButton logout={logout} />
          </RealSessionLinks>
        </div>
      </header>
      <main className="mx-auto w-full max-w-6xl space-y-8 px-4 py-8">
        <SessionLogoutError logout={logout} />
        <DashboardWorkspace />
      </main>
    </div>
  );
}

function DashboardWorkspace() {
  const [date, setDate] = useState<string | null>(null);
  const [draft, setDraft] = useState('');
  const [result, setResult] = useState<AttendanceReadResult<AttendanceDashboard> | null>(null);

  useEffect(() => {
    const controller = new AbortController();
    fetchAttendanceDashboard(date, controller.signal).then((next) => {
      if (!controller.signal.aborted && next.status !== 'aborted') {
        setResult(next);
        if (next.status === 'ok' && next.body.work_date) {
          setDraft(next.body.work_date);
        }
      }
    });
    return () => controller.abort();
  }, [date]);

  if (result === null) {
    return <p className="text-stone-600">Loading the attendance dashboard.</p>;
  }
  if (result.status !== 'ok') {
    return <Blocked result={result} />;
  }
  const dashboard = result.body;

  function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setDate(draft === '' ? null : draft);
  }

  return (
    <div className="space-y-8">
      <section className="rounded-xl border border-stone-200 bg-white px-4 py-3 shadow-sm" aria-label="Active company">
        <p className="text-xs font-semibold uppercase tracking-wide text-stone-500">Active company</p>
        <p className="mt-1 text-lg font-semibold text-stone-900">{dashboard.company_name === '' ? '—' : dashboard.company_name}</p>
        <p className="mt-1 text-sm text-stone-600">
          Raw punches are recorded events. Day results are calculated versions. Approved corrections and missing-punch times are counted separately. An expected employee-day without a result is not counted as absent.
        </p>
        <form className="mt-3 flex flex-wrap items-end gap-3" onSubmit={onSubmit} aria-label="Choose the work date">
          <label className="text-sm">
            <span className="font-semibold text-stone-600">Work date</span>
            <input className="mt-1 rounded-lg border border-stone-300 px-3 py-2" type="date" value={draft} onChange={(event) => setDraft(event.target.value)} />
          </label>
          <button className="rounded-lg bg-stone-900 px-4 py-2 text-sm font-semibold text-white" type="submit">Show date</button>
          <Link className="text-sm font-semibold text-indigo-700" to="/session/attendance">Punches and day results</Link>
          <Link className="text-sm font-semibold text-indigo-700" to="/session/attendance/periods">Periods and reports</Link>
          <Link className="text-sm font-semibold text-indigo-700" to="/session/attendance/identities">Card identities</Link>
        </form>
      </section>
      {dashboard.policy_configured && dashboard.day && dashboard.queue ? (
        <ConfiguredDashboard dashboard={dashboard} />
      ) : (
        <section className="rounded-xl border border-stone-200 bg-white px-4 py-6 shadow-sm" aria-label="Attendance not configured">
          <h1 className="text-xl font-semibold">This company has no attendance policy.</h1>
          <p className="mt-2 text-sm text-stone-600">Day figures need a policy timezone, shifts and an assignment rule. Nothing is calculated until they exist.</p>
        </section>
      )}
      <SourcePanel key={dashboard.work_date ?? ''} dashboard={dashboard} />
      <IngestionPanel dashboard={dashboard} />
    </div>
  );
}

function ConfiguredDashboard({ dashboard }: { dashboard: AttendanceDashboard }) {
  const day = dashboard.day!;
  const queue = dashboard.queue!;
  const reportLink = `/session/attendance/periods?from=${queue.from}&to=${queue.to}`;
  const dayLink = `/session/attendance/periods?from=${dashboard.work_date}&to=${dashboard.work_date}`;

  return (
    <>
      <section aria-label="Work date summary">
        <h2 className="text-lg font-semibold">{dashboard.work_date} <span className="text-sm font-normal text-stone-500">({dashboard.timezone})</span></h2>
        <div className="mt-3 grid gap-4 md:grid-cols-2">
          <Panel title="Recorded" note="Expected days come from the shift rule. Punches are stored events.">
            <Metric label="Expected employee-days" value={day.expected_employee_days} />
            <Metric label="Employees with punches" value={day.employees_with_punches} />
            <Metric label="Raw punch events" value={day.raw_punch_events} />
          </Panel>
          <Panel title="Calculated" note="Current day-result versions for this date.">
            <Metric label="Results calculated" value={day.results_calculated} />
            <Metric label="Present" value={day.present} />
            <Metric label="Calculated absence" value={day.calculated_absent} hint="No usable punch inside the shift window, or outside the policy limits" />
            <Metric label="Incomplete attendance" value={day.incomplete} hint="Only one side of the day has a punch" />
            <Metric label="Non-working day" value={day.non_working} />
            <Metric label="Other status" value={day.other_status} />
            <Metric label="Late arrivals" value={day.late} hint={`${day.late_not_calculated} results have no late figure`} />
            <Metric label="Early departures" value={day.early_departure} hint={`${day.early_not_calculated} results have no early figure`} />
          </Panel>
          <Panel title="Not yet a result" note="These employee-days are not counted as absent.">
            <Metric label="Expected days without a result" value={day.expected_without_result} />
            <Metric label="No punch received" value={day.no_punch_received} />
            <Metric label="Punches awaiting processing" value={day.awaiting_processing} />
            <p className="mt-2 text-sm"><Link className="font-semibold text-indigo-700" to={dayLink}>Open the day report</Link></p>
          </Panel>
          <Panel title="Approved changes" note="Approved items in the current versions. Raw punches are unchanged.">
            <Metric label="Approved status corrections" value={day.approved_corrections} />
            <Metric label="Approved missing-punch times" value={day.approved_missing_punch_times} />
          </Panel>
        </div>
      </section>
      <section aria-label="Work queue">
        <h2 className="text-lg font-semibold">Work queue {queue.from} to {queue.to}</h2>
        <p className="mt-1 text-sm text-stone-600">
          {queue.basis === 'attendance_period' ? 'The attendance period covering this date, up to this date.' : 'No attendance period covers this date, so the calendar month up to this date is shown.'}
        </p>
        <div className="mt-3 grid gap-4 md:grid-cols-2">
          <Panel title="Waiting for a decision">
            <Metric label="Missing-punch requests pending review" value={queue.pending_missing_punch_requests} />
            <Metric label="Status corrections pending" value={queue.pending_corrections} />
            <Metric label="Approved times meeting a recorded punch" value={queue.missing_punch_conflicts} />
          </Panel>
          <Panel title="Waiting for processing">
            <Metric label="Expected days without a result" value={queue.expected_without_result} />
            <Metric label="Punch days without a result" value={queue.awaiting_processing} />
            <p className="mt-2 text-sm"><Link className="font-semibold text-indigo-700" to={reportLink}>Open the range report</Link></p>
          </Panel>
        </div>
      </section>
      <section className="rounded-xl border border-stone-200 bg-white px-4 py-4 shadow-sm" aria-label="Attendance period status">
        <h2 className="text-lg font-semibold">Attendance period</h2>
        {dashboard.period ? (
          <>
            <p className="mt-1 text-sm">{dashboard.period.period_start} to {dashboard.period.period_end}: {dashboard.period.period_state}</p>
            {dashboard.period.finalization_blockers.length > 0 ? (
              <ul className="mt-2 list-disc pl-5 text-sm text-stone-700" aria-label="Finalization blockers">
                {dashboard.period.finalization_blockers.map((blocker) => <li key={blocker}>{blocker}</li>)}
              </ul>
            ) : <p className="mt-2 text-sm text-stone-600">{dashboard.period.period_state === 'open' ? 'Nothing blocks finalization.' : 'The period is finalized.'}</p>}
          </>
        ) : <p className="mt-1 text-sm text-stone-600">No attendance period covers this date.</p>}
        <p className="mt-2 text-sm"><Link className="font-semibold text-indigo-700" to="/session/attendance/periods">Manage periods</Link></p>
      </section>
    </>
  );
}

function SourcePanel({ dashboard }: { dashboard: AttendanceDashboard }) {
  const source = dashboard.source;
  const [preview, setPreview] = useState<AttendanceReadResult<DeviceLogPreview> | null>(null);
  const date = dashboard.work_date;

  async function onPreview() {
    if (!date) {
      return;
    }
    setPreview(await fetchDeviceLogPreview(date, date));
  }

  return (
    <section className="rounded-xl border border-stone-200 bg-white px-4 py-4 shadow-sm" aria-label="Device log source">
      <h2 className="text-lg font-semibold">Device log source</h2>
      <p className="mt-1 text-sm text-stone-600">
        Legacy shared table <code>{source.table}</code>, read only. It has no company column, so a card counts for this company only through a punch identity this company holds. Nothing is imported from this screen.
      </p>
      {source.status === 'not_configured' || !source.configured ? (
        <p className="mt-2 text-sm">No source connection is configured. Unmapped identities and import exceptions cannot be listed until one is.</p>
      ) : null}
      <p className="mt-2 text-sm"><Link className="font-semibold text-indigo-700" to="/session/attendance/identities">Register or review card identities</Link></p>
      {source.status === 'unavailable' || source.status === 'refused' ? <p className="mt-2 text-sm" role="alert">{source.message}</p> : null}
      {source.status === 'read' && source.counts ? (
        <dl className="mt-3 grid gap-2 text-sm md:grid-cols-4">
          {Object.entries(source.counts).map(([outcome, count]) => (
            <div key={outcome}><dt className="text-stone-500">{outcome.replace('_', ' ')}</dt><dd className="font-semibold">{count}</dd></div>
          ))}
        </dl>
      ) : null}
      {source.configured && date ? (
        <button className="mt-3 rounded-lg border border-stone-300 px-4 py-2 text-sm font-semibold" type="button" onClick={() => void onPreview()}>Preview source rows for {date}</button>
      ) : null}
      {preview?.status === 'ok' ? <PreviewTable preview={preview.body} /> : null}
      {preview && preview.status !== 'ok' && preview.status !== 'aborted' ? <p className="mt-3 text-sm" role="alert">{preview.status === 'unauthenticated' ? attendanceMessages.unauthenticated : preview.message}</p> : null}
    </section>
  );
}

function PreviewTable({ preview }: { preview: DeviceLogPreview }) {
  return (
    <div className="mt-4 space-y-2 text-sm">
      <p>{preview.time_basis} {preview.row_count} rows{preview.truncated ? `, truncated at ${preview.max_rows}` : ''}. Writes are disabled.</p>
      <ul className="list-disc pl-5 text-stone-600" aria-label="Decisions blocking import">
        {preview.blocking_decisions.map((decision) => <li key={decision}>{decision}</li>)}
      </ul>
      <table className="w-full border-collapse text-left">
        <thead>
          <tr className="border-b border-stone-200 text-stone-500">
            <th className="px-2 py-2 font-semibold" scope="col">Source row</th>
            <th className="px-2 py-2 font-semibold" scope="col">Source time</th>
            <th className="px-2 py-2 font-semibold" scope="col">Card</th>
            <th className="px-2 py-2 font-semibold" scope="col">Employee</th>
            <th className="px-2 py-2 font-semibold" scope="col">Outcome</th>
          </tr>
        </thead>
        <tbody>
          {(preview.rows ?? []).map((row) => (
            <tr key={row.source_event_id} className="border-b border-stone-100">
              <td className="px-2 py-2">{row.source_event_id}</td>
              <td className="px-2 py-2">{row.source_wall_time ?? '—'}</td>
              <td className="px-2 py-2">{row.identity_value ?? '—'}</td>
              <td className="px-2 py-2">{row.employee_code ?? '—'}</td>
              <td className="px-2 py-2">{row.outcome}{row.message ? ` — ${row.message}` : ''}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function IngestionPanel({ dashboard }: { dashboard: AttendanceDashboard }) {
  return (
    <section className="rounded-xl border border-stone-200 bg-white px-4 py-4 shadow-sm" aria-label="Recorded punch sources">
      <h2 className="text-lg font-semibold">Recorded punch sources</h2>
      <p className="mt-1 text-sm text-stone-600">Latest stored punch per named source in this company. A refused import stores nothing, so failed imports are not listed.</p>
      {dashboard.ingestion.length === 0 ? <p className="mt-2 text-sm">No punch has been recorded for this company.</p> : (
        <ul className="mt-2 space-y-1 text-sm">
          {dashboard.ingestion.map((row) => (
            <li key={row.source_name}>{row.source_name}: {row.events} events, last stored {row.latest_ingested_at ?? '—'}, latest event {row.latest_event_at ?? '—'}</li>
          ))}
        </ul>
      )}
    </section>
  );
}

function Panel({ title, note, children }: { title: string; note?: string; children: ReactNode }) {
  return (
    <div className="rounded-xl border border-stone-200 bg-white px-4 py-4 shadow-sm">
      <h3 className="font-semibold">{title}</h3>
      {note ? <p className="mt-1 text-xs text-stone-500">{note}</p> : null}
      <dl className="mt-2 space-y-1 text-sm">{children}</dl>
    </div>
  );
}

function Metric({ label, value, hint }: { label: string; value: number; hint?: string }) {
  return (
    <div className="flex items-baseline justify-between gap-3">
      <dt className="text-stone-600">{label}{hint ? <span className="block text-xs text-stone-400">{hint}</span> : null}</dt>
      <dd className="font-semibold">{value}</dd>
    </div>
  );
}

function Blocked({ result }: { result: { status: string; message?: string } }) {
  if (result.status === 'unauthenticated') {
    return (
      <section className="rounded-xl border border-stone-200 bg-white px-4 py-6 shadow-sm">
        <h1 className="text-xl font-semibold">{attendanceMessages.unauthenticated}</h1>
        <p className="mt-3"><a className="font-semibold text-indigo-700" href="/login">Open Laravel login</a></p>
      </section>
    );
  }
  if (result.status === 'forbidden' && result.message === 'An active company must be selected.') {
    return (
      <section className="rounded-xl border border-stone-200 bg-white px-4 py-6 shadow-sm">
        <h1 className="text-xl font-semibold">An active company must be selected.</h1>
        <p className="mt-3"><a className="font-semibold text-indigo-700" href="/company">Choose a company</a></p>
      </section>
    );
  }
  return (
    <section className="rounded-xl border border-stone-200 bg-white px-4 py-6 shadow-sm">
      <h1 className="text-xl font-semibold">{result.message ?? attendanceMessages.error}</h1>
    </section>
  );
}
