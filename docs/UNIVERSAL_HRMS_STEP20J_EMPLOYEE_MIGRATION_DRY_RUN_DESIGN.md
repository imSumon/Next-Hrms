# Universal HRMS — Step 20J Employee Migration Dry-Run Design

Design and execution specification only. No dry run was executed. No target row was written. No company is migration-approved.

D1, D2, D3, D4, and D6 stay **PROPOSED**. This design uses them as named gates. It does not approve them and it does not choose a final outcome for a quarantined row.

## 1. Documents and source artifacts reviewed

- `docs/UNIVERSAL_HRMS_STEP20B_EMPLOYEE_LIFECYCLE_DESIGN_REVIEW.md`
- `docs/UNIVERSAL_HRMS_STEP20C_EMPLOYEE_LIFECYCLE_IMPLEMENTATION_REPORT.md`
- `docs/UNIVERSAL_HRMS_STEP20D_EMPLOYEE_LIFECYCLE_INTEGRITY_REVIEW.md`
- `docs/UNIVERSAL_HRMS_STEP20E_EMPLOYEE_MIGRATION_MAPPING_AND_RECONCILIATION_DESIGN.md`
- `docs/UNIVERSAL_HRMS_STEP20F_LEGACY_EMPLOYEE_SOURCE_SCHEMA_VERIFICATION.md`
- `docs/UNIVERSAL_HRMS_STEP20G_LEGACY_EMPLOYEE_IDENTITY_AND_EXCEPTION_AUDIT.md`
- `docs/UNIVERSAL_HRMS_STEP20H_EMPLOYEE_MIGRATION_BLOCKER_AND_DECISION_REGISTER.md`
- `docs/UNIVERSAL_HRMS_STEP20I_EMPLOYEE_MIGRATION_DECISIONS_AND_EVIDENCE_GAPS.md`
- `docs/UNIVERSAL_HRMS_STEP20_FINAL_EMPLOYEE_CODE_DECISION.md`
- `docs/UNIVERSAL_HRMS_TARGET_DATABASE_SCHEMA_SPECIFICATION.md`, as cited by Steps 20B and 20E
- `docs/UNIVERSAL_HRMS_MIGRATION_DATA_CONTINUITY_ARCHITECTURE.md`, as cited by Step 20E
- `docs/UNIVERSAL_HRMS_MULTI_COMPANY_MIGRATION_MAPPING_WORKBOOK.md` section 3

Source dumps, paths confirmed in Step 20G:

| Company | Path | Header database | File size | Last write |
| --- | --- | --- | ---: | --- |
| Proedge | `D:\Project -All\Proedge HRMS\legacy-data\companies\proedgea_newproedgehrm.sql` | `proedgea_newproedgehrm` | 10,534,148 | 2026-10-02 |
| Atlas | `D:\Project -All\Proedge HRMS\legacy-data\companies\proedgea_atlas.sql` | `proedgea_atlas` | 164,919,794 | 2026-10-02 |
| VTL | `D:\Project -All\Proedge HRMS\legacy-data\companies\vtl\proedgea_vtlhrm.sql.gz` | `proedgea_vtlhrm` | 105,425,407 | 2026-10-09 |

Catalog dump used for the crosswalk in Step 20I: `D:\Project -All\Proedge HRMS\legacy-data\master\proedgea_payrollmaster.sql`, header database `proedgea_payrollmaster`.

Counts below are the Step 20G offline parse. This step did not re-parse the dumps and does not replace those counts.

| Dump | Employee rows | Distinct `emp_id_no` |
| --- | ---: | ---: |
| Proedge | 160 | 160 |
| Atlas | 151 | 151 |
| VTL | 1,420 | 1,413 |

These figures are dump findings, not a live-database census.

## 2. Tenant crosswalk and target company mapping

Approved crosswalk. The tenant is the catalog id. A dump-local `companies.id` is not a tenant. `employee_salary.company_id` is not a tenant. The same number in two databases is not one company.

| Catalog id | Company | Alias | Source database | `previous_db_id` | Employee `source_system` |
| ---: | --- | --- | --- | ---: | --- |
| 24 | Pro-edge Associates Limited | `proedge` | `proedgea_newproedgehrm` | 5 | `legacy_proedge` |
| 20 | Viyellatex Group | `vtl` | `proedgea_vtlhrm` | 5 | `legacy_vtlhrm` |
| 10 | Atlas Copco Bangladesh Ltd. | `atlas` | `proedgea_atlas` | 26 | `legacy_atlas` |

