# Universal HRMS — Attendance UAT Migration Execution Report

Result: **UAT schema migration completed successfully.** Every required post-migration check passed.

This migration does **not** authorize operational use of Attendance, Leave or Payroll in UAT, and does **not** authorize any production release. See section 9.

Procedure: `docs/UNIVERSAL_HRMS_ATTENDANCE_UAT_PREPARATION.md` sections 6.2–6.7.

## 1. Execution

| Item | Value |
|---|---|
| Date | 2026-10-19, Asia/Dhaka (UTC+6) |
| Preflight start | 22:46:23 |
| Backup created | 22:47:03 |
| Migration window | 22:47:25 to 22:47:28 |
| Verification | 22:47:44 to 22:48:03 |
| Operator | Cursor agent in the Windows session of user `ASUS`, on instruction from the project owner |
| Repository | `D:\Project -All\Proedge HRMS\next-hrms` |
| Evidence folder (outside the repository) | `D:\Project -All\Proedge HRMS\uat-migration\2026-10-19\` |

No Git command was run. No file in the repository was changed by the migration. `.env` still names `proedge_new_hrms`. No seeder, import, provisioning or `hrms:*` command was run against UAT. MySQL was not started and no production server was contacted.

## 2. Resolved target

| Check | Result |
|---|---|
| PostgreSQL server | `127.0.0.1:5432`, version 18.6, system identifier 7694707497988598612, data directory `G:\postgre_data` |
| Databases on the server | `postgres`, `proedge_new_hrms`, `proedge_new_hrms_restore_verify_20261017`, `proedge_new_hrms_test`, `proedge_new_hrms_uat`, `template0`, `template1`. No production database |
| Laravel runtime (runbook guard, immediately before backup) | connection `pgsql`, driver `pgsql`, host `127.0.0.1`, port `5432`, database `proedge_new_hrms_uat`, URL empty, timezone `UTC` |
| Laravel runtime (repeated inside the apply block, immediately before `migrate`) | same values, 12 tables |
| Environment overrides | Only `DB_DATABASE=proedge_new_hrms_uat`, scoped to the process and removed afterwards. `DB_URL`, `DB_HOST`, `DB_PORT`, `DB_CONNECTION` unset |
| UAT sessions before backup and before apply | 0 |

## 3. Preflight and baseline

| Check | Expected | Result |
|---|---|---|
| Tables | 12 | 12 |
| Pending migrations | The six in section 5, in order | Match, in order |
| Database properties | owner `postgres`, `UTF8`, provider `c` (libc), collate and ctype `English_United States.1252` | `proedge_new_hrms_uat\|postgres\|UTF8\|c\|English_United States.1252\|English_United States.1252\|-1\|\|`: connection limit -1, no ACL, no database settings |
| Fingerprint vs rehearsed baseline | Identical | Identical for all 12 tables |

Pre-migration fingerprint (`fingerprint_before.txt`; row count and md5 of ordered row text):

| Table | Rows | Content hash |
|---|---|---|
| audit_events | 3 | 25d374c58e0a7a0fabd93da0d74e3f67 |
| branches | 0 | d41d8cd98f00b204e9800998ecf8427e |
| companies | 2 | 7cf6bfc1bb37b77e4913555c706d50db |
| company_memberships | 4 | 8ce33283ea4d99cadf62a6c25291aa90 |
| departments | 1 | 827e0bfc09a047ff6928a36bedcde337 |
| designations | 0 | d41d8cd98f00b204e9800998ecf8427e |
| employees | 2 | 5db481eff712820cf2edda4bda1e7a42 |
| employment_episodes | 3 | 0beead5ff1fd5fd5c1f4356d1f2bd783 |
| location_nodes | 0 | d41d8cd98f00b204e9800998ecf8427e |
| migrations | 9 | bdadf73e96608c445c88d1cc9db8001b |
| organization_nodes | 0 | d41d8cd98f00b204e9800998ecf8427e |
| users | 3 | 0c49a55278edededd7e461d5cdaa2c40 |

## 4. Pre-migration backup

| Item | Value |
|---|---|
| Path | `D:\Project -All\Proedge HRMS\uat-migration\2026-10-19\proedge_new_hrms_uat_pre_attendance.dump` |
| Format | `pg_dump --format=custom`, PostgreSQL 18.6 |
| Size | 31,571 bytes |
| SHA-256 | `3675e2ad76025063a523d99468160d1887e366ce4e28fdd11909c24628ffc691` |
| Listing | `pg_restore --list` exit 0, 74 TOC entries, `TABLE DATA` present for all 12 tables (`dump_listing.txt`) |
| Full read | `pg_restore --file` exit 0, 24,690 bytes of SQL with 12 `COPY` blocks, one per table. The SQL file was deleted after the check |
| Restore properties | `uat_database_properties.txt` |

The dump contains UAT user rows and password hashes. Keep the folder access-restricted.

## 5. Migration

Command: `php artisan migrate --no-interaction` inside the runbook apply block, with the process-scoped override. Exit code `0`. Full output: `phase_b_migration.log`.

| Order | Migration | Batch | Time |
|---|---|---|---|
| 1 | `2026_10_18_010000_create_leave_position_recording_engine` | 2 | 39.81 ms |
| 2 | `2026_10_18_020000_create_immutable_punch_recording_engine` | 2 | 25.36 ms |
| 3 | `2026_10_18_030000_create_punch_recording_writers` | 2 | 5.07 ms |
| 4 | `2026_10_19_071658_create_attendance_day_processing` | 2 | 56.98 ms |
| 5 | `2026_10_19_114219_create_roster_source_days_and_attendance_periods` | 2 | 40.94 ms |
| 6 | `2026_10_19_124619_create_attendance_action_grants_missing_punch_and_payroll_intake` | 2 | 46.38 ms |

Each printed `DONE`. No error. No other command changed UAT.

## 6. Post-migration verification

Full output: `phase_c_verification.log`, `phase_c_runtime_check.log`, `post_checks.stdout.txt`, `fingerprint_after.txt`. Result file: `post_verification_result.txt` = `PASSED`.

| Check | Expected | Result |
|---|---|---|
| Migration records | 15; batch 1 = 9, batch 2 = 6 in section 5 order | 15; `{"1":9,"2":6}`; order matches |
| Tables | 39 = 12 baseline + 27 new, nothing else | 39, no unexpected table |
| New tables | All 27 present with 0 rows | Yes |
| Triggers | 16, on the expected tables | 16, exact table and name match |
| Columns vs `proedge_new_hrms_test` | 383, no difference | 383, 0 missing, 0 extra |
| Constraints (keys, foreign keys, unique, check) | 562, no difference | 562, 0 missing, 0 extra |
| Indexes | 91, no difference | 91, 0 missing, 0 extra |
| Trigger definitions | 16, no difference | 16, 0 missing, 0 extra |
| Functions | 8, no difference | 8, 0 missing, 0 extra |
| Baseline tables other than `migrations` | Fingerprint identical | Identical for all 11 |
| Laravel runtime after migration | `pgsql 127.0.0.1:5432/proedge_new_hrms_uat`, UTC, no pending | Same; 39 tables; `No pending migrations` |
| Default runtime | `proedge_new_hrms` | `proedge_new_hrms`; `.env` unchanged |
| UAT sessions after verification | 0 | 0 |

Triggers present: `attendance_correction_values` (no_delete, no_update), `attendance_day_result_versions` (no_delete, no_update), `attendance_missing_punch_requests` (guard_delete, guard_update), `attendance_payroll_handoff_lines` (no_delete, no_update), `attendance_period_events` (no_delete, no_update), `payroll_run_attendance_inputs` (no_update), `payroll_run_declared_inputs` (no_update), `punch_events` (no_delete, no_update), `punch_identity_overlaps` (no_delete, no_update).

No employee, punch, attendance, leave, payroll, writer-grant, action-grant or other business record was created: all 27 new tables are empty and the 11 baseline business tables are unchanged.

## 7. Other databases unchanged

| Database | Before | After |
|---|---|---|
| `proedge_new_hrms` | 20 tables, digest `b85d2b972f036e7caa83e673304bd832` | identical |
| `proedge_new_hrms_test` | 39 tables, digest `544cb2e92c67b416646c3faf987a1d56` | identical |

Both digests also equal the values recorded during the scratch rehearsal. `proedge_new_hrms_restore_verify_20261017` was not opened.

## 8. Warnings and deviations

- **Password delivery.** The runbook expects a `pgpass.conf` entry or an interactive prompt for `psql`, `pg_dump` and `pg_restore`. The session could not answer a prompt, so the evidence helper `uat-exec.php` passed the configured password to those tools through the child-process environment. It was never on a command line or in a log. The commands themselves follow the runbook.
- **Evidence helper.** Fingerprint, listing, full-read and post-checks were run through `uat-exec.php` in the evidence folder, using the runbook's SQL and options. It refuses to overwrite an existing dump and connects read-only for every check except `pg_dump`.
- **Session count of 1 inside the verification step.** That was the helper's own read-only connection to UAT. A separate process immediately afterwards counted 0.
- **Dump checksum differs from the rehearsal dump.** Each dump embeds its creation time; the table data and listing match.
- **Untested:** recreating UAT with explicit locale arguments (runbook section 6.9). It has not been needed.

No check failed.

## 9. Authorization boundary

- The six approved migrations are applied to `proedge_new_hrms_uat` only.
- The leave-position engine is schema only. No leave type, position, movement, writer, balance or leave processing exists.
- No attendance policy, shift, roster window, punch identity, punch, writer grant or action grant exists in UAT, so Attendance, Leave and Payroll workflows refuse to run.
- Operational testing in UAT still needs the separate approvals in runbook section 7: named company-scoped grant owners, distinct missing-punch requester and reviewer, policy, shifts and roster windows per company, a controlled raw-punch source, duplicate-punch and conflict rules, and confirmation that Payroll intake stays non-monetary.
- Production release is not authorized.

## 10. Recovery (not performed)

Not needed. If a later problem requires it, follow runbook section 6.9 only with separate explicit authorization: with all writers stopped, drop `proedge_new_hrms_uat`, recreate it with the properties in section 3, and restore the dump in section 4 with `pg_restore --exit-on-error`, then re-fingerprint. **This loses every UAT write made after 2026-10-19 22:47:03.** Do not use `pg_restore --clean`, `migrate:rollback` or manual constraint changes.
