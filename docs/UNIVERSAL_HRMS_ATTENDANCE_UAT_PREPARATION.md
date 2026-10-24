# Universal HRMS — Attendance UAT Preparation

Date: 2026-10-19. Status: the six migrations were applied to `proedge_new_hrms_uat` on 2026-10-19 at 22:47 (UTC+6) following section 6; see `UNIVERSAL_HRMS_ATTENDANCE_UAT_MIGRATION_EXECUTION_REPORT.md`. UAT is **not ready for operational use** (section 7). Writes during preparation went only to `proedge_new_hrms_test` and to the dropped rehearsal copy `proedge_new_hrms_uat_rehearsal`. `proedge_new_hrms` and `proedge_new_hrms_uat` were read only. No Git command was run.

## 1. Decision record

| # | Decision | Status | Behavior now |
|---|---|---|---|
| 1 | VTL uses the calendar window rule | Approved for `proedge_new_hrms_test` | VTL policy: `punch_selection = margin_first_and_last`, `working_day_code = W`, `non_working_day_code = H`, `non_working_status_code = H`, `non_working_attended_status_code = HP`. Grace, late basis, status codes, `correction_fields = ''` and `missing_punch_requests = false` are unchanged. Applied by `php artisan hrms:vtl-window-policy --apply` with one audit event. No day was recalculated. |
| 2 | Blank missing-punch time | Disallowed | A request needs an explicit time with a UTC offset. A blank time is not filled from the shift start or end, even though legacy VTL did that. |
| 3 | Recorded punch arrives after an approved missing-punch time | Flag, do not choose | Recalculation of that day is refused. The report lists the day under `missing_punch_conflicts`, and the period cannot be finalized while it is listed. Which time counts remains an open decision. |
| 4 | Requester and reviewer separation | Enforced for the initial rollout | The user who submitted a missing-punch request cannot decide it, even when that user holds `review_missing_punch`. The refused attempt is recorded as `attendance_missing_punch_self_review_refused`. Submission, approval and rejection are audited with the acting user. |
| 5 | Historical legacy approval import | Deferred | The VTL dump holds 987 September `attendance_approval_file` rows, including 77696 and 77723. None are imported. No punch, time or roster row is created from them. |
| 6 | Payroll intake until `payroll_results` exists | Approved as temporary | See section 2. No salary structure, salary head or amount is created. |
| 7 | Duplicate punch batches | Fail-closed | A punch-log batch with any duplicate or rejected row stores nothing. The duplicate key and treatment stay undecided (PUNCH-CONF-002). |
| 8 | Leave-position engine in the UAT rollout | Approved: schema only | Migration `2026_10_18_010000_create_leave_position_recording_engine` is included. This does not activate leave processing, leave balances or leave-position writers. |

Still open: which time counts in a decision-3 conflict; whether a recorded punch may ever be replaced; who holds each grant in UAT; the duplicate key and treatment; the salary structure that `payroll_results` needs.

## 2. Temporary payroll intake and `payroll_results`

The target schema (`UNIVERSAL_HRMS_TARGET_DATABASE_SCHEMA_SPECIFICATION.md` §4.26–4.28) places copied attendance inputs in `payroll_result_inputs`, a child of `payroll_results`. A `payroll_results` row requires `employee_structure_assignment_id` and `net_pay`. Neither salary structures nor salary heads exist, so no honest `payroll_results` row can be written.

Until they exist, Payroll stores the copy on the run:

| Table | Role | Future relationship |
|---|---|---|
| `payroll_periods` | Period identity and bounds | As specified |
| `payroll_runs` | Versioned run; `attendance_payroll_handoff_id` and `attendance_taken_at` record the one handoff taken | As specified, plus the two intake columns |
| `payroll_run_declared_inputs` | Input keys the run declares; immutable | As specified |
| `payroll_run_attendance_inputs` | Per run, employee and key: `copied_value` and the source handoff; immutable | Temporary. When a run calculates `payroll_results`, each row becomes a `payroll_result_inputs` row for that employee's result with the same `input_key` and `copied_value`, citing the handoff. The intake row stays as the record of what was copied |

Allowed keys: `attendance.day_count`, `attendance.present_days`, `attendance.absent_or_incomplete_days`, `attendance.non_working_days`, `attendance.other_status_days`, `attendance.late_seconds`. These are counts, not money.