Catalog id 5 is UL VS Bangladesh Ltd., `previous_db_id` 19. Catalog id 26 is Grameen Telecom Trust (GTT) Non-Clinical, `previous_db_id` 37. Neither is a tenant for this dry run. `previous_db_id` 5 is also stored on other catalog rows. It does not select Proedge or Viyellatex by itself.

Target `companies.id` is a ULID. Step 20D recorded the company tables empty of loaded companies. No ULID is assigned in this design.

Prerequisite, before any later execution can mark an employee proposed-loadable: three target `companies` rows exist in the non-production database, each with `source_key` equal to catalog `24`, `20`, or `10`, and each with a real ULID. The dry-run artifact stores `catalog_company_id` now and leaves `target_company_ulid` empty until that prerequisite is met. Inventing a ULID in this document is forbidden.

`companies.source_system` remains unnamed and nullable. The employee `source_system` values above do not depend on it.

## 3. Approved target model used by the design

Implemented tables, unchanged by this step:

- `companies`
- `employees`: ULID `id`, `company_id`, `employee_code`, `display_name`, nullable `source_system`, nullable `source_key`, `created_at` and `updated_at` as `timestamptz`. No `employees.status`.
- `employment_episodes`: ULID `id`, `company_id`, `employee_id`, `joining_date` `date NOT NULL`, nullable `last_working_date`, `episode_status` in `Open`, `inactive`, `separated`. An `Open` episode has a null `last_working_date`. Composite foreign key `(company_id, employee_id)` to `employees (company_id, id)`. At most one `Open` episode per employee.

`UNIQUE (company_id, employee_code)` applies to every employee row. A code is not reused inside a company. Separation does not release it. The employee code is not the identity. Legacy `employees.id` is `source_key`. The target primary key is a new ULID.

Already approved episode pairs, from Step 20E, still subject to the date gates below:

| Source pair | Episode result |
| --- | --- |
| `active` = 1 and `disconrinue` = 0, and `joining_date` is a real calendar date | One episode, `Open`, `last_working_date` null |
| `active` = 0 and `disconrinue` = 1, real `joining_date`, real `discontinue_date` | One episode, `separated`, `last_working_date` copied |
| `active` = 0 and `disconrinue` = 0 | No episode status. Do not store `inactive` |
| Both flags 1 | No episode status until D3 is approved. The proposal is quarantine |

A real calendar date here means a `YYYY-MM-DD` value other than `0000-00-00`. `NULL` and `0000-00-00` are not that value. Whether the zero token is a distinct exception is D2, still proposed.

`dis` is not read for identity or status. Overlap, `last_working_date` versus `joining_date` order, and the pay effect of `inactive` stay unresolved and are not checks in this pipeline. The pipeline must not emit `inactive`.

## 4. Pipeline stages and data flow

The pipeline reads dumps and writes a dry-run artifact outside the target database. Default run mode is `gates_closed`.

In `gates_closed`, stages classify every source row and emit no `proposed_load`. Switching a gate to `approved` is an explicit execution input recorded in the artifact header. This document does not switch any gate.

