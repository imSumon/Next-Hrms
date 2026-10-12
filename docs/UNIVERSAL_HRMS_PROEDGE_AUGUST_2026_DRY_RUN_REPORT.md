# Universal HRMS Proedge August 2026 Dry-Run Report

Status of this rehearsal: **FAIL**

This is not migration approval. Proedge is not migration-approved. No production database, legacy database, or source row was modified. The final application database was not used.

Rehearsal timestamp: 2026-10-09 21:11:32, taken from the local database server clock before the baseline queries.

---

## 1. Executive Summary

The August 2026 Proedge baseline was read from `legacy_proedge` and `legacy_master`. It matches the closed-month audit on the two controlling counts: 1,370 attendance rows for 47 employees, and 540 salary rows for 45 employees.

The load did not start. The approved target schema is a design specification. It has no physical tables. This step forbids inventing a replacement schema, so nothing was extracted, transformed, or loaded.

An isolated database named `legacy_proedge_aug2026_dryrun` was created. It contains 0 tables.

For every required domain that has source rows, loaded count is 0 and approved exceptions are 0. The continuity equation fails. Unexplained difference equals the source count of those domains. No exception was created to hide that gap.

Final dry-run status: **FAIL**

---

## 2. Dry-Run Objective

Rehearse the approved Proedge mapping for company 24, attendance dates 2026-08-01 through 2026-08-31, into an isolated target, then reconcile with:

```
SOURCE COUNT = LOADED + APPROVED EXCEPTIONS
```

The objective included preserving employee codes as business attributes, punch timestamps as logged, historical attendance dates as `work_date`, legacy status tokens as tokens, and August payroll amounts as stored amounts.

The objective did not include a new schema, a policy decision, or a live migration.

---

## 3. Source and Target

| Role | Database | What this rehearsal did |
|---|---|---|
| Source company data | `legacy_proedge` | Read-only `SELECT` and `SHOW` |
| Catalog and shared punches | `legacy_master` | Read-only `SELECT` and `SHOW` |
| Isolated dry-run target | `legacy_proedge_aug2026_dryrun` | Created empty. 0 tables. No rows loaded |
| Final application database | `new_proedge_hrms` | Not present on this server. Not created. Not opened |

Catalog identity, read from `legacy_master.companies`:

| id | company_name | db_alias | db_name | previous_db_id |
|---|---|---|---|---|
| 24 | Pro-edge Associates Limited | `proedge` | `proedgea_newproedgehrm` | 5 |

`legacy_proedge.companies` is a different table. Its only row is id 5, name Pro-edge Associates Limited. That local id matches `previous_db_id`. It is not the target tenant. Catalog company 5 is UL VS Bangladesh Ltd., a different company. All 540 August salary rows store `company_id` 5. The mapping workbook already sends those rows to catalog company 24.

August scope used for the required-record list:

- Catalog company 24
- Employees who appear in August attendance or August payroll: 48 people
- Departments, designations, and branches those 48 reference
- The two shift definitions used on August days
- August holiday-calendar rows
- Punch-map rows for those employees
- August `all_att_log` events whose punch identity is in the Proedge punch map
- August `attendance_detail` rows
- Leave applications that overlap August
- August `attendance_approval_file` rows
- August `employee_salary` rows and the salary heads those rows use

Tables outside that set were inventoried and not treated as required August loads. Examples: provident-fund ledgers, bills, appraisal, recruitment, the 15 `employee_overtime` rows from 2008 through 2018, and the full 58,399 salary history.

---

## 4. Safety Controls

| Control | Result |
|---|---|
| Source access | `SELECT` and `SHOW` only on `legacy_proedge` and `legacy_master` |
| Legacy writes | None |
| Production writes | None |
| `new_proedge_hrms` | Not used |
| Target schema redesign | None |
| Replacement physical schema | Not created |
| Source-data correction | None |
| Manual target edits | None. The target has no tables to edit |
| D01–D18 | Left open. BD-D15 remains deferred |
| Exception approval | None granted |

The empty database is the only new object. It is not a schema implementation.

---

## 5. Source Baseline

Inventory relevant to the mapping, counted before any transformation:

