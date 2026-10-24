# Universal HRMS Migration and Data Continuity Architecture

Status: architecture only. No migration code, scripts, database changes, production access, or data loads are included.

Approved inputs:

- `docs/UNIVERSAL_HRMS_REQUIREMENTS.md`
- `docs/UNIVERSAL_HRMS_ARCHITECTURE_ASSESSMENT.md`
- `docs/UNIVERSAL_HRMS_LOGICAL_DATA_ARCHITECTURE_ASSESSMENT.md`
- `docs/UNIVERSAL_HRMS_PHYSICAL_DATA_ARCHITECTURE_ASSESSMENT.md`
- `docs/UNIVERSAL_HRMS_TARGET_DATABASE_SCHEMA_SPECIFICATION.md`

Audit copies already studied (`legacy_proedge`, `legacy_vtlhrm`, `legacy_master`, and the company catalog) explain source shape. They are not the go-live baseline. Each company’s live cutover uses a new read-only production snapshot.

Proedge behavior is a source. Viyellatex behavior is a source. Neither is the target policy.

```
UNIVERSAL HRMS CORE
  + COMPANY-CONFIGURABLE POLICIES
  + OPTIONAL CLIENT EXTENSIONS
```

---

## 1. Purpose

No company goes live on the new HRMS until that company’s required production data has been migrated, technically validated, business-reconciled, and explicitly approved.

Matching a row count is not completion. Completion means every required source record is either loaded or recorded as an approved exception, historical meaning is preserved where it is known, and nothing required is silently dropped or recalculated.

The continuity target is **0% unexplained loss of required production data**.

```
SOURCE COUNT = LOADED + APPROVED EXCEPTIONS
```

Unexplained missing records must be zero before a company can be approved.

## 2. Migration principles

| Principle | Rule |
|---|---|
| One product | Every company loads into the same target schema |
| Company boundary | Every loaded fact carries that company’s `company_id`. No cross-company write |
| Legacy is read-only | Normal migration does not update production or the legacy databases |
| New model | Load into the target tables. Do not copy `db_alias`, `previous_db_id`, or alias branches |
| Repeatable | The same snapshot and mapping version can be loaded again and produce the same result |
| Correctable | A failed run is fixed by changing mappings or exception dispositions, then rerunning. Target rows from a dry run are replaced by the next run, not hand-edited |
| Versioned | Each run records snapshot id, mapping version, and migration version |
| Historical facts stay historical | Stored payroll amounts are not recalculated with new rules. Stored attendance days keep their source meaning where that meaning is known |
| No invented meaning | Unknown legacy fields stay unknown |
| Decisions stay open | D01–D18 are not inferred from Proedge or Viyellatex history |
| Gate | Technical load without business approval is not Ready for Cutover |

Required production data is the business facts the target schema can hold: company structure, employees, punch identities, retained punch events, day results, leave, corrections to the extent the source preserved them, overtime evidence classified by what it actually is, and payroll amounts. Legacy-only columns are not required data. Dropping them without an archive reference, when they are needed to explain a loaded fact, is not allowed. That case becomes an exception or an archive reference, not a silent drop.

## 3. Company-wise migration lifecycle

Each company moves through this path on its own:

```
PRODUCTION
→ READ-ONLY SNAPSHOT
→ EXTRACT
→ TRANSFORM
→ LOAD
→ VALIDATE
→ RECONCILE
→ BUSINESS APPROVAL
→ COMPANY MIGRATION READY
```

A company is not marked ready because the load finished. Ready for Cutover follows the certificate in section 14. Cutover Complete follows the delta in section 17.

| Status | Meaning | May go live |
|---|---|---|
| Not Started | No snapshot for this company | No |
| Extracted | Read-only snapshot captured and counted | No |
| Transforming | Mapping version is being applied | No |
| Loaded | Target rows exist for this migration version | No |
| Technical Validation | Counts and referential checks are running or have failed | No |
| Business Reconciliation | Business totals are being compared to the baseline | No |
| Exception Review | Every unexplained gap is in the exception register | No |
| Approved | Certificate signed. Unexplained missing records = 0 | No |
| Ready for Cutover | Final delta reconciled and cutover authorized | Not yet. Authorization is the last step |
| Cutover Complete | New HRMS is the system of use for this company | Yes, for this company only |