The intake refuses a handoff unless it is exported, belongs to the company, comes from a finalized attendance period with exactly the payroll period's dates, and its employee, day, class and late-second totals reconcile with its lines. A repeat take of the same handoff is a no-op. A different handoff is refused. Reopening attendance supersedes an in-progress run that took the handoff. A finalized run would block the reopen; finalization is not implemented.

## 3. Unrelated formatting change

`app/Http/Controllers/EmploymentEpisodeController.php` was reformatted by Pint during an earlier milestone. Compared with the committed copy on `main`, the method bodies and route behavior are unchanged. Pint replaced the inline `\App\Models\Employee` and `\App\Employees\EmployeeRegistry` names with `use` imports and normalized brace and empty-body formatting. The employee lifecycle and management suites, which cover the episode routes, pass. The file differs from `HEAD` only in formatting; restoring the committed bytes is optional.

## 4. Migration scope

Target: `proedge_new_hrms_uat` on `127.0.0.1:5432`, PostgreSQL 18.6, owner `postgres`, encoding `UTF8`, libc locale `English_United States.1252` (collate and ctype), no database-level settings, no database ACL, extension `plpgsql` only, schema `public` only. The same instance hosts `proedge_new_hrms`, `proedge_new_hrms_test` and `proedge_new_hrms_restore_verify_20261017`; no production database is on it.

All six pending migrations are in scope, in this order:

| Order | Migration | Creates | Depends on |
|---|---|---|---|
| 1 | `2026_10_18_010000_create_leave_position_recording_engine` | `leave_position_writers`, `leave_types`, `leave_positions`, `leave_position_movements` | Baseline `companies`, `users`, `employees` |
| 2 | `2026_10_18_020000_create_immutable_punch_recording_engine` | `punch_identities`, `punch_identity_overlaps`, `punch_events`, immutability triggers | Baseline tables |
| 3 | `2026_10_18_030000_create_punch_recording_writers` | `punch_recording_writers` | Baseline tables |
| 4 | `2026_10_19_071658_create_attendance_day_processing` | Policies, shifts, shift versions, candidates, day-result versions and pointers, corrections, correction values, triggers | 2 (`punch_events`) |
| 5 | `2026_10_19_114219_create_roster_source_days_and_attendance_periods` | Roster day windows, periods, period events, handoffs, handoff lines, triggers; alters policies and versions | 4 |
| 6 | `2026_10_19_124619_create_attendance_action_grants_missing_punch_and_payroll_intake` | Action grants, missing-punch requests, payroll periods, runs, declared inputs, attendance inputs, triggers; alters policies and versions | 4, 5 |

Nothing depends on migration 1. `php artisan migrate` applies all six in this order as one new batch. Each migration runs in its own transaction and every `down()` throws, so there is no rollback; recovery is drop and restore (section 6.9). `config/database.php` sets the session time zone to UTC for every connection, including UAT.

## 5. Evidence

### 5.1 Rehearsal (verified on a restored copy, 2026-10-19)

Result: passed. Actual UAT was read only by `pg_dump`.

| Item | Value |
|---|---|
| Target | `proedge_new_hrms_uat_rehearsal`, created for the rehearsal and dropped afterwards |
| Server | `127.0.0.1:5432`, PostgreSQL 18.6, system identifier 7694707497988598612, data directory `G:\postgre_data` |
| Source dump | `pg_dump --format=custom` of `proedge_new_hrms_uat`, 31,571 bytes, 74 TOC entries, sha256 `ebe6b2333731ff55bdfbb02d0c0112d3602c3ebcb4d1a7a3542e4e860089b938`. Deleted afterwards because it holds UAT user rows |
| Restore | `createdb --template=template0`, `pg_restore --exit-on-error`. All 12 tables matched UAT by row count and content hash |
| Apply | Process-scoped `DB_DATABASE`, configuration check, `php artisan migrate --no-interaction`. All six ran in order as batch 2, about 0.3 s. Repeated on a second fresh restore with the same result |
| Baseline before | 12 tables, 9 migration records. companies 2, users 3, company_memberships 4, employees 2, employment_episodes 3, departments 1, audit_events 3; branches, designations, location_nodes, organization_nodes 0 |
| Data effect | `migrations` 9 → 15 rows (batch 1: 9, batch 2: 6). The other 11 baseline tables were identical by content hash |
| Schema effect | 12 → 39 tables. 27 new tables present and empty. 16 immutability triggers. Columns, constraints, indexes, triggers and functions equal `proedge_new_hrms_test` exactly: 383, 562, 91, 16, 8 |
| Recovery | Drop and restore into a fresh database returned the copy exactly to the UAT baseline |