| Object | Count | August 2026 note |
|---|---|---|
| `employees` | 160 | August union of attendance and payroll is 48 |
| `emp_unit` = 5 | 159 | Legacy key, same number as `previous_db_id` |
| `emp_unit` = 0 | 1 | Employee id 1, `emp_id_no` text `emp_id_no`, inactive. Not in the August 48 |
| `active` = 1 and `disconrinue` = 0 | 44 | |
| `active` = 1 and `disconrinue` = 1 | 19 | None of these 19 are in the August 48 |
| `active` = 0 and `disconrinue` = 1 | 91 | Includes the four August edge cases below |
| `active` = 0 and `disconrinue` = 0 | 6 | Includes employee id 1 |
| Duplicate `emp_id_no` groups | 0 | Active duplicates also 0 |
| `eligible_for_ot` = 1 | 5 | 155 are 0. Flag is legacy-only |
| `departments` | 6 | August 48 use 5. Missing department references: 0 |
| `designations` | 47 | August 48 use 21. Missing designation references: 0 |
| `company_branch` | 4 | August 48 use 4 |
| `att_shift` | 2 | Both used in August |
| `emp_shift_calendar` | 0 | August subset also 0 |
| `att_holiday_calendar` | 32 | August rows: 2 |
| `emp_code_to_punch_card` | 53 | 53 codes, 53 distinct cards, no duplicate card, no duplicate code |
| `attendance_detail` | 20,163 | August: 1,370 |
| `attendance_approval_file` | 49 | August: 16 |
| `hrm_leave_emp_app` | 2,524 | Overlap August: 30 |
| `hrm_leave_emp_balance` | 57 | Not established as the August payroll input |
| `leave_emp_balance` | 1,911 | Not established as the August payroll input |
| `employee_overtime` | 15 | Years 2008–2018. August 2026: 0 |
| `employee_salary` | 58,399 | August 2026: 540 |
| `salary_head` | 15 | August uses 12 |
| `attendance_month_lock` | 0 | |
| `salary_month_lock` | 0 | |

`employees.photo`: `Friday,Saturday` on 103, `Friday` on 55, empty on 2.

`employees.weekly_holiday`: `Friday` on 143, blank on 9, null on 7, and the literal text `weekly_holiday` on 1. Rows where `photo` and `weekly_holiday` are both the text `Friday,Saturday`: 0. The two columns disagree. Neither was chosen.

Shift definitions:

| id | name | start | end | next_day | punch_start_time | punch_end_time |
|---|---|---|---|---|---|---|
| 1 | General (9:00 AM-5:00 PM) | 09:00:00 | 17:00:00 | 0 | 06:00:00 | 23:50:00 |
| 2 | General (8:00 AM-5:00 PM) | 08:00:00 | 17:00:00 | 0 | 06:00:00 | 23:50:00 |

`punch_start_time` and `punch_end_time` are real source columns. The mapping workbook already says a real punch window is mapped when the extract finds it. `absent_after` is 16:30:00 on both shifts. That column was not given a grace or late meaning.

August attendance:

| Measure | Count |
|---|---|
| Rows | 1,370 |
| Employees | 47 |
| Distinct dates | 31 |
| First date | 2026-08-01 |
| Last date | 2026-08-31 |
| Rows whose employee id is missing | 0 |
| Rows whose `shift_id` differs from `employees.shift_id` | 0 |
| Shift id 1 | 1,213 |
| Shift id 2 | 157 |

| attendance_status | Rows |
|---|---|
| Present | 717 |
| Weekly Holiday | 342 |
| Absent | 180 |
| Holiday | 88 |
| Leave | 43 |

| day_status | Rows |
|---|---|
| Blank | 1,002 |
| Late | 213 |
| Late, Early Out | 46 |
| EL | 20 |
| Early Out | 20 |
| Out punch missing | 16 |
| Out punch missing, Late | 16 |
| In punch missing | 14 |
| CL | 14 |
| SL | 7 |
| BL | 2 |

August holiday calendar:

| date | note | affected_shift_id |
|---|---|---|
| 2026-08-05 | July Mass Uprising Day | 1,2 |
| 2026-08-26 | Eid e-Miladunnabi | 1,2 |

