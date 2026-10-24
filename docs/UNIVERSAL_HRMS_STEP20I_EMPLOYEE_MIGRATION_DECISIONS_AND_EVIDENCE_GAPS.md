# Universal HRMS — Step 20I Employee Migration Decisions and Evidence Gaps

Documentation only. The rules in this file are the current proposed direction. They are not implemented, and they are not a universal approval of load behavior. No company is migration-approved.

No database connection was opened. No dump, schema, configuration, or migration file was changed.

Statuses:

- **PROPOSED** — recorded here as the working direction. Not an implemented rule.
- **APPROVED** — already stated as approved in an earlier project document. This step does not add a new approval.
- **UNRESOLVED** — investigated, and the evidence still does not settle it.
- **TECHNICAL VERIFICATION REQUIRED** — a named source still has to be read.

## 1. Documents reviewed

- `docs/UNIVERSAL_HRMS_STEP20B_EMPLOYEE_LIFECYCLE_DESIGN_REVIEW.md`
- `docs/UNIVERSAL_HRMS_STEP20C_EMPLOYEE_LIFECYCLE_IMPLEMENTATION_REPORT.md`
- `docs/UNIVERSAL_HRMS_STEP20D_EMPLOYEE_LIFECYCLE_INTEGRITY_REVIEW.md`
- `docs/UNIVERSAL_HRMS_STEP20E_EMPLOYEE_MIGRATION_MAPPING_AND_RECONCILIATION_DESIGN.md`
- `docs/UNIVERSAL_HRMS_STEP20F_LEGACY_EMPLOYEE_SOURCE_SCHEMA_VERIFICATION.md`
- `docs/UNIVERSAL_HRMS_STEP20G_LEGACY_EMPLOYEE_IDENTITY_AND_EXCEPTION_AUDIT.md`
- `docs/UNIVERSAL_HRMS_STEP20H_EMPLOYEE_MIGRATION_BLOCKER_AND_DECISION_REGISTER.md`
- `docs/UNIVERSAL_HRMS_STEP20_FINAL_EMPLOYEE_CODE_DECISION.md`
- `docs/UNIVERSAL_HRMS_MULTI_COMPANY_MIGRATION_MAPPING_WORKBOOK.md` section 3
- `docs/UNIVERSAL_HRMS_PROEDGE_AUGUST_2026_DRY_RUN_REPORT.md`
- `docs/UNIVERSAL_HRMS_THIRD_REFERENCE_COMPANY_CLOSED_MONTH_AUDIT.md`
- Legacy `Employees.php` attribute labels, the employee CSV import, and `leave_emp_annual_leave`
- Local catalog dump `legacy-data/master/proedgea_payrollmaster.sql` (header database `proedgea_payrollmaster`, file date 2026-10-02)

Employee row counts remain the Step 20G offline dump census. They are not a live-database census.

## 2. Proposed rules D1–D7

### D1. Employee display name — PROPOSED

Trim whitespace from each nonempty legacy name component. Join the remaining first, middle, and last name components with one space, in that order. Do not invent a name. If every component is empty or `NULL`, quarantine the record for manual review.

Evidence: Step 20G. Target `display_name` is required. No earlier document defines this composition. Yii `getName()` is screen text, not this rule. VTL `employees.id` 437, code `PAL1234`, has all three components empty and would be quarantined.

Further approval required before a load uses it: an explicit approval of D1 as a load rule, plus a reviewer disposition for every quarantined empty name.

### D2. MySQL zero dates — PROPOSED

Treat `0000-00-00` as its own legacy exception. It is not a valid target date and it is not ordinary `NULL`. Keep the original source value and the source-row reference on the exception. Do not invent a replacement date. When the date is required for an episode, quarantine the episode. Mapping an optional date to `NULL` is allowed only under a later mapping rule that names that conversion and keeps the exception traceable.

Evidence: Step 20G counted `0000-00-00` separately from `NULL`. Joining-date zeros: Proedge 1, Atlas 3, VTL 61. Leaving-date zeros: Proedge 1, Atlas 0, VTL 81. Target `joining_date` is `date NOT NULL` (Step 20C).

