# Universal HRMS — Device Punch Import Contract

**Status: DRAFT — NOT APPROVED FOR IMPLEMENTATION**

For Product Owner review. This draft records what is established about importing legacy device punches into the immutable punch-recording engine, the decisions still required, and the proposed batch rules. It authorizes nothing: no live source connection, no source import, no database uniqueness change, and no production or UAT write.

Date of draft: 2026-10-21. Last documentation update: 2026-10-23. The 2026-10-23 text records conservative recommendations for source edits and deletions after import, failed and partial imports, retries, and exception review. Those recommendations are not Product Owner approvals and do not authorize implementation.

## 1. Confirmed facts

### 1.1 Source

- Legacy table: `all_att_log`, called `legacy_master.all_att_log` in the architecture assessment and physically `proedgea_payrollmaster.all_att_log` in the 2026-10-02 dump. About 13.9 million rows in that dump.
- Columns: `id`, `date`, `emp_code`, `area`, `balid`, `update_datetime`, `company`, `location`, `remark`.
- `date` is a date-time with no zone. `emp_code` holds the punch card number, not the employee code.
- `company` was null on the traced rows. `area` and `balid` are populated on traced rows and their meaning is UNKNOWN (AUD-04). None of these three columns is used for company or employee attribution.
- The program that wrote the traced rows is not in the legacy repository. The in-repository writers (file upload, location transfer) did not produce them (AUD-04).
- The legacy application can delete a row by `id`, archive a row to `all_att_log_old` and delete it, and edit `date` and `emp_code` on an existing row (AUD-04).
- The legacy transfer writer stores the card through PHP `ltrim` with no character list, which removes whitespace only, not leading zeros (AUD-04).
- Live access to the legacy database is not authorized. Only a local dump and a local synthetic SQLite fixture have been used.

### 1.2 Next-HRMS implementation (as of this draft)

- `config/attendance.php` names the source: connection from `ATTENDANCE_DEVICE_LOG_CONNECTION` (unset by default), table `all_att_log`, source name `legacy all_att_log`, source event prefix `all_att_log:`, at most 2000 rows per read.
- `App\Attendance\DeviceLogSource` reads the table read only. `App\Attendance\DeviceLogPreview` classifies rows for the active company and stores nothing (`writes_enabled: false`).
- `App\Punch\DeviceEventIngestion::ingest()` is the only device-write entry point. Nothing calls it: no route, command, job, or import workflow.
- Ingestion takes the company from the trusted active-company context. The actor must be signed in, an active member of that company, and hold its punch-recording writer grant.
- Ingestion never creates, moves, ends, or reassigns a card mapping. A row is stored only when exactly one registered mapping of the active company covers the event time and no other company holds the card at that time.
- Card values are trimmed for whitespace and matched exactly. Leading zeros are preserved. No canonical card form is approved.
- Ingestion accepts only an event time with an explicit UTC offset. It does not convert source wall time.
- Manual recording and CSV upload refuse any row whose source name contains the device source name or table name, in any case or spacing, or whose source event id starts with `all_att_log:`.
- Repeat protection is application logic under advisory locks (source event, then card across all companies, then company card). There is no database uniqueness constraint on punch events beyond the row id.

### 1.3 Proposed mapping of one source row

| Punch event field | Source | Status |
|---|---|---|
| `source_name` | Configuration: `legacy all_att_log` | Implemented |
| `source_event_id` | `all_att_log:{id}` | Proposed. Conflicts with the migration workbook (see 2.C) |
| Card | `emp_code`, whitespace trimmed | Implemented |
| `event_at` | `date` plus an approved time basis | Blocked by decision 2.A |
| `ingested_at` | Commit time | Implemented |
| Employee and company | Registered card mapping of the active company covering `event_at` | Implemented |
| `area`, `balid`, `update_datetime`, `company` | Not used | Meaning unknown |

### 1.4 Current row outcomes