Each of those dates has 44 Holiday attendance rows. The three August attendees with no row on either date are `10238`, `10518`, and `10325`. 44 + 44 = 88, which is the Holiday token count. The source does not contain the six missing employee-days. They are not deleted rows.

August payroll:

| Measure | Count |
|---|---|
| Rows | 540 |
| Employees | 45 |
| Heads used | 12 |
| `company_id` | 5 on all 540 rows |
| Unresolved head ids | 0 |
| Earnings rows | 360 |
| Earnings amount | 2,737,016.78 |
| Deduction rows | 180 |
| Deduction amount | 163,175.16 |
| Derived net check | 2,573,841.62 |

The derived net is earnings minus deductions. `employee_salary` has no net column. The check is not a new pay rule.

| Head id | Name | Kind | Rows | Amount |
|---|---|---|---|---|
| 1 | Basic | Earnings | 45 | 1,082,343.38 |
| 2 | House Rent | Earnings | 45 | 541,171.69 |
| 3 | Conveyance Allowance | Earnings | 45 | 270,585.89 |
| 4 | Personal Allowance / LFA | Earnings | 45 | 703,523.20 |
| 5 | Over Time | Earnings | 45 | 6,158.25 |
| 7 | Others | Earnings | 45 | 25,000.00 |
| 8 | Income Tax | Deduction | 45 | 69,831.61 |
| 9 | Adjustment | Deduction | 45 | 23,333.33 |
| 12 | Festival Bonus | Earnings | 45 | 0.00 |
| 13 | Personal Loan | Deduction | 45 | 0.00 |
| 14 | PF | Deduction | 45 | 70,010.22 |
| 16 | Medical Allowance | Earnings | 45 | 108,234.37 |

Non-zero Over Time rows: 6 employees, amount 6,158.25. The other 39 Over Time rows are stored zeros.

August population edges:

| emp_id_no | employees.id | In August attendance | In August payroll | Punch card | active | disconrinue |
|---|---|---|---|---|---|---|
| 10238 | 114 | Yes, except 5 Aug and 26 Aug | No | Yes | 0 | 1 |
| 10518 | 260 | Yes, except 5 Aug and 26 Aug | No | Yes | 0 | 1 |
| 10325 | 261 | Yes, 2 days, no punch id and no first punch | No | No | 0 | 1 |
| 10326 | 262 | Yes, 31 days, no punch id and no first punch | Yes | No | 1 | 0 |
| 10327 | 263 | No | Yes | No | 0 | 1 |

Attendance employees not in payroll: 3. Payroll employees not in attendance: 1. Union: 48.

---

## 6. Extraction Results

Extraction was not started.

The pre-flight gate failed first: the approved target schema has not been physically implemented. Inventing `CREATE TABLE` statements would have been a replacement schema. The step forbids that.

No staging rows exist. Source identifiers were not copied anywhere, because there was no staging database structure to receive them.

---

## 7. Transformation Results

Transformation was not started.

No employee code was turned into a primary key. No punch timestamp was rewritten. No attendance date was recomputed. No status number was given a universal meaning. No payroll amount was recalculated. `balid`, `area`, `db_alias`, `previous_db_id`, and `eligible_for_ot` were not given new meanings.

The punch-window columns found in `att_shift` remain source evidence for a later run. They were not loaded.

---

## 8. Load Results

Load was not started.

`legacy_proedge_aug2026_dryrun` has 0 tables and 0 loaded rows. No target record was edited.

---

## 9. Domain-by-Domain Reconciliation

Loaded = 0. Approved exceptions = 0. Rejected = 0, because rejection did not run. Unexplained difference = source count wherever the source count is greater than 0.