These statuses are specified. They are not implemented.

Company A can be Cutover Complete while Company B is still in Business Reconciliation. One company’s approval does not approve another company. Sibling aliases in the legacy catalog (for example other Viyellatex company rows) are separate companies. They are not included in company 20’s certificate.

## 4. Production baseline

A production migration baseline is a frozen, reproducible description of one company at one cutoff. Local audit databases inform the transformation. They do not replace this baseline.

For each company and each migration version, record:

| Baseline item | Content |
|---|---|
| Company | Target `company_id` and legacy company id |
| Source database | The production database name and the read-only snapshot id. Shared punch storage is named separately from the company database |
| Source company identity | Legacy company id. Not `db_alias`. Not `previous_db_id` |
| Extraction timestamp | When the snapshot was taken |
| Production data cutoff | The last source timestamp included |
| Employee totals | Distinct employees, codes, active, inactive, separated |
| Attendance totals | Day-result counts by source status text, and the date range |
| Raw punch totals | Events attributed to this company through the punch-identity map, plus events that could not be attributed |
| Leave totals | Requests, outcomes, and day counts as stored |
| Payroll totals | Employees paid, head amounts, earnings, deductions, net, by period |
| OT totals | Separate figures for attendance OT evidence and payroll OT amounts. They are not added together |
| Source identifiers | Legacy primary keys retained as `source_system` and `source_key` |
| Migration version | Mapping version plus snapshot id |
| Record counts | Source count by object, before transform |

The same snapshot id and mapping version must reproduce the same extract counts. A new production cutoff creates a new baseline. It does not edit the previous one.

Shared `all_att_log` style storage is not a company database. Company attribution is through that company’s punch-identity map. Events that map to nobody, or to more than one company, are baseline exceptions. They are not assigned by guess.

## 5. Master data migration

| Object | Source | Target | Transformation | Validation | Reconciliation | Known ambiguity |
|---|---|---|---|---|---|---|
| Company | Catalog company row | `companies` | Name and legacy id as `source_key`. Drop alias-as-behavior | One target company per legacy company id | Company identity | `db_alias` and `previous_db_id` are not target fields |
| Branch | Legacy site or unit only where it is actually a site | `branches` | Create a branch only when the source has a real site | Count of sites | Site list | Factory depth (unit, floor, line) is not a universal tree. Extra depth is optional `location_nodes` or an exception |
| Department | Department master | `departments` | Under the company, not forced under a branch | Codes and names | Department count | Naming differences across copies |
| Designation | Designation master | `designations` | Same | Codes and names | Designation count | None universal |
| Employee group | Group or category used for shared policy, when one exists | `employee_groups` | Load only a real group | Membership count | Group totals | A legacy column that is not a group must not be turned into one |
| Employee | `employees` | `employees` | Code and name. One company | Distinct codes among active employees | Section 13 employee block | Duplicate active codes are exceptions |
| Employee code | Employee code column | `employees.employee_code` | Preserve the code text on the original identity. Do not reassign it | `UNIQUE (company_id, employee_code)` | Code match, including duplicates | Codes are not reused. Two identities are not merged. A duplicate is an explicit exception. Closed by `docs/UNIVERSAL_HRMS_STEP20_FINAL_EMPLOYEE_CODE_DECISION.md` |
| Employment history | Joining, separation, department, designation, status | `employment_episodes`, `employment_assignments` | One episode per joining span. Facts become intervals. Do not invent intervals the source never had | Joining and last working date present when the source has them | Active, inactive, separated counts | A single current row is not a full history. Missing history stays missing |
| Reporting | Boss and grand-boss columns, when populated | `reporting_chains`, `reporting_links` | Optional evidence of a chain. Not an approval workflow | Both people exist in the same company | Chain count versus populated source columns | Null boss on an approved request is a source limitation, not a step. BD-D17 stays open |
| Salary heads | Head master | `salary_heads` | Code, name, earning or deduction as labeled | Head count | Head list | Do not add a universal absenteeism or OT head |
| Salary structure | Structure and employee amounts | `salary_structures`, assignments, head values | Identity plus the amounts the source stored | Employees with amounts | Amount totals by head | Amounts are historical inputs, not a new formula |
| Shift definition | `att_shift` | `shifts`, `shift_versions` | Start, end, punch window. A next-day window sets `crosses_midnight` | Window present | Shift count | Grace is not copied. No minutes are chosen |
| Shift assignment | Employee shift and, where present, roster rows | `shift_assignment_candidates` | Default and roster are both candidates | Every source assignment has a candidate or an exception | Candidate counts | Do not write `shift_resolutions` as if BD-D03 were decided. The historical day result cites the shift the source stored on that day |
| Holiday calendar | Holiday dates | `holiday_calendars`, `public_holidays` | Preserve the source date and name | Date count | Holiday dates | A weekly off stored in an employee photo column is not a public holiday |
| Weekly off | Photo weekday text, or a weekly-off column, only when the business confirms the meaning | `weekly_off_rules` | Transform only confirmed values | Confirmed rows match source | Weekly-off coverage | Unconfirmed photo text is an exception, not a rule |