1. **Fingerprint.** For each dump, record path, byte size, last-write time, header `Database:` name, and a SHA-256 of the file bytes. For the gzip, hash the compressed file, then decode it in a stream. Do not decompress in place. Refuse to continue if the path, header database, or size differs from section 1 unless the run record explains the new fingerprint. Do not modify the file.
2. **Parse.** Read `CREATE TABLE employees` and `INSERT` tuples. Accept a tuple only when its field count matches that dump’s column list. Keep `id`, `emp_id_no`, `emp_first_name`, `emp_mid_name`, `emp_last_name`, `joining_date`, `active`, `disconrinue`, `discontinue_date`. Ignore `dis`. Do not keep salary amounts, addresses, or national identifiers in the artifact.
3. **Provenance.** On every output row store `source_database`, `source_table` = `employees`, `source_pk` = `employees.id`, `source_file_sha256`, and `source_system`.
4. **Crosswalk.** Set `catalog_company_id` from the file: Proedge 24, VTL 20, Atlas 10. Set `target_company_ulid` from the prerequisite company map. If that ULID is absent, identity classification may continue and `proposed_load` stays blocked for that company.
5. **Code and name.** Classify `emp_id_no` and the three name fields using the definitions in section 6. Apply D1 and D4 only as gates.
6. **Duplicates and ambiguous identity.** Group exact non-empty `emp_id_no` inside one dump. A duplicate group quarantines every row in the group. Source primary keys in these dumps were unique in Step 20G. A null or duplicate `source_pk` is an ambiguous identity and is not loadable.
7. **Dates and flags.** Classify `joining_date` and `discontinue_date` as `null`, `zero`, `calendar`, or `other`. Classify the flag pair. Apply D2 and D3 only as gates.
8. **Episode evidence.** One current row can support at most one episode, and only for an approved pair with a calendar `joining_date`. Do not read `employment_history`. Do not create an episode from department, designation, or location. Apply D6 only as a gate. The `0`/`1` pair with no calendar leaving date stays unresolved (Step 20E) and is quarantined, not mapped to `separated`.
9. **Proposed ULID.** For every row with a unique `source_pk`, generate a proposed employee ULID and, where an episode is classified, a proposed episode ULID. Store them only in the artifact. A ULID is `proposed_loadable` only when the company ULID exists, the code is unique, the name gate allows a display name, and the row is not in a duplicate group. Otherwise the ULID is `proposed_not_loadable`. Distinct source rows keep distinct ULIDs. Duplicate codes are not merged. No ULID is written to `employees` or `employment_episodes`.
10. **Reports.** Write the artifact: run header, one employee classification per source row, one episode classification per source row, exception groups, and the reconciliation summary. Do not open the target database.
11. **Reconcile.** Apply section 7. A second pass repeats the arithmetic from the artifact alone. The run fails if the two passes disagree.

`created_at` and `updated_at` are not written. A later authorized load must supply `timestamptz` values at insert time. The artifact’s `classified_at` is report metadata, not a target column.

## 5. Proposed mapping and exception data structures

These are artifact records, not database tables.

### Run header

| Field | Meaning |
| --- | --- |
| `run_id` | New identifier for this classification, not a target key |
| `mode` | `gates_closed` unless an execution input says otherwise |
| `gate_d1` … `gate_d6` | `proposed` or `approved`. This design sets every gate to `proposed` |
| `source_fingerprints` | The three dump fingerprints |
| `catalog_fingerprint` | Path and SHA-256 of the master dump used for the crosswalk |
| `target_database_write` | `false` |

### Employee classification, one per source row

| Field | Meaning |
| --- | --- |
| `catalog_company_id` | 24, 20, or 10 |
| `target_company_ulid` | Empty until the company prerequisite exists |
| `source_system`, `source_database`, `source_table`, `source_pk`, `source_file_sha256` | Provenance |
| `source_emp_id_no` | Exact text, including a duplicate |
| `display_name_candidate` | Filled only when gate D1 is `approved` and at least one name component remains after the D1 rule. Otherwise empty |
| `proposed_employee_ulid` | Artifact-only. Crockford ULID matching the target check |
| `mapping_status` | `proposed_loadable` or `proposed_not_loadable` |
| `disposition` | `classified_only`, `quarantined`, or `approved_exception` |
| `reason_codes` | Zero or more codes from section 6 |

`disposition = approved_exception` requires a signed exception id. No such signature exists. The dry run must not invent one.

### Episode classification, one per source row

| Field | Meaning |
| --- | --- |
| Same provenance as the employee row | Ties the episode decision to the same source key |
| `flag_pair` | `active` / `disconrinue` as stored |
| `joining_date_class`, `discontinue_date_class` | `null`, `zero`, `calendar`, or `other` |
| `episode_candidate_status` | Empty, or `Open`, or `separated`. Never `inactive` |
| `proposed_episode_ulid` | Artifact-only, present only when a candidate status exists |
| `disposition` | `classified_only`, `quarantined`, `no_episode_evidence`, or `approved_exception` |

An employee can be `proposed_loadable` while its episode is `quarantined`. That split is required. An episode cannot be `proposed_loadable` when its employee is `proposed_not_loadable`.

### Exception group

Used for duplicate codes. One group row plus one employee classification per member.

| Field | Meaning |
| --- | --- |
| `group_id` | Stable within the run, for example `vtl:emp_id_no:10024` |
| `member_source_pks` | Every legacy `id` in the group |
| `member_count` | 2 for each known VTL group |
| `authorization` | Empty. A later signature may name which member, if any, may load |