| Domain | Source | Transformed | Loaded | Rejected | Approved exceptions | Unexplained difference |
|---|---:|---:|---:|---:|---:|---:|
| Catalog company 24 | 1 | 0 | 0 | 0 | 0 | 1 |
| August-scope employees | 48 | 0 | 0 | 0 | 0 | 48 |
| Departments referenced by those employees | 5 | 0 | 0 | 0 | 0 | 5 |
| Designations referenced by those employees | 21 | 0 | 0 | 0 | 0 | 21 |
| Branches referenced by those employees | 4 | 0 | 0 | 0 | 0 | 4 |
| Shift definitions used in August | 2 | 0 | 0 | 0 | 0 | 2 |
| August holiday-calendar rows | 2 | 0 | 0 | 0 | 0 | 2 |
| Punch identities for the 48 | 45 | 0 | 0 | 0 | 0 | 45 |
| August punch events on the Proedge punch map | 3,257 | 0 | 0 | 0 | 0 | 3,257 |
| August attendance rows | 1,370 | 0 | 0 | 0 | 0 | 1,370 |
| Leave applications overlapping August | 30 | 0 | 0 | 0 | 0 | 30 |
| August correction rows | 16 | 0 | 0 | 0 | 0 | 16 |
| August salary rows | 540 | 0 | 0 | 0 | 0 | 540 |
| Salary heads used in August | 12 | 0 | 0 | 0 | 0 | 12 |
| August roster rows | 0 | 0 | 0 | 0 | 0 | 0 |
| August `employee_overtime` rows | 0 | 0 | 0 | 0 | 0 | 0 |
| Payable OT store for August | 0 | 0 | 0 | 0 | 0 | 0 |

Sum of unexplained differences on domains with source rows: **5,353**.

That sum counts each required source row once. Attendance OT quantities and the Over Time salary head sit inside the attendance and salary counts. They are not added again.

Empty domains satisfy the equation only because both sides are zero. They do not make the rehearsal pass.

Weekly-off text, lock tables, `eligible_for_ot`, leave-balance tables, and `employee_overtime` history from 2008–2018 are not in the sum. The mapping does not load them as August results. They are recorded in sections 15, 16, and 18. They were not converted into approved exceptions.

---

## 10. Employee Reconciliation

| Check | Source evidence | Loaded |
|---|---|---|
| Master file | 160 employees | 0 |
| August required people | 48 | 0 |
| Duplicate codes | 0 | — |
| Identity | `emp_id_no` is the business code. `employees.id` is the legacy key. Neither became a target primary key, because no target row exists | — |
| Local company id 5 versus catalog id 24 | Confirmed. Not remapped in a target | — |
| Active and discontinued both true | 19 in the master, 0 inside the August 48 | — |
| Template-like row | Employee id 1, code text `emp_id_no`, `emp_unit` 0 | Not in the August 48 |

The 48 source employees are unexplained missing required records. The other 112 master employees are outside this August required set. They are not approved exclusions. A later full-history run has to count them on its own baseline.

---

## 11. Punch and Identity Reconciliation

| Check | Source evidence | Loaded |
|---|---|---|
| Punch-map rows | 53, no duplicate card, no duplicate code | 0 |
| Map rows for the August 48 | 45 | 0 |
| August 48 with no card | 3: `10325`, `10326`, `10327` | No source map row exists for them |
| August events whose log identity is in the Proedge map | 3,257 | 0 |
| `company` null on those events | 3,213 | — |
| `company` populated on those events | 44 | Value not read as the tenant |
| `balid` populated | 3,253 | Meaning not assigned |
| `area` populated | 3,209 | Meaning not assigned |
| `update_datetime` null | 3,257 | Not used as the punch time |

`10326` has 31 August attendance rows, no punch card, no `punch_id`, and no first punch. `10325` has 2 such rows. Those days have no raw-punch evidence in the mapped log. Punches were not invented.

The 3,257 events are unexplained missing required records. The 45 map rows are unexplained missing required records. The three employees without cards remain employees in the employee count. There is no map row to count for them.

---

## 12. Attendance Reconciliation

| Check | Source | Loaded | Unexplained |
|---|---:|---:|---:|
| Rows | 1,370 | 0 | 1,370 |
| Employees | 47 | 0 | Covered by the 48-employee domain |
| Dates | 31, 2026-08-01 through 2026-08-31 | 0 | — |
| Present | 717 | 0 | Inside the 1,370 |
| Weekly Holiday | 342 | 0 | Inside the 1,370 |
| Absent | 180 | 0 | Inside the 1,370 |
| Holiday | 88 | 0 | Inside the 1,370 |
| Leave | 43 | 0 | Inside the 1,370 |
| Shift references | All 1,370 resolve to shift 1 or 2 and match `employees.shift_id` | 0 | — |
| Late label | 213 Late, plus 46 Late and Early Out, plus 16 Out punch missing and Late | 0 | — |
| Early label | 20 Early Out, plus 46 combined with Late | 0 | — |
| Missing punch text | 16 out missing, 16 out missing and late, 14 in missing | 0 | — |
| Leave tokens on the day | EL 20, CL 14, SL 7, BL 2 | 0 | — |
| `ot_hours` filled | 50 rows, 3 employees | 0 | Inside the 1,370 |
| `ot_hours` blank or zero | 1,320 | 0 | Inside the 1,370 |