Further approval required: acceptance of D2 as a load rule. Under this proposal, a zero `discontinue_date` stays an exception even though `last_working_date` may be null for other reasons. A rule that stores those zeros as `NULL` is not included here.

### D3. Both `active` and `disconrinue` equal 1 — PROPOSED

Do not infer `Open`, `inactive`, or `separated` from this pair. Quarantine the lifecycle status. Keep both source flags and the relevant dates for review. Do not infer status from `discontinue_date` alone.

Evidence: Step 20G dump counts, not a live census. Proedge 19 rows, Atlas 74 rows, VTL 1,186 rows. Atlas `EB9091` is `employees.id` 7175, both flags 1, `discontinue_date` 2026-08-25. Step 20E already quarantines this pair. D3 records that quarantine as the proposed handling and adds that the leaving date does not by itself choose a status.

Further approval required: acceptance of D3 before any both-true episode is written. September attendance and salary for `EB9091` remain documented in the third-company audit and were not re-read. They are not required to apply this quarantine.

### D4. VTL duplicate employee codes — PROPOSED

Quarantine each unresolved duplicate-code group. Keep every source row traceable. Do not merge identities or silently recode employee codes. Load an affected row only after reliable source evidence and explicit authorization for that group.

Evidence: Step 20G. Seven `emp_id_no` groups, 14 rows: `10024`, `15575`, `132058`, `6119`, `10023`, `10027`, `10034`. The no-merge and no-recode principles are already **APPROVED** in the employee-code decision and Step 20E. D4 adds the proposed quarantine of the whole group until each row is authorized.

Further approval required: acceptance of group quarantine as the load behavior, then a disposition for each of the 14 rows. Step 20G section 11 is the worksheet. All seven groups remain unresolved. No owner, date, or cited evidence is recorded.

### D5. Legacy `dis` — UNRESOLVED

Not a proposed business rule. Do not use `dis` for identity or lifecycle status.

Evidence is in section 4. The meaning is still unresolved. Further evidence would be a documented writer or a business definition that states what the integer means in each company. None was found in the code reviewed here.

### D6. Employment episodes — PROPOSED

Create an episode only when source evidence supports that employment event and the dates and status map under an approved rule. Do not infer episodes from department, designation, location, or other attribute changes. Do not fabricate history so that counts match. When history cannot be mapped safely, keep the source record and write an explicit exception.

This proposal does not add date-range overlap rules, a `last_working_date` versus `joining_date` order check, or pay behavior for `inactive`. Those three remain **UNRESOLVED**, as in Step 20B.

Evidence: Step 20E already limits one current legacy row to at most one episode and forbids attribute-change episodes. `employment_history` is text. No episode table was confirmed (Steps 20F and 20G). D6 records that limit as the proposed load behavior.

Further approval required: acceptance of D6 before episodes are written. Overlap, date order, and inactive pay stay out of this load.

### D7. Company and tenant mapping — APPROVED rule, dump rows now read

The tenant is the catalog company. Dump-local `companies.id` and `employee_salary.company_id` are not the tenant. Do not treat an id from one database as the same tenant as the same number in another database.

The mapping rule itself is **APPROVED** in the workbook and Step 20E. This step read the local catalog dump and records the rows in section 4. That read is not a new business approval. It is file evidence for the rule that was already approved.

A live re-read of `legacy_master` was not done. The dump is not a live census.

## 3. Decision-status matrix