| Outcome | Condition | Stored |
|---|---|---|
| `recorded` | One covering mapping, no other company holder, new source event id, no same-time punch | Yes |
| `already_recorded` | Source event id already recorded with the same card and time in this company | No |
| `source_event_conflict` | Source event id already recorded with a different card, time, or company, or with an unreadable time | No |
| `possible_duplicate` | A punch for the card at the same time already exists in this company | No |
| `unmapped` | No mapping of this company covers the event time | No |
| `ambiguous` | More than one mapping covers the time, or another company also holds the card | No |
| `invalid_timestamp` | No offset, impossible date, zero date, or later than ingestion time | No |
| `invalid_identity` | Blank card | No |
| `invalid_source_event` | Blank source event id | No |

## 2. Decisions required before implementation

### A. Timestamp interpretation

**Product Owner decision recorded (2026-10-21):** the current operating region and the intended attendance-policy timezone is `Asia/Dhaka` (UTC+06:00).

This decision sets the policy timezone only. It does not settle how the legacy source stored its times:

- `all_att_log.date` carries no timezone offset.
- Its stored values are not assumed to be Asia/Dhaka time, and not assumed to be UTC.
- Before any source value is converted into an immutable punch-event timestamp, evidence must confirm the source clock and timezone basis (device clock, writer server clock, or UTC).
- Once the basis is confirmed, each source value is converted deterministically to an offset-aware timestamp. The same source value always yields the same instant.

Still open:

- Does `all_att_log.date` represent device-local time, server-local time, or UTC?
- The current preview reads `date` in each company's attendance policy timezone (Asia/Dhaka) and labels it so. That reading is a provisional display only and is not evidence of the source basis.
- How are zero dates and impossible timestamps reported? They fall outside every date range, so the current preview cannot see them. Reconciliation must list them separately and include them in source totals.
- Evidence required: source or device configuration, or a traceable punch with a known real-world time.

### B. Duplicate policy — PUNCH-CONF-002

- Is `all_att_log.id` the authoritative repeat key? **Decided 2026-10-21:** candidate key, canonical form `all_att_log:{id}`. Permanent stability is not proven.
- If a source row changes while keeping the same id, is it held for review? **Decided 2026-10-21:** yes, held for review; the original punch is never overwritten or deleted.
- Are two punches for the same card at the same timestamp permitted? **Decided 2026-10-21:** a same-card, same-time row with a different source id is held for review, neither accepted nor discarded automatically.
- Should database-level uniqueness be introduced, and on exactly which key? **Deferred 2026-10-21** until existing target data is checked and the source identity contract is validated. Not approved.
- Note: the legacy table's uniqueness on `date` plus `emp_code` (present in the 2026-10-02 dump; not read from a live database) may already have dropped same-second events. No Next-HRMS rule can recover them.
- Repeats, changed repeats, and same-time events are refused for review. Nothing is stored and nothing is discarded. This matches the decisions above.

#### PUNCH-CONF-002 — Product Owner Decision

**DRAFT — NOT APPROVED FOR IMPLEMENTATION.** The rules marked "Approved 2026-10-21" below are approved for documentation only. Implementation of the import workflow remains unauthorized. The rules marked "Recommended 2026-10-23" are conservative documentation defaults. They are not Product Owner approvals.

##### Product Owner decision — 2026-10-21

Approved for documentation:

1. **Source-event key.** `all_att_log.id` is the candidate source-event identity, represented canonically as `all_att_log:{id}`.
2. **Unchanged repeat.** If the same source-event id, company, card, and event time have already been accepted, the row is treated as already recorded. No further punch is created.
3. **Changed repeat.** If an existing source-event id arrives with a different card, event time, company, or other material identity field, it is held for review. The original immutable punch is never overwritten or deleted.
4. **Same card and timestamp, different source id.** Held for review. It is neither accepted nor discarded automatically.
5. **Database uniqueness.** Deferred. No unique index or database constraint is approved until existing target data has been checked and the source identity contract has been validated.

Qualifications:

- The 2026-10-02 dump supports `all_att_log.id` as a candidate key. Permanent id stability is not proven.
- The legacy application can edit source-row content under an existing id, and the archive table `all_att_log_old` was absent from the inspected dump.
- The canonical source id `all_att_log:{id}` must be validated consistently by ingestion, preview, and any future import workflow. That validation is not implemented yet; today ingestion accepts any non-empty source event id.
- This decision does not approve the timestamp interpretation of `all_att_log.date` (decision A), live-source access, import authorization (decision E), or production import.