The historical date on each row remains the baseline fact. It was not rewritten, because it was not loaded. A future load must copy `attendance_detail.date` to `work_date`.

Status words stay source tokens. They were not translated into a new vocabulary.

---

## 13. Leave Reconciliation

Applications with a range that overlaps 2026-08-01 through 2026-08-31:

| status | Applications |
|---|---:|
| 3 | 29 |
| 1 | 1 |
| Total | 30 |
| Distinct employees | 21 |
| Applications whose employee id does not exist | 0 |

All 30 are unexplained missing required records.

Status 3 and status 1 were not converted to a target approval state. August attendance contains 43 Leave rows. The application count and the Leave-day count are different measures. This rehearsal did not join each application day to each Leave row, because transformation did not run. That join remains work for a later rehearsal. It is not a reason to invent impacts now.

`hrm_leave_emp_balance` has 57 rows. `leave_emp_balance` has 1,911 rows. The approved mapping loads a balance only when that store is the payroll input. August payroll has no attendance or leave snapshot. These balance tables were not added to the required August equation and were not zeroed.

---

## 14. Correction Reconciliation

August `attendance_approval_file`:

| employee_id value | employees.id | emp_id_no | status | Rows |
|---|---|---|---|---:|
| 10318 | 250 | 10318 | 1 | 1 |
| 10318 | 250 | 10318 | 4 | 15 |

Total 16 rows, one employee code. The code matches `employees.emp_id_no` for id 250. It was not treated as a user id.

All 16 are unexplained missing required records.

No correction was written onto a punch. Requested times, status numbers, and the historical day were not transformed. The earlier audit found that the sampled August days stayed Absent. This rehearsal did not re-check each of the 16 against the day, because the load never started. A later run still has to keep the request and the day result as separate facts.

---

## 15. OT Reconciliation

The three figures were kept apart in the baseline. None were loaded.

| Figure | August 2026 source evidence | Loaded |
|---|---|---|
| Calculated OT | `attendance_detail.ot_hours` is filled on 50 rows across 3 employees. The closed-month audit identified this company's stored unit as seconds after shift end. This rehearsal did not convert the 50 values | 0 |
| Payable OT | No separate payable store. `eligible_for_ot` = 1 on 5 of 160 employees and was not treated as payable quantity | 0 |
| Paid OT | Salary head 5, Over Time. 45 stored rows. 6 employees have a non-zero amount. Total 6,158.25 | 0 |
| `employee_overtime` | 0 rows for August 2026. 15 rows exist from 2008 through 2018 and are outside this period | 0 |

50 filled day quantities and 6 paid employees are not the same population. 6,158.25 was not compared with the seconds and was not regenerated. The inequality remains a source fact. It is not a migration error and not a decision on BD-D08, BD-D09, BD-D10, BD-D11, or BD-D12.

---

## 16. Payroll Reconciliation

| Check | Source | Loaded | Unexplained |
|---|---:|---:|---:|
| Salary rows | 540 | 0 | 540 |
| Employees paid | 45 | 0 | Inside the 48-employee domain |
| Heads used | 12 | 0 | 12 |
| Earnings | 2,737,016.78 | 0 | Inside the 540 |
| Deductions | 163,175.16 | 0 | Inside the 540 |
| Derived net check | 2,573,841.62 | 0 | Not a stored source net |
| OT payment | 6,158.25 inside head 5 | 0 | Inside the 540 |
| `company_id` on the salary row | 5 | Not written as tenant 5 | Must become catalog 24 on a later load |
| August payroll regenerated | No | No | — |

Head amounts in section 5 are the historical stored results. Zero rows for Festival Bonus and Personal Loan are stored rows. A later load keeps them. It does not drop them to make the file look smaller.