Known VTL groups, from Step 20G, to be re-detected rather than hardcoded as a substitute for the parse: `10024` (ids 255, 100497), `15575` (270, 398), `132058` (100348, 100427), `6119` (100470, 100472), `10023` (100480, 100637), `10027` (100582, 100638), `10034` (100586, 100639).

## 6. Decision gates D1–D6

| Gate | While `proposed` | If a later run sets it `approved` | Still forbidden |
| --- | --- | --- | --- |
| D1 Display name | Record the three components’ classes. Leave `display_name_candidate` empty. Reason `name_rule_proposed`. VTL `source_pk` 437, code `PAL1234`, is an empty-name candidate | Trim nonempty parts, join with one space, quarantine when nothing remains | Inventing a name. Using Yii `getName()` |
| D2 Zero dates | Class `zero` stays distinct from `null`. Reason `zero_date_rule_proposed`. Do not copy `0000-00-00` into a date field | Required episode dates that are zero quarantine the episode. Optional zero dates become `NULL` only if that conversion is the approved text of D2 and the exception still stores the original token | Storing `0000-00-00` as a target date. Folding zero into `NULL` without that approval |
| D3 Both flags 1 | Episode disposition `quarantined`, reason `both_flags_true`. Keep both flags and both date classes. Includes Atlas `source_pk` 7175, code `EB9091`, `discontinue_date` 2026-08-25 | Same quarantine, unless a newer written rule replaces D3. A date alone does not select `Open`, `inactive`, or `separated` | Inferring a status, including from `discontinue_date` |
| D4 Duplicate codes | Every member `proposed_not_loadable`, reason `duplicate_code_group` | Same hold until that group has an authorization record. Authorized members load with their own ULID and the original code text | Merge. Recode. Dropping one member from the source count |
| D5 `dis` | Not a gate. Column ignored | No approval path in this design | Using `dis` for identity or status |
| D6 Episodes | Episode disposition `classified_only` or `quarantined`. No `proposed_load` of an episode | At most one episode for an approved pair with a calendar joining date. Unmapped history is an exception, not a fabricated episode | Episodes from attribute changes or from `employment_history` text. Overlap checks, date-order checks, and inactive pay |

Flag pair `0`/`0` is already a no-status result in Step 20E, including Proedge `source_pk` 1. It is not given `inactive`. Pair `0`/`1` with a calendar joining date and a calendar leaving date is the approved `separated` pair, still blocked from `proposed_load` while D2 and D6 are `proposed`. Pair `0`/`1` without a calendar leaving date stays unresolved and quarantined.

Known dump counts that a later parse must be able to reproduce, not a new census: both flags true, Proedge 19, Atlas 74, VTL 1,186. Joining-date `0000-00-00`: Proedge 1, Atlas 3, VTL 61. Joining-date `NULL`: VTL 6. These sets can overlap. The reconciliation uses the parse output, not a hand sum of these figures.

## 7. Reconciliation templates

Scope is one company and one object. Duplicate source rows count as two source rows. Every source row appears in exactly one employee disposition and exactly one episode disposition.

Dry-run equation, while nothing is written to the target:

```text
SOURCE COUNT = PROPOSED LOAD + QUARANTINED + APPROVED EXCEPTIONS
LOADED = 0
```

`PROPOSED LOAD` means `mapping_status = proposed_loadable` and `disposition` is not `quarantined` or `approved_exception`. With the gates left `proposed`, employee `PROPOSED LOAD` is 0 because D1 is not approved, and episode `PROPOSED LOAD` is 0 because D6 is not approved.

`QUARANTINED` is not an approved exception. `APPROVED EXCEPTIONS` counts only rows with a signed exception id. That count is 0 today.

Final approval, after an authorized execution, uses a different equation and does not reuse the dry-run labels:

```text
SOURCE COUNT = LOADED + APPROVED EXCEPTIONS
QUARANTINED = 0
```

Employees and episodes are separate equations. An employee may be loaded while that row’s episode stays quarantined. A source row in neither list is unexplained. Unexplained must be 0 before migration approval.

### Employee template

