# Step 20E Employee Migration Mapping and Reconciliation Design

Date: 2026-10-10. Revised the same day after Steps 20F through 20J.

Documentation only. No database, migration file, configuration, or source system was changed. Reconciliation was not executed. No company is migration-approved.

The first text of this file left the Atlas and VTL physical column names unresolved. Steps 20F and 20G then read the local dumps and named those columns. This revision records that evidence. It does not rerun a count and it does not approve a load. Proposed rules D1 through D6 in Step 20I are not approved load rules.

Labels:

- **APPROVED** — an existing approved document states the rule.
- **INFERRED** — a procedure needed to operate an approved rule, not a new business fact.
- **UNRESOLVED** — the documents do not settle the decision.
- **NOT VERIFIED** — a count or match has not been produced against the target database. A dump census is a source finding, not this class of target check.

## 1. Sources reviewed

- `docs/UNIVERSAL_HRMS_STEP20D_EMPLOYEE_LIFECYCLE_INTEGRITY_REVIEW.md`
- `docs/UNIVERSAL_HRMS_STEP20B_EMPLOYEE_LIFECYCLE_DESIGN_REVIEW.md`
- `docs/UNIVERSAL_HRMS_STEP20C_EMPLOYEE_LIFECYCLE_IMPLEMENTATION_REPORT.md`
- `docs/UNIVERSAL_HRMS_STEP20_FINAL_EMPLOYEE_CODE_DECISION.md`
- `docs/UNIVERSAL_HRMS_TARGET_DATABASE_SCHEMA_SPECIFICATION.md` sections 4.1, 4.3, and 4.4, as cited by Steps 20B and 20C
- `docs/UNIVERSAL_HRMS_MIGRATION_DATA_CONTINUITY_ARCHITECTURE.md`
- `docs/UNIVERSAL_HRMS_MULTI_COMPANY_MIGRATION_MAPPING_WORKBOOK.md`
- `docs/UNIVERSAL_HRMS_MULTI_COMPANY_MIGRATION_MAPPING_VALIDATION.md`
- `docs/UNIVERSAL_HRMS_THIRD_REFERENCE_COMPANY_CLOSED_MONTH_AUDIT.md`
- `docs/UNIVERSAL_HRMS_PROEDGE_AUGUST_2026_DRY_RUN_REPORT.md`
- `docs/UNIVERSAL_HRMS_STEP20F_LEGACY_EMPLOYEE_SOURCE_SCHEMA_VERIFICATION.md`
- `docs/UNIVERSAL_HRMS_STEP20G_LEGACY_EMPLOYEE_IDENTITY_AND_EXCEPTION_AUDIT.md`
- `docs/UNIVERSAL_HRMS_STEP20I_EMPLOYEE_MIGRATION_DECISIONS_AND_EVIDENCE_GAPS.md`
- `docs/UNIVERSAL_HRMS_STEP20J_EMPLOYEE_MIGRATION_DRY_RUN_DESIGN.md`

No database connection was opened for this revision.

## 2. Source-to-target mapping matrix

The target primary key is a new ULID. The employee code is not that key. `source_system` plus `source_key` traces one legacy row. Legacy `employees.id` is the source primary key in all three dumps. Legacy `emp_id_no` is the business code. Those two columns were confirmed from the dump `CREATE TABLE` statements in Steps 20F and 20G. Confirming the column is not a new approval of a load.