`salary_month_lock` and `attendance_month_lock` have 0 rows. August was not marked closed.

One discontinued employee, `10327`, has August salary and no August attendance. Three discontinued employees have August attendance and no August salary. The missing side was not created.

---

## 17. Data Integrity Results

These checks were run against the source, because the target has no rows.

| Check | Result |
|---|---|
| Company isolation of the target | The dry-run database exists and is empty. No other company's rows were loaded into it |
| Catalog tenant versus salary key | Catalog 24 is the tenant. Salary `company_id` 5 is `previous_db_id`. Catalog company 5 is a different company |
| Duplicate employee codes | 0 |
| Duplicate punch cards | 0 |
| August attendance employee exists | 1,370 of 1,370 |
| August attendance shift exists and matches the employee shift | 1,370 of 1,370 |
| August salary head exists | 540 of 540 |
| August-scope department and designation references | 0 missing |
| August leave employee exists | 30 of 30 |
| August correction employee code exists | 16 of 16, code `10318` |
| Target orphans, duplicate target identities, null target keys | Not applicable. No target tables |
| Source-to-target traceability | Not established. Extraction did not start |
| Punch events with a populated `company` column | 44 of 3,257. Not used as a tenant |
| Employees in the August 48 with no punch card | 3 |

No integrity failure inside the target was possible. The blocking failure is the absent physical schema.

---

## 18. Exception Register

No exception was approved. The register is empty on purpose.

The following source conditions were observed. They are not dispositions. A later rehearsal may turn a confirmed one into a register row. This rehearsal does not.

| Company | Source | Reference | Domain | Reason | Severity if a later run reaches it | Class |
|---|---|---|---|---|---|---|
| 24 | `companies` in `legacy_proedge` | id 5 | Company | Local company id is 5. Catalog tenant is 24 | Would block a load that used 5 as the tenant | MAPPING ISSUE already decided by the workbook: target tenant is 24 |
| 24 | `employee_salary` | August 2026, all 540 rows | Payroll | `company_id` 5 | Same as above | MAPPING ISSUE |
| 24 | `employees` | id 1, code `emp_id_no` | Employee | Code text is the column name. `emp_unit` 0. Not in the August 48 | Data condition outside this month's required set | DATA ISSUE |
| 24 | `employees` | 19 rows | Employee | `active` = 1 and `disconrinue` = 1. None are in the August 48 | Ambiguous episode status when a full master load is in scope | DATA ISSUE |
| 24 | `employees` | `photo` and `weekly_holiday` | Weekly off | The columns disagree. One master value is the text `weekly_holiday` | Must not become a rule without confirmation | BUSINESS DECISION / EVIDENCE GAP |
| 24 | `emp_code_to_punch_card` | absent for `10325`, `10326`, `10327` | Punch identity | No source map row | Missing reference when those employees are loaded | DATA ISSUE |
| 24 | `all_att_log` | 3,253 of 3,257 August mapped events | Punch | `balid` populated | Unsafe to interpret | LEGACY-ONLY FIELD / EVIDENCE GAP |
| 24 | `all_att_log` | 3,209 events | Punch | `area` populated | Unsafe to interpret | LEGACY-ONLY FIELD / EVIDENCE GAP |
| 24 | `all_att_log` | 44 events | Punch | `company` is not null | Not a tenant key | EVIDENCE GAP |
| 24 | `hrm_leave_emp_app` | status 3 and 1 | Leave | Numbers are source tokens | Must not become a universal state | BUSINESS DECISION |
| 24 | `attendance_approval_file` | status 1 and 4 | Correction | Numbers are source tokens | Must not become a universal state | BUSINESS DECISION |
| 24 | `att_shift` | `absent_after` | Shift | Stored clock on both shifts | Must not become grace | LEGACY-ONLY FIELD |
| 24 | `employees` | `eligible_for_ot` | OT | Does not define payable OT | Must not load as eligibility | LEGACY-ONLY FIELD |

Creating any of these as an approved exception would have made the equation look closer to balanced without a load. That was not done.

---

## 19. Unresolved Issues