## 6. Attendance and punch migration

| Object | Source | Target | Rule |
|---|---|---|---|
| Raw punch | Shared punch log and any company log that actually contains events | `punch_events` | `event_at` is the source timestamp, unchanged. `ingested_at` is the migration load time, stored separately |
| Punch identity | Punch-card map | `punch_identities` | Identity value may differ from employee code. Preserve both |
| Punch source | Named only when the source path is known | `punch_sources` | The studied closed months came from an unknown writer. Those events use a source name that states “legacy punch log, writer unknown.” Do not name a vendor |
| Company of a punch | Identity map in the company database | `punch_events.company_id` | Not the empty company column on the shared log |
| Source event id | Only a real upstream event id | `source_event_id` | `balid` is not this field |
| Day result | `attendance_detail` | `attendance_day_result_versions` and the current pointer | One surviving source row becomes one historical version |
| Earlier versions | Usually absent | Not invented | Delete-and-insert in the legacy processors destroyed prior versions. The limitation is recorded |
| Selected punches | In and out stored on the day, matched to raw events | `attendance_selected_punches` | Link only when the timestamp matches an existing event. Do not invent the middle punches the processor dropped |
| Shift on the day | Shift stored on that day result | Cited on the historical result | This preserves what was used. It is not a new winner between default and roster |
| Correction | Approval file | Section 8 | Separate from the punch |

`work_date` for a historical day result is the date the legacy system stored as that attendance day. That is the historical business date. It is not recomputed by a new shift-winner rule. Recomputation would change history and would decide BD-D03.

Punch timestamps stay on `event_at`. They are not rewritten to `work_date`. A punch at Oct 10 02:00 remains Oct 10 02:00 even if a preserved day result for Oct 9 selected it.

After cutover, new calculations derive `work_date` from the effective shift, including a cross-midnight window. That go-forward rule is not applied backwards to rewrite loaded history.

Status text is preserved as a source token on the historical result, labeled as a legacy token. It is not registered as the company’s future vocabulary. Proedge words and Viyellatex letters are both legacy tokens. Incomplete punch evidence is preserved beside the token: the source’s missing-punch text, or a stored in without a stored out. The target `incomplete_condition` is filled only when that evidence is direct. The future outcome of incomplete punch remains BD-D02 and is not written as the historical status.

Where the source day cannot be tied to a shift window safely, keep the day result, keep the punches, and record the limitation. Do not invent the link.

Same-second source uniqueness may already have dropped an event before extract. The migration does not recreate it. The baseline records that the source itself could not hold two events at the same timestamp for one identity.

## 7. Leave migration