##### Recommended conservative rules — 2026-10-23

**NOT Product Owner approved. NOT approved for implementation.** These rules apply the 2026-10-21 immutability and hold-for-review decisions to source edits, failures, retries, and exceptions. They do not authorize a batch layer, a new table, live source access, or an import workflow.

**Invariant.** An accepted punch event is never overwritten or deleted. A changed, deleted, missing, invalid, unmapped, ambiguous, or conflicting source record is never silently discarded.

**Item 6 — Exception records.** A row that is not accepted as a punch is never stored as a punch. When a batch layer exists, each such row is also kept as a durable exception record so it remains actionable. The exception schema is not approved. Until that record exists, the row-level outcome in the batch report is the only trace and must not be dropped.

**Item 7 — Source edit or deletion after import.** Reconciliation re-reads previously accepted source-event ids. A changed row is held as `source_event_conflict`. A source id that can no longer be read is held as a missing-source exception. The original punch stands. Absence from the live table is not treated as authority to delete the punch, and is not assumed to be an archive, a reuse of the id, or a correction.

**Item 8 — Failure, partial failure, and retry.** Each accepted row is its own commit through `ingest()`. A later retry of the same company, source, and range must not create another punch for an already accepted source event. Committed exact repeats return `already_recorded`. Rows that never committed are processed again under the same rules. Accepted punches are never deleted to "undo" a batch.

**Review authority.** Who reviews and closes exceptions is unresolved (decision E). Recording the import actor on the batch is not an assignment of review authority.

##### Current behaviour

Established from `DeviceEventIngestion::ingest()` and `PunchRecordingRegistry` as of this draft. Each call handles one row in its own transaction, under the source-event, card-wide, and company card locks.

| Case | What happens today | Evidence |
|---|---|---|
| 1. Same source event id, same company, card, and event time | `already_recorded`. Nothing stored | Code and tests |
| 2. Same source event id, changed card, event time, or company (or an unreadable time) | `source_event_conflict`. Nothing stored. The earlier punch is unchanged. The repeat check runs first, across all companies | Code and tests |
| 3. Same card and event time, different source event id | `possible_duplicate` when a punch for that card at that instant already exists in the active company, from any source. Nothing stored | Code and tests |
| 4. Different source event id and different event time | `recorded`, if exactly one mapping of the company covers the time and no other company holds the card | Code and tests |
| 5. Legacy row edited or deleted after import | Not detected. An edited row is seen only if it is read and ingested again, which gives case 2. A deleted or archived row is never noticed. Nothing reconciles source rows against stored punches | Absence of any reconciliation code |
| 6. Batch retried after partial failure or an uncertain commit | No batch layer exists. Re-sending a row that committed gives case 1; one that did not commit is processed normally. This holds only for writes made through `ingest()`. No database constraint enforces it | Code; not tested under concurrency |

Recommended handling for cases 5 and 6 is recorded in "Recommended conservative rules — 2026-10-23" and Section 3. It is not current behaviour and is not Product Owner approved.

The manual recording path (`record()`) deliberately stores repeated source event ids for manual sources. It refuses any row marked as a device-log row, so it is outside this policy.

##### What the legacy evidence does and does not establish

- The legacy application can delete a row by `id`, archive it to `all_att_log_old` and then delete it, and edit `date` and `emp_code` in place (AUD-04).
- The table cannot contain two rows for the same card in the same second (unique key below). Case 3 inside this one source then means the same event under a new id (deleted and re-inserted, or reloaded), or a different source.

**Offline dump evidence (recorded 2026-10-21).** Source: `D:\Project -All\Proedge HRMS\legacy-data\master\proedgea_payrollmaster.sql`, dump dated 2026-10-02, read offline and read only. No database was started or connected.

Structural evidence, from the table definition in the dump:

- `all_att_log.id` is a signed `INT` `AUTO_INCREMENT` primary key.
- The table uses the MyISAM engine.
- The unique key on (`date`, `emp_code`) exists.
- The next `AUTO_INCREMENT` value is 52,976,952.

Snapshot evidence, from the row data in the dump:

- 13,932,516 live rows with 13,932,516 distinct ids, ranging from 36,386,914 to 52,976,951. No id repeats within the table.
- `all_att_log_old` is absent from the dump, so overlap between live and archived ids cannot be assessed.

Limits: one snapshot cannot establish permanent id stability. It cannot rule out inserts with explicit ids, reloads, table rebuilds, or content changed under an existing id (which AUD-04 shows the legacy application can do).

**Conclusion: `all_att_log.id` is a supported candidate source-event key, but permanent stability is not proven.** The proposal to hold changed repeats for review stands. No unique index and no source-id policy is approved.

- Not established: whether ids are ever reused or renumbered, whether archiving keeps the id, and how the unknown external writer inserts, updates, or reloads rows.

##### Decision table

| # | Item | Options | Recommended default — NOT APPROVED | Integrity consequence | Schema or constraint change | Decision / approval |
|---|---|---|---|---|---|---|
| 1 | Authoritative source-event key | (a) `all_att_log.id`. (b) Natural key `date` plus `emp_code`. (c) Both, held separately | (a), conditional on the evidence in "Evidence still required" | (a) is stable only if ids are never reused or rewritten. (b) changes whenever a row is edited and depends on the unresolved time basis | None for the choice itself | **Approved 2026-10-21:** (a), as a candidate key. Permanent stability not proven |
| 2 | Source event id format | (a) `all_att_log:{id}`. (b) Raw `{id}` scoped by source name | (a). Manual recording's device-row guard and the preview already rely on this prefix | (b) weakens manual and device separation | None | **Approved 2026-10-21:** (a), canonical `all_att_log:{id}`. Consistent validation not yet implemented |
| 3 | Exact repeat (case 1) | (a) No-op, reported `already_recorded`. (b) Refuse the batch | (a) | (a) makes retries safe. (b) blocks every retry of a partly committed batch | None | **Approved 2026-10-21:** (a) |
| 4 | Reused id with changed card, time, or company (case 2) | (a) Always hold for review; never overwrite. (b) Accept as a new event | (a). Punch events are immutable, so overwriting is not an option | (b) can create two punches for one physical event, or attribute one row to two companies | None | **Approved 2026-10-21:** (a) |
| 5 | Same card and time, different id (case 3) | (a) Always hold for review. (b) Accept when the other event is from a different named source. (c) Treat as the same event and skip | (a) until the owner approves (b) or (c) | (b) and (c) are business rules: (b) can double-count one punch, (c) can drop a real one | None | **Approved 2026-10-21:** (a), held for review; neither accepted nor discarded automatically |
| 6 | Invalid, unmapped, or ambiguous rows | (a) Never stored as accepted punches; listed in reconciliation. (b) Stored in a separate exception record | (a) for punches now; (a) plus (b) when a batch layer exists | Neither loses the source row; (b) keeps a durable review trail | (b) needs a new table. Not approved | **Recommended 2026-10-23, not Product Owner approved:** never store as a punch (a). Keep a durable exception record when a batch layer exists (b) |
| 7 | Source edit or deletion after import (case 5) | (a) Reconciliation re-reads accepted source-event ids and raises an exception for a changed row (case 2) or a missing row. Punches are never rewritten or deleted. (b) Ignore source changes after import | (a) | (b) lets payroll keep events the source no longer holds, with no trace | (a) may need a reconciliation record; no change to `punch_events` | **Recommended 2026-10-23, not Product Owner approved:** (a) |
| 8 | Retry after partial failure or uncertain commit (case 6) | (a) Re-run the same company and range under the same batch reference; committed rows return `already_recorded`. (b) Manual cleanup | (a) | (b) is not possible: punch events cannot be deleted | Batch reference record when the batch layer is designed. Not approved | **Recommended 2026-10-23, not Product Owner approved:** (a) |
| 9 | Database-level uniqueness | (a) Partial unique index on `punch_events (source_name, source_event_id)` where `source_name = 'legacy all_att_log'`. (b) Same, including `company_id`. (c) No constraint; locks only | (a), added only after items 1, 2, and 7 are approved and existing data is checked | (a) prevents one source row in two companies, and two accepted punches for one row under any concurrency. (b) still allows one row in two companies. (c) depends on every writer using `ingest()` | Yes: migration. Must not cover manual sources, which store repeats by design | **Deferred 2026-10-21.** No constraint approved until existing target data is checked and the source identity contract is validated |