| Item | Current recommendation | Status | Evidence | Still required |
| --- | --- | --- | --- | --- |
| D1 Display name | Trim, drop empty parts, join with one space, quarantine a fully empty name | **PROPOSED** | Step 20G name census. No prior composition rule | Explicit approval before load |
| D2 Zero dates | Distinct exception. Not a target date. Not ordinary `NULL`. Quarantine a required episode date | **PROPOSED** | Step 20G zero-date counts. Target `joining_date` is `date NOT NULL` | Explicit approval before load |
| D3 Both flags 1 | Quarantine status. Do not infer `Open`, `inactive`, `separated`, or a status from `discontinue_date` | **PROPOSED** | Step 20G counts and `EB9091`. Step 20E quarantine | Explicit approval before any both-true episode is written |
| D4 Duplicate codes | Quarantine the group. No merge. No recode | **PROPOSED** as the group hold. No-merge is already **APPROVED** | Step 20G groups. Employee-code decision | Approval of the hold, then a disposition for each of the 14 rows |
| D5 `dis` | Do not use it for identity or status | **UNRESOLVED** meaning | Section 4 | A definition, if any later domain needs the column |
| D6 Episodes | One supported event only. No attribute-change history. No invented rows | **PROPOSED** | Steps 20E, 20F, 20G | Explicit approval before episode load |
| Overlap, date order, inactive pay | Leave undecided. Do not add checks or pay rules | **UNRESOLVED** | Step 20B | A later decision before a load that needs them |
| D7 Tenant | Catalog 24, 20, and 10 are the tenants. Local 5 and 26 are not | **APPROVED** as the rule. Row text is now read from the local catalog dump | Workbook, Step 20E, section 4 | Live `legacy_master` only if a gate requires a server re-read |
| Identity | New ULID. `source_key` is legacy `employees.id`. `employee_code` is exact `emp_id_no` | **APPROVED** | Employee-code decision. Steps 20C and 20G | Execution, not a new rule |
| Reconciliation | `SOURCE COUNT = LOADED + APPROVED EXCEPTIONS`, employees and episodes separately | **APPROVED** as the contract | Step 20E | It has not been executed |

## 4. Technical evidence findings

### D5 — `employees.dis`

Physical definition, from the three company dumps in Step 20G: `int DEFAULT '0'`, not indexed, not the primary key. Stored values in those dumps are `NULL`, `0`, and, on two Proedge rows, `1`.

Application code:

- `Employees::attributeLabels()` sets the label to `Earn Leave Balance` when the session alias is `vtl`, `vtlw`, or `vtlnms`. Every other alias gets the label `Dis`.
- The employee form shows a text input when the company field list includes `dis`. The column is commented out of the employee grid.
- The CSV import writes spreadsheet column index 43 onto `dis`. The import skips the header row and does not record that header’s text, so the spreadsheet title is not in the code.
- A PMS detail view prints `Yes` or `No` from the same `Employees` model. That display is not a stored definition.
- The menu item “Employee Earn Leave Balance” opens `leave/leaveempannualleave`. That screen uses `leave_emp_annual_leave`, whose balance columns are `prev_bal`, `earned`, `used`, `encashment`, and `balance`. It is a different table from `employees.dis`.

The VTL label and the leave ledger are different objects. The label does not establish that `employees.dis` stores earn-leave balance. No reviewed code path calculates lifecycle status from `dis`.

**UNRESOLVED** as a business meaning. Sufficient to keep it out of identity and episode mapping.

### D7 — Catalog ids and dump-local ids

Source: one `INSERT` into `companies` in `proedgea_payrollmaster.sql`. The parser accepted 61 rows of 14 fields. Columns used here are `id`, `previous_db_id`, `company_name`, `db_name`, and `db_alias`. The table also has credential columns. Those values were not extracted and are not recorded here.

| Catalog `id` | `company_name` | `db_alias` | `db_name` | `previous_db_id` |
| --- | --- | --- | --- | ---: |
| 5 | UL VS Bangladesh Ltd | `ulvs` | `proedgea_payroll_aug_13` | 19 |
| 10 | Atlas Copco Bangladesh Ltd. | `atlas` | `proedgea_atlas` | 26 |
| 20 | Viyellatex Group | `vtl` | `proedgea_vtlhrm` | 5 |
| 24 | Pro-edge Associates Limited | `proedge` | `proedgea_newproedgehrm` | 5 |
| 26 | Grameen Telecom Trust (GTT) Non-Clinical | `telenor` | `proedgea_payroll_aug_13` | 37 |

This file evidence matches the approved tenant map: catalog 24, 20, and 10, with databases `proedgea_newproedgehrm`, `proedgea_vtlhrm`, and `proedgea_atlas`. The dry-run report’s shorter phrase “UL VS Bangladesh Ltd.” and the third-company audit’s shorter phrase “Grameen Telecom Trust” refer to these same catalog ids. The dump stores the names in the table above.