| Object | Treatment |
|---|---|
| Leave type | Load as `leave_types` with the source code and name. Paid, unpaid, or partial is not filled |
| Leave request | Load range, type, and reason into `leave_requests` |
| Approval outcome | Load as a historical approval instance. The legacy status number is a source token, not the target’s approved value |
| Leave days | Load `leave_day_impacts` where the source day result shows that leave was applied on that date |
| Balances | A source row may be kept as a proposed position under LEAVE-CORE-005 and MIG-CORE-011. It is not a confirmed balance. Do not compute a new balance. Do not fill an empty variance with zero |
| Historical rows | Preserve. Do not re-approve them through a new step list |

Business validation is required when:

- the status number’s meaning is not confirmed for that company
- a request exists and the day result does not show the leave, or the reverse
- a balance exists but the requests do not explain it
- the opening figure, the leave period, the encashment amount, or the confirmer is unresolved
- the type’s pay effect is needed for a future payroll rule

Those rows wait in the exception register or are loaded as historical facts with the pay effect left empty. BD-D06 stays open. Both studied closed months left basic pay unchanged on leave days. That evidence does not become a paid-leave rule.

## 8. Correction and approval migration

Load a correction only from what the source row actually contains.

| Stage | When it can be loaded |
|---|---|
| Original | Only when the source still has the pre-approval value. If the legacy row overwrote it, the original stage is absent and the limitation is recorded |
| Requested | The value the request asked for, when present |
| Approved | The value an approval stored, when present |
| Final | The day result after the change, when the day actually changed and can be tied to the request |

Studied evidence:

- Some Viyellatex approvals replaced the displayed punch with a shift boundary. The raw shared-log timestamp, when it still exists, is the punch event. It is not updated. The displayed boundary is not written back onto `punch_events`. If the original displayed value was overwritten, original stays unknown.
- Some Proedge approvals stored times while the day stayed unchanged. The request loads. The day result is not rewritten to match the request.
- Approver fields that hold a name are not user ids. `acting_user_id` is the unknown marker. The name is kept on the exception or archive note.
- Status numbers differ by legacy alias. They are not converted to a universal approved state without a company-specific validation of that number’s meaning.
- Boss and grand-boss columns are not loaded as workflow steps.

A correction that cannot be tied to an employee and a date is an exception. It is not dropped.

## 9. OT migration

Three source meanings stay three target meanings. A legacy amount is not calculated OT. Attendance OT is not paid OT.

| Source evidence | What it actually is | Target | What it is not |
|---|---|---|---|
| Attendance OT quantity on the day | A calculated figure in a legacy unit, and only when the unit for that company is confirmed | `overtime_calculations`, with the unit labeled | Not payable OT and not money |
| Viyellatex whole hours beside a correction, or seconds in another client | Legacy calculated evidence | Load only after the unit is confirmed for that company. Otherwise exception | Not one universal unit |
| `employee_overtime` | A separate legacy table. Empty in the studied 2026 months | Do not invent payable rows from an empty table | Not proof of payable OT |
| `eligible_for_ot` | A flag that did not match either studied month | Do not load | Not eligibility |
| Payroll OT head amount | Money paid on that historical payroll | `payroll_result_heads` for that historical result | Not the attendance quantity, and not a rate |
| A printed rate that was not multiplied | Archive evidence | Not a target rate | Not BD-D12 |

If calculated evidence exists and paid money exists, both load, linked only when the employee and period match. They are allowed to differ. No formula, rounding, eligibility, or rate is derived from the pair.

When the unit or the meaning is unclear, preserve the source number in the archive reference and raise an exception. Do not choose seconds versus whole hours.

## 10. Payroll migration

Historical payroll is loaded as historical results. It is not recalculated.

| Object | Target treatment |
|---|---|
| Period | `payroll_periods` from the source month. The label and bounds are the source period |
| Run | One historical `payroll_runs` row with `attendance_usage = declares_no_attendance` when the source stored amounts only |
| Employee result | One `payroll_results` row per employee and period, state finalized, because it is a past result that was paid. Finalized actor is the unknown marker when the source has no user |
| Heads | `payroll_result_heads` for each stored earning and deduction, including tax or other heads the source actually stored |
| Net | The source net when present. Otherwise the sum of the stored heads, recorded as a derived check, not as a new payroll rule |
| OT payment | The OT head amount only, as paid OT |
| Payslip history | The loaded result is the historical payslip source. Legacy print layouts are not loaded as code |
| Lock tables | Not loaded as close or cutoff. A Yes flag is an exception for business confirmation. The studied months were not locked |