##### Evidence still required

- The DDL of `all_att_log` in the live database. The 2026-10-02 dump shows an `INT AUTO_INCREMENT` primary key on `id` and the unique key on `date` plus `emp_code`; the live database has not been checked.
- The DDL and row ids of `all_att_log_old`, which is absent from the 2026-10-02 dump.
- Whether `id` values are ever reused, rewritten, or renumbered (reloads, table rebuilds, restores).
- Whether archiving to `all_att_log_old` keeps the original `id`, so a missing row can be told apart from an archived one.
- How the unknown external writer inserts and corrects rows: in place, delete then insert, or reload.
- Whether existing `punch_events` data contains repeated `(source_name, source_event_id)` values in each target database before any constraint. Only `proedge_new_hrms_test` was checked: none on 2026-10-20. UAT and the main database were not checked.
- The source time basis (decision A). It affects case 3 and any natural-key option.

##### Decisions awaiting the Product Owner

- Confirmation of the 2026-10-23 conservative recommendations for items 6, 7, and 8. They are documented defaults, not approvals.
- Item 9, database uniqueness: deferred, to be decided after existing target data is checked and the source identity contract is validated.
- Whether any named source may ever legitimately record the same card at the same time. Item 5 holds such rows for review until then.
- Who reviews and resolves held rows and reconciliation exceptions, and whether that person must be different from the importer and from the person who manages card mappings (decision E).
- Whether device imports use the existing punch-recording writer grant or a dedicated company-scoped import grant (decision E).
- How a source id that is absent from the live table but present in `all_att_log_old` should be classified. The archive table was absent from the inspected dump, so this cannot be inferred.
- Whether reconciliation is only operator-triggered, and how wide a re-read may be. Cadence is not chosen.
- Whether a fingerprint change requires a new batch reference before any further write. The recommendation below is yes; it is not approved.

**DRAFT — NOT APPROVED FOR IMPLEMENTATION.**

### C. Source identity

- Evidence so far: in the 2026-10-02 dump, `all_att_log.id` is a signed `INT` `AUTO_INCREMENT` primary key on a MyISAM table, unique across 13,932,516 rows. `all_att_log_old` is absent from that dump. See "Offline dump evidence" under PUNCH-CONF-002. **`all_att_log.id` is a supported candidate source-event key, but permanent stability is not proven.**
- Confirm whether `all_att_log.id` is stable across source updates, archival to `all_att_log_old`, and deletion.
- Reconcile the proposed `source_event_id` (`all_att_log:{id}`) with the migration workbook, which plans an empty `source_event_id` and a `source_key` column that `punch_events` does not have.
- How source rows changed or removed after import are reconciled is recorded as a 2026-10-23 recommendation under PUNCH-CONF-002 item 7 and Section 3. It is not Product Owner approved. Permanent id stability and the meaning of `all_att_log_old` remain unproven.

### D. Card ownership and employee eligibility

- Who is authorized to register, end, and verify each company's card mappings, and which company card map is the evidence?
- What evidence must support a card-to-employee mapping?
- Must a mapping's effective period fall within the employee's employment period? Employment periods are not checked today.
- How is a card held by two companies at the same time resolved? Today such rows are refused as ambiguous in both companies.

### E. Authorization

- Should device imports use the existing punch-recording writer grant or a dedicated company-scoped import grant? A dedicated grant needs a schema change.
- Should the person importing punches be different from the person who manages card mappings?
- Who may review unresolved rows (unmapped, ambiguous, conflicts, invalid, source-changed, source-missing) and act on them?