| Measure | Proedge catalog 24 | VTL catalog 20 | Atlas catalog 10 |
| --- | ---: | ---: | ---: |
| SOURCE COUNT | 160 | 1,420 | 151 |
| Distinct codes in that source | 160 | 1,413 | 151 |
| LOADED | 0 | 0 | 0 |
| PROPOSED LOAD | filled by the run | filled by the run | filled by the run |
| QUARANTINED | filled by the run | filled by the run | filled by the run |
| APPROVED EXCEPTIONS | 0 until signed | 0 until signed | 0 until signed |
| Unexplained | must be 0 | must be 0 | must be 0 |

### Episode template

| Measure | Proedge | VTL | Atlas |
| --- | ---: | ---: | ---: |
| SOURCE COUNT | 160 | 1,420 | 151 |
| LOADED | 0 | 0 | 0 |
| PROPOSED LOAD | filled by the run | filled by the run | filled by the run |
| QUARANTINED | filled by the run | filled by the run | filled by the run |
| NO EPISODE EVIDENCE, still inside QUARANTINED or classified | filled by the run | filled by the run | filled by the run |
| APPROVED EXCEPTIONS | 0 until signed | 0 until signed | 0 until signed |
| Unexplained | must be 0 | must be 0 | must be 0 |

Episode `SOURCE COUNT` equals the employee source count because each current row is in scope for an episode decision. A row with no mapped episode remains in the episode equation as quarantined or as no-episode evidence. It is not dropped.

Independent validation reads the artifact a second time, recomputes both equations per company, and checks that every `source_pk` appears once on each side. The run fails closed when the passes differ.

## 8. Validation rules and failure conditions

The run fails and writes no target data when any of these are true:

- A dump fingerprint does not match section 1, or a tuple field count does not match that dump’s `employees` column list.
- A source row has no classification, or has two.
- A `source_pk` is null or repeated inside one dump.
- Two `proposed_loadable` rows in one company share `emp_id_no`.
- A duplicate group lists fewer members than the parse found.
- `display_name_candidate` is nonempty while gate D1 is `proposed`.
- An episode candidate status is `inactive`, or any status is emitted from both flags being 1.
- `0000-00-00` is copied into a proposed target date.
- An episode is `proposed_loadable` while its employee is not.
- `target_company_ulid` is filled with a value that is not in the prerequisite company map.
- `dis` is present in a mapping or status reason as a deciding field.
- The two reconciliation passes disagree, or unexplained is not 0.
- The run opens a database connection or writes a target table.

Classification of a known candidate is not a failure. VTL id 437, the seven duplicate groups, both-true rows, and `EB9091` are expected quarantine or classified-only outcomes under the closed gates.

## 9. Safety and repeatability

- Read dumps and the catalog file. Do not write them, decompress them in place, or import them.
- Do not connect to PostgreSQL, MySQL, Laragon, or production.
- Same fingerprints and same `gates_closed` inputs must produce the same dispositions and the same reason codes. Proposed ULID values may differ between runs. They are not approved mappings and must not be reused as if they had been loaded.
- Persist the artifact only as a report file outside the target database. Do not insert into `companies`, `employees`, or `employment_episodes`.
- Record `mode` and each gate value in the header so a later reader can see that proposals were not treated as approvals.
- Keep dump findings labeled as dump findings when the artifact quotes Step 20G counts.

## 10. Preconditions for later execution

1. Explicit approval, outside this document, of each gate that the run will set to `approved`. Unapproved gates stay `proposed` and block `proposed_load` for the rows they cover.
2. A non-production target company row for catalog 24, 20, and 10, with the ULID recorded in the crosswalk input. No invented ULID.
3. A named extract. If the extract is these three files, the fingerprints must match section 1. If the extract is a Laragon copy, a read-only compare to these dumps is a separate step and has not been done.
4. An exception register that can hold a signed disposition. Unsigned quarantines remain quarantines.
5. A runner that implements sections 4 through 8 and refuses a target write while `target_database_write` is false.

Meeting those preconditions is an execution decision. This design does not make it. Overlap, date order, inactive pay, and the meaning of `dis` are not preconditions for a classification run that never emits `inactive` and never writes episodes from text history. They remain unresolved for any later load that would need them.

## 11. Execution statement

No dry run was executed. No migration ran. No database connection was opened. No dump, schema, configuration, application file, or target row was changed. Proedge, Atlas, and VTL are not migration-approved. Proposed gates are not approved load rules.

DRY-RUN DESIGN COMPLETE