Recovery limitations found in the rehearsal:

- `pg_restore --clean --if-exists` over a migrated database fails: baseline keys such as `companies_pkey` and `users_pkey` cannot be dropped while new tables reference them.
- Without `--single-transaction` that failure is partial. In the rehearsal it dropped 28 baseline foreign keys before it stopped; data stayed intact but the schema was damaged.
- With `--single-transaction` it fails atomically and changes nothing, so it is not a recovery path either.
- Every `down()` throws and each migration commits on its own, so a failure partway leaves earlier migrations applied.
- Recovery discards anything written to UAT after the backup.

### 5.2 Read-only checks against actual UAT (2026-10-19, after the rehearsal)

- The runtime guard in section 6.5 passed against `proedge_new_hrms_uat`: Laravel resolved `pgsql 127.0.0.1:5432/proedge_new_hrms_uat`, 12 tables, and exactly the six migrations above pending, in order.
- The same guard refused when the process pointed at `proedge_new_hrms_test`.
- UAT content digest was identical before and after the rehearsal. UAT had no open sessions.
- The section 6.2–6.4 and 6.7 psql commands ran read-only against UAT and returned: 0 sessions; `postgres|UTF8|c|English_United States.1252|English_United States.1252`; `migrations` batch `1|9`; 0 triggers; and a 12-line fingerprint whose hashes equal the rehearsal baseline, for example `companies|2|7cf6bfc1bb37b77e4913555c706d50db` and `users|3|0c49a55278edededd7e461d5cdaa2c40`.
- `pg_dump`, the full archive read and the recovery commands were not run against UAT in this step.

### 5.3 Must be repeated against actual UAT immediately before migrating

| Check | Why it cannot rely on the rehearsal |
|---|---|
| Writers stopped and no sessions open | Changes with time |
| Fresh dump, checksum, listing, full archive read | The rehearsal dump was deleted and UAT may have changed |
| Database properties (owner, encoding, locale) | Needed to recreate exactly during recovery |
| Per-table row counts and content hashes | Baseline for the after-migration comparison and for recovery |
| Runtime guard | Proves the process targets UAT at the moment of execution |
| Post-migration verification | Only proves the actual migration |

Not rehearsed: recreating the database with the explicit `--lc-collate` and `--lc-ctype` values in section 6.9. The rehearsal copy used the instance default. Section 6.9 includes a check that the recreated database matches the recorded properties.

## 6. Migration authorization checklist and runbook