**Known write responsibility (already implemented, not an import approval).** `DeviceEventIngestion::ingest()` takes the company from the trusted active-company context. The actor must be signed in, an active member of that company, and hold its punch-recording writer grant. That is the current write check. It does not decide who may run a batch, who may review exceptions, or whether a dedicated import grant is required.

**Recommended exception audit contents — 2026-10-23, not Product Owner approved.** Every batch and every exception should record company, source name, source-event id, outcome, actor, time, the source values read (or that the source id was absent), the accepted punch id when one exists, the batch reference, and any later resolution (decision, resolver, time, reason). Recording the import actor is not an assignment of review authority.

**Unresolved.** The Product Owner has not named the reviewer, has not required the importer and reviewer to be different people, and has not chosen between the existing writer grant and a dedicated import grant. No role is assigned by this document.

### F. Source registry

- Manual and device rows are separated by name and id markers only. A device punch entered manually under an unrelated source name cannot be detected.
- Is an approved list of named sources required?

## 3. Proposed batch, retry, and reconciliation rules

**Status: recommended 2026-10-23. NOT Product Owner approved. NOT approved for implementation.** PUNCH-CONF-002 items 1–5 remain the only rules approved for documentation. Items 6–8 and this section are conservative defaults.

- Preview one company and an explicit date range before import.
- Capture a fingerprint of the previewed source rows and mapping state. Recheck it inside the commit; refuse the batch if anything changed.
- Require an explicit operator confirmation before any write.
- Route every row through `DeviceEventIngestion::ingest()` only. Never register or reassign a card automatically.
- Record a batch reference with actor, company, source, date range, fingerprint, and outcome totals in the audit trail.
- Report separate counts for recorded, already recorded, source conflict, possible duplicate, unmapped, ambiguous, invalid timestamp, invalid card, invalid source id, source missing, fingerprint mismatch, and batch failed.
- Preserve row-level outcomes. Reconcile every source row read against a stored event or a reported exception, including zero and out-of-range dates.
- Retries must not create additional punches for an already accepted source event. Today this holds only for writes made through `ingest()`.
- Treat changed, missing, conflicting, invalid, unmapped, and ambiguous rows as review exceptions. They are never stored as punches and never discarded.
- Define transaction boundaries, a maximum batch size, and recovery behaviour before enabling batch imports. Many rows in one transaction hold all their locks until commit; concurrent batches can deadlock and be rolled back, so a retry must be safe to repeat.

### 3.1 Detection

| Case | How it is detected | What is not inferred |
|---|---|---|
| Unchanged accepted source event | `ingest()` finds the same source-event id, company, card, and event time | Nothing further is written |
| Source row edited after import | Reconciliation re-reads each previously accepted `all_att_log:{id}` and compares card, event time, and company. A material difference is `source_event_conflict` | The new values are not a correction of the punch |
| Source row deleted or unreadable after import | The accepted source-event id can no longer be read from the live source | Not classified as archived, reused, or corrected. `all_att_log_old` was absent from the inspected dump |
| Date moved out of the original window | Id-level re-read is required. A range-only re-read can look like a deletion in the old window | A later appearance under the same id in another window is the same source event, not a new punch |
| Invalid, unmapped, or ambiguous row | Existing `ingest()` / preview outcomes | Not stored as a punch |
| Partial batch | One or more rows committed and one or more did not, or the batch stopped before every previewed row was attempted | Not treated as rolled back. Accepted punches stay |
| Uncertain commit | The process, connection, or transaction result is unknown | Not treated as a failure that must be undone. The next retry is the recovery |
| Fingerprint mismatch | Source rows or card mappings changed after preview and before or during commit | Not an automatic re-import of the new set |

A range-only re-read is a fallback when live lookup by id is not available. It must still list missing-in-range ids as exceptions. It cannot safely claim the row was deleted.

### 3.2 Recording