| Source | Target | Rule | Class |
| --- | --- | --- | --- |
| Catalog `companies.id` 24, 20, or 10 | `companies.source_key`, with a new `companies.id` | One target company per catalog id. Name copied from catalog `company_name` | **APPROVED** |
| Catalog `db_alias` and `previous_db_id` | No target column | Alias does not select behavior. Salary `company_id` 5 or 26 is not a second tenant | **APPROVED** |
| `legacy_proedge`, `legacy_vtlhrm`, `legacy_atlas` | `employees.source_system` | Names the legacy database that held the row | **APPROVED** |
| `employees.id` in each company dump | `employees.source_key` | Integer primary key, copied as text. Not the target `id` | **APPROVED** as the role. Column confirmed for Proedge, Atlas, and VTL |
| `employees.emp_id_no` | `employees.employee_code` | Exact text. Not reassigned. Not the identity | **APPROVED** as the copy rule. Column confirmed for all three. `varchar(50)`, nullable, not unique |
| `emp_first_name`, `emp_mid_name`, `emp_last_name` | `employees.display_name` | A non-empty name is required. How the three columns combine is not approved | **APPROVED** as the role. Composition is **UNRESOLVED** (D1 is proposed only) |
| `employees.joining_date` | `employment_episodes.joining_date` | One episode for that known span. The date is not invented | **APPROVED** as the role. Column confirmed. `0000-00-00` handling is **UNRESOLVED** |
| `employees.discontinue_date` | `employment_episodes.last_working_date` | Copy a real calendar date when an approved pair says to copy it. Empty when the source has none and the pair allows that | **APPROVED** for Atlas. The same column exists on Proedge and VTL. Use on a conflicting flag pair is **UNRESOLVED** |
| `employees.emp_unit`, dump-local `companies.id`, salary `company_id` | No target tenant column | Local 5 is Proedge in one dump and Viyellatex in another. Atlas local 26 is not catalog 26 | **APPROVED** |
| `employees.old_id` | No identity column | Yes/No, empty, or `NULL`. Not unique | Not a source key. Meaning beyond that form is unused |
| `employees.dis` | No identity or status column | Integer. Meaning **UNRESOLVED** | Do not map |
| Department, designation, branch, shift, boss, and grand-boss changes | Not a new `employment_episodes` row | Later assignment or reporting tables | **APPROVED** |

Company link:

| Catalog id | Name | Source database | `employees.source_system` | `previous_db_id` in the catalog dump | Dump-local `companies.id` |
| ---: | --- | --- | --- | ---: | ---: |
| 24 | Pro-edge Associates Limited | `proedgea_newproedgehrm` | `legacy_proedge` | 5 | 5 |
| 20 | Viyellatex Group | `proedgea_vtlhrm` | `legacy_vtlhrm` | 5 | 5 |
| 10 | Atlas Copco Bangladesh Ltd. | `proedgea_atlas` | `legacy_atlas` | 26 | 26 |

Catalog id 5 is UL VS Bangladesh Ltd., `previous_db_id` 19. Catalog id 26 is Grameen Telecom Trust (GTT) Non-Clinical, `previous_db_id` 37. Step 20I read those rows from `proedgea_payrollmaster.sql`. That file is not a live census. Target `companies.id` values do not exist yet. This design does not invent ULIDs.

`source_system` on the company row itself is **UNRESOLVED**. The column is nullable. Employee `source_system` does not depend on it.

Dump source counts, from the Step 20G parse, **NOT VERIFIED** against `proedge_new_hrms`:

| Dump | Rows | Distinct `emp_id_no` |
| --- | ---: | ---: |
| Proedge | 160 | 160 |
| Atlas | 151 | 151 |
| VTL | 1,420 | 1,413 |

## 3. Exception and quarantine matrix

Automatic mapping is prohibited for every row below. No identity is merged. No code is recoded. A quarantined row is not an approved exception until a named reviewer records a disposition. Until then it blocks that company’s migration approval. Quarantine is not the right-hand side of the reconciliation equation.