`payroll_result_inputs` for attendance stay absent on these historical rows. The absence is explicit. It is not filled by rereading punches. Optional snapshot lines are not invented to make the old result look complete.

A future payroll in the new product uses the new run. It does not rewrite these rows.

## 11. Legacy-only and ambiguous data

| Item | Class | Treatment |
|---|---|---|
| Company, department, designation, employee code, name | A. Direct migration | Load |
| Joining, separation, org facts, shift windows, holiday dates, punch-card map | B. Transform and migrate | Intervals, versions, and identities |
| `all_att_log` timestamps, day results, salary amounts | B. Transform and migrate | Preserve values. Do not reinterpret status or money |
| `area`, `balid` | H. Unsafe without clarification | Uninterpreted archive only. Not source event id, not device, not company |
| Legacy status numbers | G. Ambiguous | Company validation before any target state is set |
| `db_alias` | F. Legacy-only | Do not load |
| `previous_db_id` / `emp_unit` used as a tenant trick | F. Legacy-only | Do not load as company or policy |
| `employees.photo` weekday text | G. Ambiguous | Not a weekly-off rule until confirmed |
| Boss and grand-boss | G. Ambiguous | Chain evidence only. Not approval steps |
| `eligible_for_ot` | F. Legacy-only | Do not load |
| OT formulas, the 0.75 fraction, `basic/208×2` | F. Legacy-only | Do not load as policy |
| `employee_overtime` when empty | D. Migration evidence only | Record that it was empty. Do not synthesize rows |
| NMS payslip figures | E. Client extension | Off. Not company 20’s payslip. Not loaded as core attendance |
| Mobile GPS and unnamed device path | E. Client extension | Not loaded as the punch source of the studied months |
| Client payslip files | E. Client extension | Not loaded as schema |
| Month-lock Yes flags | G. Ambiguous | Not close or cutoff without confirmation |
| Approval name strings | G. Ambiguous | Not user ids |
| Destroyed pre-images of day results and overwritten correction originals | C. Historical archive | Limitation recorded. Not reconstructed |
| Same-second punches the source could not store | C. Historical archive | Loss happened before extract. Not invented back |
| Middle punches dropped by min/max processing | C. Historical archive | Not invented back |

## 12. Zero unexplained data loss

For every required object, the company baseline stores:

| Count | Meaning |
|---|---|
| Source count | Rows in the snapshot for that object |
| Transformed count | Rows the mapping emitted |
| Loaded count | Rows committed for this migration version |
| Rejected count | Rows the mapping refused |
| Exception count | Rows in the register |
| Approved exception count | Exceptions with a final disposition |
| Final accepted count | Loaded plus approved exceptions |

The continuity equation is:

```
SOURCE COUNT = LOADED + APPROVED EXCEPTIONS
```

Rejected rows must appear as exceptions. An exception without a disposition does not satisfy the equation. A disposition of “excluded” is allowed only when the reviewer records why the row is not required production data. That exclusion is visible. It is not a silent drop.

| Control | What must match |
|---|---|
| A. Record count | Equation above, per object |
| B. Distinct employees | Source people versus loaded people plus approved exceptions |
| C. Employee codes | Every source code accounted for. Active duplicates listed, not merged |
| D. Attendance days | Every source employee-day loaded or excepted. Status tokens preserved |
| E. Raw punches | Every source event attributed to the company, plus unattributed events as exceptions. Timestamps unchanged |
| F. Leave | Requests and applied days |
| G. Corrections | Every source request. Missing stages counted as limitations, not as dropped requests |
| H. OT | Calculated evidence and paid amounts reconciled separately |
| I. Payroll employees | Every employee with source pay |
| J. Payroll heads | Every source head row |
| K. Payroll amounts | Earnings, deductions, and net to the source currency unit |
| L. Historical periods | Each closed or paid period in scope, not only the latest month |