- **Accepted punch.** Written only by `ingest()`, and only for `recorded`. Never updated. Never deleted.
- **Exact repeat.** Reported `already_recorded`. No punch is written.
- **Exception.** Every other outcome is an exception: `source_event_conflict`, `possible_duplicate`, `unmapped`, `ambiguous`, `invalid_timestamp`, `invalid_identity`, `invalid_source_event`, and the proposed outcomes `source_missing`, `fingerprint_mismatch`, and `batch_failed`.
- **Durable exception record.** Recommended when a batch layer exists (item 6). Contents: company, source name, source-event id, outcome, actor, time, source values read or "absent", accepted punch id when one exists, batch reference. Closing fields: decision, resolver, time, reason. The table is not approved.
- **Batch reference.** Recommended: actor, company, source, date range, fingerprint, confirmation, start and end time, and counts for every outcome above. Status is one of: `completed`, `completed_with_exceptions`, `incomplete`, `failed` (no row committed), or `uncertain`. The table is not approved.
- **Audit trail.** The batch reference, each accepted punch, and each exception or later resolution are retained. An exception is not removed when it is closed; it is marked resolved.

### 3.3 Retry and idempotency

- Retry the same company, source, and date range under the same batch reference when the fingerprint is unchanged.
- If the fingerprint changed, refuse further writes on that reference. A new preview and a new batch reference are required. That last sentence is a recommendation, not a Product Owner decision.
- A row that already committed with the same source-event id, company, card, and event time returns `already_recorded`.
- A row that already committed and now has a different card, event time, or company is `source_event_conflict`. The original punch stands.
- A row that never committed is processed as a first attempt.
- An uncertain commit is retried the same way. `already_recorded` means the first commit landed. A normal `recorded` or exception means it did not.
- Accepted punches are never deleted to recover from a failed or partial batch.
- Retry safety depends on every device write going through `ingest()`. Database uniqueness remains deferred (item 9).
- Overlapping concurrent batches for the same company and range should be refused. That is a recommendation. Advisory locks are the only current serialisation.

### 3.4 Reconciliation

- After a batch, every previewed source row has either an accepted punch or an open exception.
- Zero dates and impossible timestamps stay outside the date-range read. They are listed separately and included in source totals (decision A still open).
- A later reconciliation of accepted source-event ids detects edits and disappearances that happened after import (item 7).
- Reconciliation never rewrites or deletes a punch. It only opens or refreshes exceptions.
- How often reconciliation runs, and whether it is only operator-triggered, is unresolved.

### 3.5 Resolution

- Closing an exception requires an explicit recorded decision. Silence is not a close.
- Closing an exception does not write, overwrite, or delete a punch.
- Safe decisions that can be recorded without a further Product Owner rule: leave the accepted punch standing and note the source change or absence; keep the row held; fix a card mapping and then re-present the source row to `ingest()` as a new attempt.
- Re-presenting a source row is still subject to items 1–5. It cannot attach new source values to an already accepted punch.
- Who may take those decisions is unresolved (decision E). The import actor recorded on the batch is not the reviewer unless the Product Owner later says so.

## 4. Approval gate

Implementation may proceed only after all of the following:

1. The Product Owner has resolved decisions A to F in Section 2.
2. The batch and reconciliation design in Section 3 is agreed.
3. A rehearsal against the synthetic fixture in `proedge_new_hrms_test` has produced a reconciliation report.
4. Live source access is explicitly authorized, with credentials held outside the repository and a read-only database account.

**DRAFT — NOT APPROVED FOR IMPLEMENTATION. No live connection, source import, database uniqueness change, or production write is authorized by this document.**

## 5. Evidence

- `docs/UNIVERSAL_HRMS_AUD04_PUNCH_WRITER_AREA_BALID_AUDIT.md`
- `docs/UNIVERSAL_HRMS_ARCHITECTURE_ASSESSMENT.md`
- `docs/UNIVERSAL_HRMS_MULTI_COMPANY_MIGRATION_MAPPING_WORKBOOK.md`
- `config/attendance.php`
- `app/Attendance/DeviceLogSource.php`, `app/Attendance/DeviceLogPreview.php`
- `app/Punch/DeviceEventIngestion.php`, `app/Punch/PunchRecordingRegistry.php`, `app/Punch/PunchIdentityRegistry.php`
- `tests/Feature/DeviceEventIngestionTest.php`, `tests/Feature/DeviceLogSourcePreviewTest.php`