| Exception | Detection | Evidence required | Automatic mapping | Resolution | Effect |
| --- | --- | --- | --- | --- | --- |
| Duplicate employee code in one company | Two or more rows in one source database share one exact `emp_id_no` | Every source key, the code, and the company. VTL has 7 groups and 14 rows. Proedge and Atlas have 0 groups in the Step 20G dumps | Prohibited. Do not drop either row and do not assign a new code | Reviewer disposition for each source row. Group quarantine is **proposed** (D4), not an approved load rule | Both rows stay in the source count. Neither loads until each disposition is approved |
| Empty display name | All of `emp_first_name`, `emp_mid_name`, and `emp_last_name` are `NULL` or empty. VTL `employees.id` 437, code `PAL1234`, is the known dump case | Source key and code | Prohibited. Do not invent a name. Composition of partial names is **UNRESOLVED** | Reviewer supplies the name or approves exclusion, after D1 is approved | The employee is not loaded while the name is empty |
| Missing joining date | `joining_date` is `NULL`. VTL has 6 such rows in the dump. Proedge and Atlas have 0 | Source key and code | Prohibited. Do not invent a date | Reviewer supplies a real date or approves exclusion of the episode | The identity may still load. The episode does not |
| Zero date `0000-00-00` | The token is stored and is not `NULL`. Joining-date zeros in the dump: Proedge 1, Atlas 3, VTL 61 | Source key, the original token, and which column | Prohibited. Do not store it as a target date and do not treat it as ordinary `NULL` unless a later approved rule says so | D2 is **proposed**. Reviewer keeps the token on the exception | A required episode date stays quarantined |
| Both `active` and `disconrinue` are 1 | Dump counts: Proedge 19, Atlas 74, VTL 1,186. Atlas `EB9091` is `employees.id` 7175, `discontinue_date` 2026-08-25 | Source key, both flags, both date classes. September attendance and missing September pay for `EB9091` stay **DOCUMENTED ONLY** from the third-company audit | Prohibited. Do not choose `Open`, `inactive`, or `separated`. Do not infer status from `discontinue_date` alone | D3 is **proposed** quarantine. A reviewer disposition is still required before a status is stored | Identity may load. Episode status stays unmapped |
| `active` = 0 and `disconrinue` = 0 | Proedge dump: 6 rows, including `employees.id` 1 | Source key and both flags | Prohibited. Do not store `inactive` | Reviewer holds the episode | No episode status |
| Discontinued, not active, leaving date missing | `active` = 0, `disconrinue` = 1, and `discontinue_date` is null or zero. The pair counts exist. The leaving-date split of that pair was not counted | Source key, flags, and date class | Prohibited until a reviewer accepts `separated` with a null `last_working_date` | **UNRESOLVED** | Episode quarantined. The identity may still load |
| Ambiguous company | Salary `company_id`, `emp_unit`, dump-local `companies.id`, or `db_alias` is used as the tenant | Catalog id and the rejected key | Prohibited | The row stays on catalog 24, 20, or 10 | A row left on tenant 5 or 26 fails the company gate |
| Duplicate or missing source identity | Duplicate or null `employees.id` in one dump | The key values | Prohibited | Reviewer identifies the surviving row | Step 20G found 0 null and 0 duplicate ids in these dumps. That is a dump finding, **NOT VERIFIED** on a later extract |
| Suspicious Proedge code | `employees.id` 1, `emp_id_no` text `emp_id_no`, flags 0/0, joining date `0000-00-00`, `emp_unit` 0 | Dump row, confirmed in Step 20G | Prohibited as an automatic person | Reviewer decides whether it is a template row | It stays in a full-file source count |
| More than one joining span | A second source row or a history table for the same person | Evidence the second span is the same identity | Prohibited to invent it from a department, designation, branch, or shift change | Reviewer confirms a rejoin of the same `source_key`, or a different person | Same target employee and code receive another episode only when that evidence exists |
| Attendance after `last_working_date` | A stored attendance day is later than the leaving date | The day and the date | The day is not deleted and does not by itself change episode status | Listed with the episode exception | `EB9091` is the documented Atlas example. The September facts were not re-read in Step 20G |

`employment_history` text and `epf_service_book_second_part` are not episode sources. A row that cannot map to an episode under an approved pair stays in the episode equation as quarantined. It is not omitted.

## 4. Employee episode mapping rules

One current legacy employee row supports at most one episode: the joining date through the leaving date when those values exist. It does not reconstruct earlier rejoins. Employee identity remains stable across rejoining. A confirmed rejoin uses the same `employees.id` and the same `employee_code`, with a new episode. The employee table has no status column.

| Source evidence | Episode result | Class |
| --- | --- | --- |
| `active` = 1, `disconrinue` = 0, `joining_date` is a real calendar date | One episode, `Open`, `last_working_date` null | **APPROVED** |
| `active` = 0, `disconrinue` = 1, real joining date, real leaving date | One episode, `separated`, `last_working_date` copied | **APPROVED** |
| `active` = 0, `disconrinue` = 1, real joining date, leaving date empty | `separated` with null `last_working_date` only after a reviewer accepts it | **UNRESOLVED** |
| Both flags 1 | No episode status | **APPROVED** quarantine. D3’s written hold is **proposed** |
| Both flags 0, or any other pair | No episode status | **UNRESOLVED**. Do not store `inactive` |
| `joining_date` or `discontinue_date` is `0000-00-00` | Not a target date | **UNRESOLVED** (D2 proposed) |
| Rejoin confirmed as the same source identity | Same employee and code, previous episode retained, new episode inserted | **APPROVED** when the second span is evidenced. No episode table was confirmed |
| Department, designation, location, or shift change | No new episode | **APPROVED** |
| Second `Open` episode for the same employee | Rejected by the target unique index | **APPROVED** physical rule |