Shared punch storage is reconciled twice: once for events mapped to the company, and once for events left unmapped. Unmapped is not zero-loss until each event is loaded to the right company or approved as an exception.

## 13. Business reconciliation

Technical counts are necessary and not sufficient. Each company gets a reconciliation pack against its baseline. Metrics that the source does not have are marked “not in source,” not zero.

### Employees

Total employees. Active. Inactive or separated. Distinct employee codes. Duplicate active codes.

### Attendance

Employee-day counts. Counts of the source tokens for present, absent, leave, holiday, and weekly holiday, using that company’s own tokens. Incomplete or missing-punch evidence. Late and early evidence where the source stored a duration or a label. The pack does not translate tokens into a new status set.

### Leave

Applications. Approved and rejected as source outcomes, after that company’s status numbers have been validated. Leave days on the day results. Proposed balances where the source has them, with the period label, source evidence, and exception status. A proposed balance is not reported as a confirmed opening. Where `prev_bal`, `earned`, or `encashment` is present, each amount stays in its source column until LEAVE-OPENING is signed.

### Overtime

Calculated evidence totals, in the source unit. Payable evidence, only if a real payable store exists. Paid evidence as payroll money. The three totals are not required to be equal.

### Payroll

Employees processed. Salary heads. Total earnings. Total deductions. Net pay. OT paid. Other financial totals the source actually stored, such as tax, when those heads exist. Totals are by period.

The exact extra metrics can vary by company. A metric added for one company is part of that company’s certificate. It does not become a universal policy value.

## 14. Company migration certificate

A company reaches **MIGRATION APPROVED** only when all of the following are true:

- Technical validation passed for this snapshot and mapping version
- Business reconciliation passed
- Every exception is reviewed
- Unexplained missing records = 0
- An authorized business reviewer approves
- The migration version is recorded
- The production baseline id is recorded

Approval is company-scoped. It expires if a newer production cutoff is taken. The new cutoff needs its own delta reconciliation before cutover.

The certificate records the reviewer role, the time, the versions, and the list of approved exceptions. It does not choose D01–D18. This approval workflow is specified and not implemented.

Ready for Cutover is a later status. It requires the certificate plus the final delta in section 17.

## 15. Exception management

No automatic migration may discard a required row. The exception register is the only place a row can wait.

| Field | Required content |
|---|---|
| Company | The company being migrated |
| Source reference | Source system, source key, and object |
| Reason | Why it did not load cleanly |
| Severity | Blocks the certificate, or is a recorded limitation that can be approved |
| Resolution | What the reviewer decided |
| Resolver | The person or role |
| Resolution date | When |
| Final disposition | Load with limitation, map to an existing target row, or exclude as not required data |

Examples that must be register rows, not silent drops:

- duplicate active employee code
- duplicate active punch identity
- punch with no employee mapping
- punch that maps to more than one company
- invalid or impossible dates
- correction with no original value
- OT quantity whose unit is unknown
- payroll amount with no employee
- unknown legacy status number
- leave balance whose opening figure, period, or confirmer is unresolved
- encashment amount whose validation is unresolved
- leave-balance year label that does not match that company’s stored leave-year labels
- missing source key
- lock flag offered as proof of close
- photo text offered as a weekly off

A blocking exception keeps the company out of Approved. A limitation can be approved only when the reviewer accepts the preserved source value and the recorded gap.

## 16. Repeatable migration

```
Production Snapshot V1
→ Migration V1
→ Validation
→ Mapping fix
→ Migration V2
→ Validation
```

Dry runs are expected. Each run has a snapshot id and a mapping version. The target data for that company and that migration version is produced only by the run. Manual edits in the target are not part of the method. A fix changes the mapping or the exception disposition, then the run is repeated.

Repeating a run against the same snapshot replaces that version’s loaded rows for the company. It does not create a second unofficial copy. It does not update the legacy source.