Run from `D:\Project -All\Proedge HRMS\next-hrms` in PowerShell. Do not edit `.env`. Do not put the database password on a command line; use a `pgpass.conf` entry or the interactive prompt for PostgreSQL tools. Store every artifact outside the repository, for example `D:\Project -All\Proedge HRMS\uat-migration\<yyyy-mm-dd>\`, with access limited to the operator; the dump contains user rows and password hashes.

### 6.1 Written approval

Do not start without a signed record that states all of:

- [ ] Target database `proedge_new_hrms_uat` on `127.0.0.1:5432`, and nothing else.
- [ ] The migration window: start, end and time zone.
- [ ] All six migrations in section 4, including the leave-position engine as schema only.
- [ ] The operator and the approver, by name.
- [ ] Acceptance that recovery is drop and restore and loses any UAT write made after the backup.

### 6.2 Writers stopped

- [ ] Stop every application process, queue worker, scheduler and local server that could connect to UAT.
- [ ] Confirm no other session is open:

```powershell
psql -h 127.0.0.1 -p 5432 -U postgres -d postgres -X -At -c "SELECT count(*) FROM pg_stat_activity WHERE datname = 'proedge_new_hrms_uat';"
```

Expected `0`. Any other value: stop (section 6.8).

### 6.3 Fresh backup

```powershell
$dir = 'D:\Project -All\Proedge HRMS\uat-migration\<yyyy-mm-dd>'
New-Item -ItemType Directory -Force -Path $dir | Out-Null
psql -h 127.0.0.1 -p 5432 -U postgres -d postgres -X -At -c "SELECT datname, pg_get_userbyid(datdba), pg_encoding_to_char(encoding), datlocprovider, datcollate, datctype FROM pg_database WHERE datname = 'proedge_new_hrms_uat';" | Set-Content "$dir\uat_database_properties.txt"
pg_dump -h 127.0.0.1 -p 5432 -U postgres --format=custom --file="$dir\proedge_new_hrms_uat_pre_attendance.dump" proedge_new_hrms_uat
Get-FileHash -Algorithm SHA256 "$dir\proedge_new_hrms_uat_pre_attendance.dump" | Format-List | Set-Content "$dir\dump_sha256.txt"
pg_restore --list "$dir\proedge_new_hrms_uat_pre_attendance.dump" | Set-Content "$dir\dump_listing.txt"
pg_restore --file="$dir\dump_full_read.sql" "$dir\proedge_new_hrms_uat_pre_attendance.dump"
```

- [ ] Each command exits `0`.
- [ ] `uat_database_properties.txt` shows owner `postgres`, `UTF8`, provider `c`, collate and ctype `English_United States.1252`. Any difference: record it and use the recorded values in section 6.9.
- [ ] The listing has a `TABLE DATA` entry for each of the 12 tables: `audit_events`, `branches`, `companies`, `company_memberships`, `departments`, `designations`, `employees`, `employment_episodes`, `location_nodes`, `migrations`, `organization_nodes`, `users`.
- [ ] The full read produced `dump_full_read.sql` without error, which proves every data block decompresses. Delete that file after the check; keep the dump, checksum and listing.

### 6.4 Pre-migration fingerprint

Save this outside the repository as `$dir\fingerprint.sql`:

```sql
SELECT format(
  'SELECT %L AS table_name, count(*) AS row_count, md5(coalesce(string_agg(t::text, %L ORDER BY t::text), %L)) AS content_hash FROM public.%I t',
  table_name, '|', '', table_name)