| Issue | Class |
|---|---|
| No physical tables exist for the approved target schema | TARGET SCHEMA ISSUE |
| This step forbids inventing those tables | TOOLING/ENVIRONMENT ISSUE |
| 5,353 required source rows have no target row and no approved exception | Consequence of the schema gate, not a source defect |
| Weekly-off column disagreement | BUSINESS DECISION |
| Leave and correction status numbers | BUSINESS DECISION |
| `balid`, `area`, and the 44 non-null punch `company` values | EVIDENCE GAP |
| Calculated OT seconds versus paid OT money | CONFIGURATION / BUSINESS DECISION. The difference is expected. It is not a migration defect |
| D01–D18 | BUSINESS DECISION. Untouched |
| August attendance versus payroll population difference of 3 and 1 | DATA ISSUE to preserve, not to repair |
| `10326` has a full August attendance grid and no punch evidence | DATA ISSUE |
| Leave-day impacts were not matched application by application | The match was not run. It is unfinished analysis, not a discovered mismatch |
| Local audit copy is not a production baseline | EVIDENCE GAP for any future certificate |

No issue in this list was reclassified as a migration error just because Proedge's historical behavior differs from an open policy.

---

## 20. Lessons Learned

The baseline can be counted without a target schema. The August audit counts held: 1,370 attendance rows, 47 attendance employees, 540 salary rows, 45 payroll employees, Over Time total 6,158.25 on 6 non-zero employees.

A load rehearsal cannot pass by staging into a made-up table list. The continuity equation needs the approved target entities.

`att_shift` already has `punch_start_time` and `punch_end_time`. A later Proedge load can map those columns to the punch window. It does not need a schema change, and it must not copy `absent_after` into grace.

The local company id, the salary `company_id`, and `emp_unit` all repeat 5. The catalog tenant remains 24. That trap is visible before any load.

Employee code uniqueness holds in this copy. Punch-card uniqueness holds in this copy. The hard gaps are missing cards, disagreeing weekly-off fields, and the absent physical schema.

---

## 21. Required Changes Before Real Migration

1. Implement the approved target schema physically, in an isolated database, without adding or removing entities. This rehearsal did not do that.
2. Point the next dry run at that physical schema. Keep `new_proedge_hrms` out of the rehearsal.
3. Repeat extraction, transformation, and load. Preserve `source_system` and `source_key` on every loaded row.
4. Map Proedge punch windows from `punch_start_time` and `punch_end_time`. Leave `absent_after` out of policy.
5. Send every August salary row to catalog company 24. Do not create catalog company 5.
6. Leave weekly-off text, leave status numbers, and correction status numbers unconverted until a Proedge reviewer confirms them.
7. Leave `balid`, `area`, and punch `company` uninterpreted.
8. Load the 50 OT day quantities and the 6,158.25 OT head as separate facts. Do not reconcile them into one number.
9. Load attendance and payroll independently. Do not create the three missing salary sets or the one missing attendance set.
10. Take the certificate baseline from a read-only production snapshot. This local copy cannot certify go-live.
11. Leave D01–D18 open.

None of these changes were applied in this step.

---

## 22. Final Dry-Run Status

**FAIL**

The equation does not hold for the required August domains. Loaded is 0. Approved exceptions are 0. Unexplained difference is the source count, totaling 5,353 required source rows. Empty domains were not used to declare a pass.

Proedge is not migration-approved. Real migration was not started.

### Safety statement

| Item | Result |
|---|---|
| Production modified | NO |
| Legacy source modified | NO |
| Final `new_proedge_hrms` modified | NO |
| Dry-run target used | `legacy_proedge_aug2026_dryrun` |
| Source count reconciliation passed | NO |
| Unexplained missing required records | 5,353 |
| Migration approved | NO |

### Verification

| Check | Result |
|---|---|
| Source database is `legacy_proedge` | Yes |
| Company is catalog 24 | Yes |
| Period is August 2026 | Yes |
| Source access was read-only | Yes |
| Target schema was not redesigned | Yes |
| Replacement schema was not invented | Yes |
| Payroll was not regenerated | Yes |
| Punch timestamps were not changed | Yes. They were not loaded |
| Historical attendance dates were not reinterpreted | Yes |
| D01–D18 were not decided | Yes |
| Status is exactly FAIL, PASS, or PASS WITH APPROVED EXCEPTIONS | FAIL |