Audit copies already on local machines may be used to rehearse mappings. A rehearsal is not a certificate. The certificate names the production snapshot.

## 17. Final delta and cutover

The freeze length is not decided here.

Before go-live for that company:

1. Define the production cutoff.
2. Capture the final read-only baseline.
3. Migrate all required data for that baseline.
4. Reconcile.
5. Resolve exceptions until unexplained missing records = 0.
6. Record MIGRATION APPROVED.
7. Capture the delta since the approved snapshot, still read-only.
8. Apply the delta through the same mapping version.
9. Reconcile the delta. Source count for the delta equals loaded plus approved exceptions.
10. Confirm zero unexplained differences against the final cutoff.
11. Authorize cutover for this company only.
12. New HRMS use starts for this company. Further legacy writes are outside this architecture and require a new delta, not a silent edit.

Ready for Cutover is step 11. Cutover Complete is step 12. Steps 7 through 10 repeat if the cutoff moves.

## 18. Parallel validation

During a parallel period, compare legacy and new results for the same employees and dates. The new product is not required to reproduce a legacy defect.

| Difference class | Meaning |
|---|---|
| Expected architectural difference | Separate punch and `work_date`, versioned results, snapshot payroll, no alias branch |
| Expected policy difference | A company has chosen a D01–D18 value that the legacy code did not use. The choice itself is still a business decision, not a migration output |
| Migration transformation | Intervals, unknown actors, preserved tokens, absent pre-images |
| Legacy defect | Duplicate cards, overwritten corrections, min/max punch loss, lock flags that were not operated, OT flag that did not match pay |
| New-system defect | A loaded value that does not match the preserved source, or a go-forward result that breaks an already approved rule |
| Unresolved business decision | Grace, incomplete punch, shift winner, leave pay, absence, OT rules, cutoff, reopen, correction fields, approval steps, snapshot lines |

Parallel payroll comparison uses the loaded historical amounts. It does not rerun old months through a new formula. Go-forward parallel periods, if the business runs them, are compared as new calculations and classified with the table above. A mismatch that is only “legacy did it this way” is not automatically a new-system defect.

## 19. Company-wise cutover

Cutover is per company.

```
Company A → Approved → Ready for Cutover → Cutover Complete
Company B → Business Reconciliation
Company C → Extracted
```

Company A’s users see Company A’s data. Company B’s data stays unloaded or unapproved and is not live. Shared punch extract filters by each company’s identity map. A punch that cannot be assigned stays in that company’s, or the shared extract’s, exception register. It is not given to a live company to make the live company’s counts look complete.

Rollback before Cutover Complete is a return to the legacy system for that company. The target version for that company can be discarded and rerun. Rollback does not edit production. After Cutover Complete, reversal is a business cutover decision, not a silent reload.

## 20. Historical data strategy

| History | Strategy |
|---|---|
| Payroll amounts | Load as finalized historical results. Do not recalculate with new rules |
| Attendance days | Keep the source date as `work_date` and the source status as a legacy token. Do not re-resolve the shift winner |
| Punch times | Keep the original timestamp |
| Punch-to-day links | Create only where the stored in or out matches an existing event |
| Missing pre-images | Record the limitation. Do not fabricate original punches or earlier result versions |
| Leave and corrections | Load what is present. Leave pay effect empty. Approval numbers unconverted until validated |
| OT | Load calculated evidence and paid money in separate places |
| Policy | Do not write `policy_values` from legacy code constants |
| Locks | Do not mark periods closed |

Material gaps require business approval on the certificate: destroyed originals, unmapped punches, unknown OT units, and unknown status numbers. Approval accepts the limitation. It does not fill the gap with a guessed value.

## 21. Migration safety