FROM information_schema.tables
WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
ORDER BY table_name
\gexec
```

```powershell
$env:PGOPTIONS = '-c default_transaction_read_only=on'
psql -h 127.0.0.1 -p 5432 -U postgres -d proedge_new_hrms_uat -X -At -v ON_ERROR_STOP=1 -f "$dir\fingerprint.sql" -o "$dir\fingerprint_before.txt"
Remove-Item Env:PGOPTIONS
```

- [ ] 12 lines. Row counts match the approved baseline: audit_events 3, branches 0, companies 2, company_memberships 4, departments 1, designations 0, employees 2, employment_episodes 3, location_nodes 0, migrations 9, organization_nodes 0, users 3. A different count is not automatically wrong, but the approver must accept it before continuing.

### 6.5 Runtime guard

Refuses unless Laravel resolves to `pgsql` at `127.0.0.1:5432/proedge_new_hrms_uat` with no URL override, the database has 12 tables, and exactly the six expected migrations are pending in order. It reads only.

```powershell
$ErrorActionPreference = 'Stop'
$expectedPending = @(
  '2026_10_18_010000_create_leave_position_recording_engine',
  '2026_10_18_020000_create_immutable_punch_recording_engine',
  '2026_10_18_030000_create_punch_recording_writers',
  '2026_10_19_071658_create_attendance_day_processing',
  '2026_10_19_114219_create_roster_source_days_and_attendance_periods',
  '2026_10_19_124619_create_attendance_action_grants_missing_punch_and_payroll_intake'
)
$env:DB_DATABASE = 'proedge_new_hrms_uat'
try {
  if ($env:DB_URL -or $env:DB_HOST -or $env:DB_PORT -or $env:DB_CONNECTION) { throw 'Refusing: DB_URL, DB_HOST, DB_PORT or DB_CONNECTION is set in this process.' }
  $show = (php artisan db:show --json --no-interaction | Out-String) | ConvertFrom-Json
  if ($LASTEXITCODE -ne 0) { throw 'Refusing: db:show failed.' }
  $c = $show.platform.config
  if ($show.platform.connection -ne 'pgsql' -or $c.database -ne 'proedge_new_hrms_uat' -or $c.host -ne '127.0.0.1' -or "$($c.port)" -ne '5432' -or $null -ne $c.url -or $c.timezone -ne 'UTC') { throw "Refusing: Laravel resolves to $($show.platform.connection) $($c.host):$($c.port)/$($c.database)." }
  $tableCount = @($show.tables).Count
  $pending = @(php artisan migrate:status --pending --no-interaction | Select-String -Pattern '^\s*(\d{4}_\d{2}_\d{2}_\d{6}_\S+)' | ForEach-Object { $_.Matches[0].Groups[1].Value })
  if (Compare-Object $expectedPending $pending -SyncWindow 0) { throw "Refusing: pending migrations differ: $($pending -join ', ')" }
  if ($tableCount -ne 12) { throw "Refusing: expected 12 tables before migration, found $tableCount." }
  "GUARD PASSED: pgsql $($c.host):$($c.port)/$($c.database), tables=$tableCount, pending=$($pending.Count) in expected order"
} finally {
  Remove-Item Env:DB_DATABASE -ErrorAction SilentlyContinue
}
```

- [ ] Output is `GUARD PASSED: pgsql 127.0.0.1:5432/proedge_new_hrms_uat, tables=12, pending=6 in expected order`. Anything else: stop.

### 6.6 Apply

Run in the same PowerShell session, immediately after a passing guard. The block repeats the guard so a change between steps cannot slip through.

```powershell
$ErrorActionPreference = 'Stop'
$env:DB_DATABASE = 'proedge_new_hrms_uat'
try {
  $show = (php artisan db:show --json --no-interaction | Out-String) | ConvertFrom-Json
  $c = $show.platform.config
  if ($LASTEXITCODE -ne 0 -or $show.platform.connection -ne 'pgsql' -or $c.database -ne 'proedge_new_hrms_uat' -or $c.host -ne '127.0.0.1' -or "$($c.port)" -ne '5432' -or $null -ne $c.url -or @($show.tables).Count -ne 12) { throw 'Refusing: target check failed immediately before migrate.' }
  php artisan migrate --no-interaction
  if ($LASTEXITCODE -ne 0) { throw 'MIGRATION FAILED: stop and escalate (section 6.8).' }
  php artisan migrate:status --no-interaction
} finally {
  Remove-Item Env:DB_DATABASE -ErrorAction SilentlyContinue
}
php artisan config:show database.connections.pgsql.database
```

- [ ] Six `DONE` lines, in the section 4 order, and no error.
- [ ] The last line shows `proedge_new_hrms`, which proves the override is gone.

Do not run seeders, imports, fixture commands or `hrms:*` commands against UAT.

### 6.7 Post-migration verification

```powershell
$env:PGOPTIONS = '-c default_transaction_read_only=on'
psql -h 127.0.0.1 -p 5432 -U postgres -d proedge_new_hrms_uat -X -At -v ON_ERROR_STOP=1 -f "$dir\fingerprint.sql" -o "$dir\fingerprint_after.txt"
psql -h 127.0.0.1 -p 5432 -U postgres -d proedge_new_hrms_uat -X -At -v ON_ERROR_STOP=1 -c "SELECT batch, count(*) FROM migrations GROUP BY batch ORDER BY batch;" -c "SELECT tgname FROM pg_trigger WHERE NOT tgisinternal ORDER BY tgname;" -o "$dir\post_checks.txt"
Remove-Item Env:PGOPTIONS
```

All must hold:

- [ ] `fingerprint_after.txt` has 39 lines.
- [ ] For the 11 baseline tables other than `migrations`, each line equals the same line in `fingerprint_before.txt`.
- [ ] `migrations` has 15 rows; batches are `1|9` and `2|6`.
- [ ] These 27 tables appear with row count `0`: `leave_position_writers`, `leave_types`, `leave_positions`, `leave_position_movements`, `punch_identities`, `punch_identity_overlaps`, `punch_events`, `punch_recording_writers`, `attendance_policies`, `shifts`, `shift_versions`, `shift_assignment_candidates`, `attendance_day_result_versions`, `attendance_day_results`, `attendance_corrections`, `attendance_correction_values`, `roster_source_days`, `attendance_periods`, `attendance_period_events`, `attendance_payroll_handoffs`, `attendance_payroll_handoff_lines`, `attendance_payroll_action_grants`, `attendance_missing_punch_requests`, `payroll_periods`, `payroll_runs`, `payroll_run_declared_inputs`, `payroll_run_attendance_inputs`.
- [ ] Exactly these 16 triggers: `attendance_correction_values_no_delete`, `attendance_correction_values_no_update`, `attendance_day_result_versions_no_delete`, `attendance_day_result_versions_no_update`, `attendance_missing_punch_requests_guard_delete`, `attendance_missing_punch_requests_guard_update`, `attendance_payroll_handoff_lines_no_delete`, `attendance_payroll_handoff_lines_no_update`, `attendance_period_events_no_delete`, `attendance_period_events_no_update`, `payroll_run_attendance_inputs_no_update`, `payroll_run_declared_inputs_no_update`, `punch_events_no_delete`, `punch_events_no_update`, `punch_identity_overlaps_no_delete`, `punch_identity_overlaps_no_update`.
- [ ] `php artisan migrate:status --pending` with the UAT override reports no pending migration.
- [ ] `.env` still names `proedge_new_hrms`.

Record the approver's sign-off with the four output files.

### 6.8 Stop and escalate

Stop immediately, without retrying, when any of these happens:

- The guard refuses, or any command exits non-zero.
- A migration fails or prints an error.
- A session other than the operator's appears during the window.
- Any post-migration check differs from section 6.7.

Then:

1. Keep writers stopped. Do not run further migrations, manual DDL, `migrate:rollback`, `migrate:fresh` or `pg_restore --clean`.
2. Save the full console output and the files in `$dir`.
3. Notify the approver with the failing step and output.
4. The approver decides between recovery (section 6.9) and investigation with UAT left stopped.

### 6.9 Recovery

**Warning: recovery replaces UAT with the pre-migration dump. Anything written to UAT after the backup is lost.** Never use `pg_restore --clean` against the migrated database; the rehearsal showed it either fails or drops baseline foreign keys.

Run only on the approver's instruction, with every writer stopped:

```powershell
psql -h 127.0.0.1 -p 5432 -U postgres -d postgres -X -At -c "SELECT count(*) FROM pg_stat_activity WHERE datname = 'proedge_new_hrms_uat';"
dropdb -h 127.0.0.1 -p 5432 -U postgres proedge_new_hrms_uat
createdb -h 127.0.0.1 -p 5432 -U postgres --owner=postgres --template=template0 --encoding=UTF8 --locale-provider=libc --lc-collate="English_United States.1252" --lc-ctype="English_United States.1252" proedge_new_hrms_uat
pg_restore -h 127.0.0.1 -p 5432 -U postgres --exit-on-error --dbname=proedge_new_hrms_uat "$dir\proedge_new_hrms_uat_pre_attendance.dump"
```

- [ ] The session count is `0` before `dropdb`. If not, stop the remaining client rather than force-terminating it, unless the approver instructs otherwise.
- [ ] Use the values recorded in `uat_database_properties.txt` if they differ from the ones above.
- [ ] After restore, re-run the section 6.3 property query and the section 6.4 fingerprint. Both must equal the before files exactly, including `migrations` 9.

## 7. Migration is not operational readiness

Migrating the schema does not authorize any UAT user to operate Attendance, Leave or Payroll. After migration every new table is empty: no policy, shift, roster window, punch identity, writer or action grant exists, so the application refuses those workflows.

Before operational testing, each of these needs its own approval:

- A named, company-scoped owner for each grant used: punch recording writer, `finalize_attendance_period`, `reopen_attendance_period`, `handoff_attendance_period`, `review_missing_punch`, `consume_attendance_handoff`.
- A missing-punch reviewer distinct from every requester whose requests that reviewer will decide.
- An attendance policy, shifts, shift versions and roster windows for each UAT company. The VTL policy in section 1 is approved for `proedge_new_hrms_test` only.
- A controlled raw-punch source and source name. Without punches every rostered working day is absent by rule.
- Written rules for duplicate punches and for decision-3 conflicts. Until then duplicates block a batch and conflicts block recalculation and finalization.
- Confirmation that Payroll intake stays non-monetary until salary structures and salary heads exist. No `payroll_results`, amounts or payslips are produced.
- Leave: migration 1 creates empty leave tables only. No leave type, position, movement or leave-position writer is created, and no leave balance or leave processing is activated.
- A guarded way to serve the application against UAT; `.env` keeps `proedge_new_hrms`.

## 8. Authorization still required before execution

1. The signed approval in section 6.1.
2. The operator's confirmation, inside the window, of sections 6.2 to 6.5.
3. The approver's sign-off on section 6.7, or a decision under section 6.8 to recover by section 6.9.

Operational use of UAT then needs the separate approvals in section 7.