`previous_db_id` 5 is not unique. Besides catalog 20 and 24, this insert also stores 5 on catalog ids 21 (VTL Staff), 22 (Interfab Shirt Manufacturing Ltd.), 23 (Pledge Harbor International School), 33 (PMS DEMO), 37 (VIYELLATEX SPINNING LTD. Workers), 51 (Poshora), and 55 (NREE DEMO). No other parsed row has `previous_db_id` 26. Catalog id 10 is the Atlas row whose `previous_db_id` is 26. Catalog id 26 is a different company, and its own `previous_db_id` is 37.

Step 20G dump-local `companies` rows sit in the company databases, not in this catalog:

| Company dump | Local `companies.id` | Local name |
| --- | ---: | --- |
| Proedge | 5 | Pro-edge Associates Limited |
| VTL | 5 | Viyellatex Group |
| Atlas | 26 | Atlas Copco Bangladesh Ltd. |

Those local ids match the catalog `previous_db_id` of catalog 24, 20, and 10. They are not catalog ids. The same local number 5 is Proedge in one database and Viyellatex in another. Atlas local id 26 matches Atlas `previous_db_id`, and it is the same number as catalog id 26, which this dump names as Grameen Telecom Trust (GTT) Non-Clinical.

Step 20G also showed Atlas `employee_salary.company_id` taking many values, with 26 as one of them. That column is not the tenant. Salary key 5 on Proedge and VTL rows matches a shared `previous_db_id`, which this catalog assigns to more than one company.

This is dump evidence from 2026-10-02 catalog and company files. It is not a live `legacy_master` census. Laragon copies were not compared.

## 5. Remaining blockers

- D1, D2, D3, and D6 are **PROPOSED**. A load that uses them still needs an explicit approval. This document is that proposal, not that approval.
- D4’s 14 VTL rows have no per-row authorization.
- D5’s meaning is **UNRESOLVED**. The column stays out of identity and status.
- Overlap, date order, and inactive pay stay **UNRESOLVED** and out of this load.
- Empty-name, zero-date, both-true, duplicate-code, and template-row exceptions from Step 20H are undisposed.
- `companies.source_system` is still unnamed. The column is nullable. Employee `source_system` values are already specified.
- Step 20G counts and this catalog extract describe dump files. They do not describe a running database.
- No company is migration-approved.

## 6. Dry-run readiness assessment

The evidence is sufficient to **design** a non-production employee dry run. It is not sufficient to **run** one.

A design can now state:

- Identity: new ULID, `source_key` = legacy `employees.id`, `employee_code` = exact `emp_id_no`, `source_system` = `legacy_proedge`, `legacy_atlas`, or `legacy_vtlhrm`. **APPROVED** and not yet executed.
- Tenant: catalog 24, 20, or 10 in `companies.source_key`. Dump-local 5 and 26 stay out of that key. **APPROVED**, with catalog rows read from the local master dump.
- Traceability: every loaded row and every exception row carries the source system and source key.
- Counts: employees and episodes use separate equations. Duplicate codes count as two source rows. Dump counts are labeled as dump counts.
- Exceptions: D1–D4 and D6, if later approved, quarantine empty names, zero dates on required episode fields, both-true statuses, duplicate-code groups, and unmapped history. No merge and no silent recode.
- Validation: `SOURCE COUNT = LOADED + APPROVED EXCEPTIONS`, repeated in a second pass before any approval. Quarantine is not the right-hand side. Quarantine must be zero before final approval. A dry run may still report a nonzero quarantine.

The design must label D1–D4 and D6 as proposed assumptions. Running the dry run would apply those assumptions to target rows. That run waits on an explicit approval of those four rules, a named extract (these dumps, or the Laragon copies after a later read-only compare), and an exception register. This step does not authorize that run.

## 7. Recommended next step

Approve or reject D1, D2, D3, D4, and D6 as load rules. Leave D5 unmapped. Leave overlap, date order, and inactive pay unchanged. After approval, build the exception register from the Step 20G dump counts and the catalog rows in section 4, without loading employees and without marking a company migration-approved.

BUSINESS DECISIONS STILL REQUIRED