| Safety property | How it is met |
|---|---|
| Read-only production | Extract from a snapshot. No legacy update in the migration path |
| Repeatable | Snapshot id plus mapping version |
| Auditable | Baseline, run, exception, and certificate are retained |
| Reversible before cutover | Discard the company’s target version and rerun. Legacy stays unchanged |
| Versioned | Migration version on every loaded row’s run, via `source_system` and the run record |
| Company-scoped | One company per run result. `company_id` on every loaded fact |
| Validated | Technical equation and business pack |
| Exception-driven | No silent drop |
| No production change during design | This document does not connect to production or apply a load |

Development of migration tooling, when it later exists, still uses copies and snapshots. It does not patch live legacy rows to make them easier to load.

## 22. D01–D18 protection

Migration does not decide these. Historical source values may be stored as historical facts. They are not copied into `policy_values` as the company’s future rule.

| ID | Migration posture |
|---|---|
| BD-D01 Grace | Not derived from a legacy late label or from 16 minutes or from 117 seconds |
| BD-D02 Incomplete punch | Source evidence is preserved. The future outcome is not written |
| BD-D03 Shift winner | Candidates load. Historical day cites the shift the source stored. No new resolution rule |
| BD-D04 Late start | Stored durations stay durations. The start rule is not inferred |
| BD-D05 Early pay | Early evidence stays evidence. No deduction is created |
| BD-D06 Leave pay | Types load. Pay class stays empty |
| LEAVE-OPENING | Source balances stay proposed. No opening formula, leave year, or confirmer is inferred from a stored column. An `ENC` day count and an `earn_leave_encashment` net payable are not loaded as `employee_salary` |
| BD-D07 Absence | Historical deduction heads load as amounts. The formula is not inferred |
| BD-D08 Eligibility | The legacy flag is not loaded |
| BD-D09 Formula | OT quantities load only with a confirmed source unit. No formula |
| BD-D10 Rounding | No rounding rule is derived |
| BD-D11 OT approval | A punch correction is not loaded as an OT approval |
| BD-D12 Rate | Paid money loads as money. No rate is derived |
| BD-D13 Attendance cutoff | Lock flags are not cutoff |
| BD-D14 Payroll close | Lock flags are not close. Historical results are finalized as past facts, which is not a close policy |
| BD-D15 Reopen | Deferred. No reopen role is migrated |
| BD-D16 Correction fields | Only stages the source still has |
| BD-D17 Approval steps | No boss or grand-boss workflow is created |
| BD-D18 Snapshot lines | Historical results do not gain invented attendance inputs |

## 23. Readiness and next step

| Item | Assessment |
|---|---|
| Company-wise gate defined | YES |
| Live use blocked until that company’s reconciliation is approved | YES |
| Continuity equation defined | YES |
| Business reconciliation pack defined | YES |
| Repeatable dry runs defined | YES |
| Final delta and cutover defined | YES |
| Punch timestamps preserved | YES |
| Historical `work_date` preserved from the source day | YES |
| Go-forward `work_date` left to the target shift model without rewriting history | YES |
| Historical payroll not recalculated | YES |
| Production treated as read-only | YES |
| D01–D18 | OPEN. BD-D15 DEFERRED |
| Migration implementation | NOT STARTED |

The next step, when authorized, is a company-specific mapping workbook and a dry run against a read-only copy. That work is not started here. No company is MIGRATION APPROVED.

### Verification

| Check | Result |
|---|---|
| Company-wise migration is mandatory | Yes. Sections 3 and 19 |
| No company goes live without reconciliation approval | Yes. Sections 3 and 14 |
| No unexplained missing production records | Yes. Section 12 |
| Source-count reconciliation exists | Yes. Section 12 |
| Business-level reconciliation exists | Yes. Section 13 |
| Migration is repeatable | Yes. Section 16 |
| Final delta migration is defined | Yes. Section 17 |
| Historical data is preserved | Yes. Section 20 |
| Raw punch timestamps are preserved | Yes. Section 6 |
| Historical work date follows the stored source day. Go-forward work date follows the target shift model | Yes. Section 6 |
| Historical payroll is not silently recalculated | Yes. Section 10 |
| Production remains read-only | Yes. Sections 2 and 21 |
| D01–D18 remain open | Yes. Section 22 |
| Migration code created | No |
| Database modified | No |