`Open` is the stored token for the active case. `inactive` is a legal token and is not inferred from an unclear flag pair. Date-range overlap, whether `last_working_date` must fall on or after `joining_date`, and the pay effect of `inactive` stay **UNRESOLVED**. This design does not add those rules. D6, which says not to fabricate history, is **proposed** and is not an additional approval.

## 5. Company-wise reconciliation contract

Run the contract separately for each catalog company. Do not add the three companies into one total.

For employees, and again for episodes:

```text
SOURCE COUNT = LOADED + APPROVED EXCEPTIONS
```

| Count | Meaning |
| --- | --- |
| Source count | Every in-scope source row. A duplicate code is two source rows, not one. For these dumps the employee source counts are 160, 1,420, and 151. The episode source count is the same set of rows, because each current row needs an episode decision |
| Loaded count | Rows inserted in the target for that company. This count is 0. No load has run |
| Quarantined count | Rows with no approved disposition. This count must be zero before approval. It is not part of the right-hand side |
| Approved-exception count | Rows a reviewer disposed as excluded or held out, with a written reason. An undisposed row is not in this count |

An employee can be loaded while that person’s episode remains quarantined. A row with no joining date, a zero date, or an unresolved status pair is an episode exception or still quarantined. It is not silently omitted.

Traceability for a loaded employee is `source_system` + `source_key` → target `employees.id`. Traceability for an exception is the same source key → one exception-register row. A source row that appears in neither list fails the contract.

A dry run that writes no target rows uses a different report, specified in Step 20J: `SOURCE COUNT = PROPOSED LOAD + QUARANTINED + APPROVED EXCEPTIONS`, with `LOADED = 0`. A proposed load is not a loaded row. A quarantine is not an approved exception.

This reconciliation has not been executed. Independent validation has not been run. The Step 20G figures are dump findings, not a reconciliation result.

## 6. Migration-readiness approval checklist

A company is not migration-ready until every line is true. None of the three companies meets this list.

| Gate | Required evidence | Current state |
| --- | --- | --- |
| Source identity | Physical primary key and employee-code column | `employees.id` and `employees.emp_id_no` for Proedge, Atlas, and VTL. Confirmed from the dumps |
| Company mapping | Catalog 24, 20, or 10 in `companies.source_key`. Keys 5 and 26 are not tenants | Rule **APPROVED**. Catalog rows read from the master dump in Step 20I. Target company ULIDs are not loaded. **NOT VERIFIED** as a target load |
| Source inventory | Extract counts for employees and for episode decisions, taken on the declared extract | Step 20G dump counts exist. They are **NOT VERIFIED** against the target or against a later live copy |
| Exception register | Every quarantined row has a reviewer, a disposition, and a reason | Not created. D1–D4 and D6 are proposed, not approved |
| Traceability | Each source key maps to one target id or one approved exception | **NOT VERIFIED** |
| Employee equation | Source = loaded + approved exceptions, quarantine = 0 | **NOT VERIFIED**. Loaded = 0 |
| Episode equation | Same equation, counted separately | **NOT VERIFIED** |
| Independent check | A second pass repeats both equations from the register and the target keys | **NOT VERIFIED** |
| Business sign-off | A named reviewer accepts the register for that company | Not given |

No company is marked approved.

## 7. Unresolved decisions and unknown source mappings

- Display-name composition (D1, proposed).
- Whether `0000-00-00` is a distinct exception (D2, proposed).
- Status for both flags true (D3, proposed). The prohibition on inferring `inactive` is already in force.
- Per-row authorization of the seven VTL duplicate groups (D4 hold is proposed; no merge is **APPROVED**).
- Episode creation when history is only the current row (D6, proposed).
- `active` = 0, `disconrinue` = 1, and no calendar leaving date.
- Which pair, if any, may become `inactive`.
- Episode date-range overlap, date order, and the pay effect of `inactive`.
- Meaning of `employees.dis`.
- `companies.source_system`.
- Target `companies.id` ULIDs. They are not invented here.
- Whether the Step 20G dump counts match the stopped Laragon copies. **NOT VERIFIED**.

Closed since the first text of this file: Atlas and VTL primary key `employees.id`, code `emp_id_no`, name columns, `joining_date`, `discontinue_date`, and the VTL spelling `disconrinue`.

## 8. Recommended next step

Approve or reject D1, D2, D3, D4, and D6 as load rules. Then build the exception register from the Step 20G dump rows for catalog companies 24, 20, and 10. Do not load rows. Do not approve a company. Do not add assignment, attendance, leave, overtime, or payroll tables.

DESIGN COMPLETE
