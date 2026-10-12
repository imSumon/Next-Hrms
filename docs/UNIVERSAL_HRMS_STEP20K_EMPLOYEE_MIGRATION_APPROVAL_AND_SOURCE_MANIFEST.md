# Universal HRMS — Step 20K Employee Migration Approval and Source Manifest

Approval package only. No dry run was executed. No target row was written. No company is migration-approved.

D1, D2, D3, D4, and D6 remain **PROPOSED**. This document does not select APPROVE or REVISE for any of them. Approver identity and approval date are blank.

Classes:

- **VERIFIED** — measured in this step, or established by the named earlier report from the source it cites.
- **DOCUMENTED ONLY** — an earlier report states it. This step did not re-read that source.
- **PROPOSED** — the working rule from Step 20I. Not an approval.
- **UNRESOLVED** — still open, and outside the D1–D6 approval matrix.
- **NOT VERIFIED** — not read in this step.

## 1. Documents reviewed

Present and read:

- `docs/UNIVERSAL_HRMS_STEP20A_FOUNDATION_IMPLEMENTATION_REPORT.md`
- `docs/UNIVERSAL_HRMS_STEP20A_MIGRATION_REVIEW.md`
- `docs/UNIVERSAL_HRMS_STEP20B_EMPLOYEE_LIFECYCLE_DESIGN_REVIEW.md`
- `docs/UNIVERSAL_HRMS_STEP20C_EMPLOYEE_LIFECYCLE_IMPLEMENTATION_REPORT.md`
- `docs/UNIVERSAL_HRMS_STEP20D_EMPLOYEE_LIFECYCLE_INTEGRITY_REVIEW.md`
- `docs/UNIVERSAL_HRMS_STEP20E_EMPLOYEE_MIGRATION_MAPPING_AND_RECONCILIATION_DESIGN.md`
- `docs/UNIVERSAL_HRMS_STEP20F_LEGACY_EMPLOYEE_SOURCE_SCHEMA_VERIFICATION.md`
- `docs/UNIVERSAL_HRMS_STEP20G_LEGACY_EMPLOYEE_IDENTITY_AND_EXCEPTION_AUDIT.md`
- `docs/UNIVERSAL_HRMS_STEP20H_EMPLOYEE_MIGRATION_BLOCKER_AND_DECISION_REGISTER.md`
- `docs/UNIVERSAL_HRMS_STEP20I_EMPLOYEE_MIGRATION_DECISIONS_AND_EVIDENCE_GAPS.md`
- `docs/UNIVERSAL_HRMS_STEP20J_EMPLOYEE_MIGRATION_DRY_RUN_DESIGN.md`
- `docs/UNIVERSAL_HRMS_STEP20_FINAL_EMPLOYEE_CODE_DECISION.md`
- `docs/UNIVERSAL_HRMS_MULTI_COMPANY_MIGRATION_MAPPING_WORKBOOK.md`, as cited by Steps 20E, 20I, and 20J
- `docs/UNIVERSAL_HRMS_TARGET_DATABASE_SCHEMA_SPECIFICATION.md`, as cited by Steps 20B and 20E
- `docs/UNIVERSAL_HRMS_MIGRATION_DATA_CONTINUITY_ARCHITECTURE.md`, as cited by Step 20E

No document in that list was missing.

## 2. D1 / D2 / D3 / D4 / D6 approval matrix

The proposed text is copied from Step 20I. Approval choice, revised rule, approver, and date are empty on purpose.

Evidence required before use is the same for every row: a completed choice of APPROVE or a written REVISE, a named approver, and a date. Until those three fields are filled, the gate in the Step 20J design stays `proposed`.

### D1 — Display name — PROPOSED

| Field | Content |
| --- | --- |
| Exact proposed rule | Trim each nonempty name component. Join the remaining first, middle, and last names with one space. Do not invent a name. Quarantine a record when every component is empty or `NULL`. |
| Business impact | `display_name` is required. VTL `employees.id` 437, code `PAL1234`, has all three components empty and cannot load until a reviewer supplies a name or approves exclusion. Yii `getName()` is not this rule. |
| Approval choice | |
| Revised rule, if REVISE | |
| Approver | |
| Approval date | |
| Evidence required before use | Signed D1, plus a disposition for every quarantined empty name. Step 20G found one such row in these dumps. |

### D2 — Zero dates — PROPOSED

| Field | Content |
| --- | --- |
| Exact proposed rule | Preserve `0000-00-00` as a distinct exception. It is not a valid target date and it is not ordinary `NULL`. Do not invent a date. Keep the original token and the source-row reference. A required episode date that is zero quarantines the episode. Mapping an optional zero date to `NULL` requires an explicitly approved mapping rule and a traceable exception. |
| Business impact | Target `joining_date` is `date NOT NULL`. Joining-date zeros in the Step 20G dumps: Proedge 1, Atlas 3, VTL 61. Leaving-date zeros: Proedge 1, Atlas 0, VTL 81. Folding the token into `NULL` without this approval would hide that census. |
| Approval choice | |
| Revised rule, if REVISE | |
| Approver | |
| Approval date | |
| Evidence required before use | Signed D2. The proposal does not itself authorize storing a zero `discontinue_date` as `NULL`. |

### D3 — Both flags true — PROPOSED

| Field | Content |
| --- | --- |
| Exact proposed rule | Do not infer `Open`, `inactive`, or `separated` when `active` and `disconrinue` are both 1. Quarantine the unresolved lifecycle status. Do not infer status from `discontinue_date` alone. Keep both flags and the date classes. |
| Business impact | Step 20G counts: Proedge 19, Atlas 74, VTL 1,186. Atlas `EB9091` is `employees.id` 7175, both flags 1, `discontinue_date` 2026-08-25. VTL’s both-true set is most of that file. An automatic status would classify that majority. |
| Approval choice | |
| Revised rule, if REVISE | |
| Approver | |
| Approval date | |
| Evidence required before use | Signed D3 before any both-true episode is written. September attendance and the missing September salary for `EB9091` stay **DOCUMENTED ONLY** in the third-company audit. They are not required to apply this quarantine. |

### D4 — Duplicate VTL codes — PROPOSED

| Field | Content |
| --- | --- |
| Exact proposed rule | Quarantine each unresolved duplicate-code group. Preserve every source row. Do not merge identities. Do not silently recode employee codes. Load an affected row only after reliable source evidence and explicit authorization for that group. |
| Business impact | Seven VTL `emp_id_no` groups, 14 rows: `10024` (ids 255, 100497), `15575` (270, 398), `132058` (100348, 100427), `6119` (100470, 100472), `10023` (100480, 100637), `10027` (100582, 100638), `10034` (100586, 100639). Each group is two source rows in the source count. Proedge and Atlas have 0 duplicate groups in these dumps. |
| Approval choice | |
| Revised rule, if REVISE | |
| Approver | |
| Approval date | |
| Evidence required before use | Signed D4 for the group hold. No-merge and no-recode are already **APPROVED** in the employee-code decision and Step 20E. The 14 per-row authorizations do not exist. |

### D6 — Episodes — PROPOSED

| Field | Content |
| --- | --- |
| Exact proposed rule | Create an episode only when source evidence supports that employment event and the dates and status map under an approved rule. Do not fabricate history. Do not infer an episode from a department, designation, location, or other attribute change. When history cannot be mapped safely, keep the source record and write an explicit exception. |
| Business impact | No employment-episode table was confirmed. `employment_history` is text. One current employee row can support at most one episode, and only for an already approved flag pair with a real calendar joining date. This proposal does not add overlap rules, a date-order check, or pay behavior for `inactive`. |
| Approval choice | |
| Revised rule, if REVISE | |
| Approver | |
| Approval date | |
| Evidence required before use | Signed D6 before any episode is written. Approved pairs remain: `active` 1 and `disconrinue` 0 with a real joining date maps to one `Open` episode; `active` 0 and `disconrinue` 1 with real joining and leaving dates maps to one `separated` episode. Pair `0`/`1` with no calendar leaving date stays unresolved and is not mapped by D6. |

## 3. Other unresolved decisions

These stay outside the approval matrix and outside the current pipeline. This step adds no resolution and no scope change.

| Item | Status | Why it stays out |
| --- | --- | --- |
| Episode date-range overlap | **UNRESOLVED** | Step 20B. The implemented table enforces one `Open` episode only. A single-span classification that never writes a second episode does not need this rule. |
| Episode date-order validation | **UNRESOLVED** | Step 20B. No check requires `last_working_date` on or after `joining_date`. |
| Pay effect of `inactive` | **UNRESOLVED** | Step 20B, D06/D07. No pay column exists on `employment_episodes`. The pipeline must not emit `inactive`. |
| Meaning of legacy `dis` | **UNRESOLVED** | Step 20I. The column is not an identity key and not a status. It stays unmapped. |

`companies.source_system` is still unnamed. The column is nullable. Employee `source_system` values `legacy_proedge`, `legacy_vtlhrm`, and `legacy_atlas` are already specified and do not depend on it.

## 4. Source-file manifest and fingerprints

Fingerprints were calculated in this step with SHA-256 over the file bytes. The files were not opened for row content, not decompressed, and not modified. No personal employee fields were read.

Step 20G recorded path, size, and last-write date. It did not record a hash. Path, size, and last-write date below match that record. The hash is new, so there is no earlier hash to compare. No substitute file was used.

| Company | Absolute path | Exists | Bytes | Last write | Format | SHA-256 |
| --- | --- | --- | ---: | --- | --- | --- |
| Proedge | `D:\Project -All\Proedge HRMS\legacy-data\companies\proedgea_newproedgehrm.sql` | Yes | 10,534,148 | 2026-10-02 15:05:06 | Uncompressed SQL dump. Header database `proedgea_newproedgehrm` is **DOCUMENTED ONLY** from Steps 20F and 20G | `DC5A92065E291F981689A06423615B555C52CDF73BDC1A976344A5531E9B9534` |
| Atlas | `D:\Project -All\Proedge HRMS\legacy-data\companies\proedgea_atlas.sql` | Yes | 164,919,794 | 2026-10-02 15:07:21 | Uncompressed SQL dump. Header database `proedgea_atlas` is **DOCUMENTED ONLY** from Steps 20F and 20G | `239D2D131AF4337DDDB979468413CFEC5E2C2AC80FD7F8EF14278C2A6DB85D8D` |
| VTL | `D:\Project -All\Proedge HRMS\legacy-data\companies\vtl\proedgea_vtlhrm.sql.gz` | Yes | 105,425,407 | 2026-10-09 12:14:46 | Gzip-compressed SQL. Hash is of the compressed bytes. Header database `proedgea_vtlhrm` is **DOCUMENTED ONLY** from Steps 20F and 20G | `DFCC698A971098C621AE5042E1557AD4A4A1B96150BBEBDF1D2398A677DBEF65` |

Step 20G employee census for these paths, not re-counted here: Proedge 160 rows / 160 codes, Atlas 151 / 151, VTL 1,420 / 1,413. Those figures describe the dump parse. They are not a live-database census.

Catalog crosswalk used by Steps 20I and 20J, from the local master dump, not re-parsed here:

| Catalog id | Company | Source database | `previous_db_id` | Employee `source_system` |
| ---: | --- | --- | ---: | --- |
| 24 | Pro-edge Associates Limited | `proedgea_newproedgehrm` | 5 | `legacy_proedge` |
| 20 | Viyellatex Group | `proedgea_vtlhrm` | 5 | `legacy_vtlhrm` |
| 10 | Atlas Copco Bangladesh Ltd. | `proedgea_atlas` | 26 | `legacy_atlas` |

Dump-local `companies.id` is 5 for Proedge, 5 for VTL, and 26 for Atlas. Those numbers match `previous_db_id`. They are not catalog ids. Catalog 5 and catalog 26 are other companies. The master dump path cited by Step 20I is `D:\Project -All\Proedge HRMS\legacy-data\master\proedgea_payrollmaster.sql`. This step did not hash that file.

## 5. Target company ULID verification status

**NOT VERIFIED.** No target company ULID is documented for catalog 24, 20, or 10.

Step 20C inserted probe rows in a transaction and rolled them back. Counts before and after were 0 for `companies`, `branches`, `location_nodes`, `organization_nodes`, `employees`, and `employment_episodes`. Step 20J states that no ULID is assigned. This step did not open `proedge_new_hrms` and does not treat that earlier zero as the current row count.

A later read-only session, only after the connection is confirmed to be the local database, can obtain the ULIDs with:

```sql
SELECT id, company_name, source_system, source_key
FROM companies
WHERE source_key IN ('24', '20', '10');
```

Accept a returned `id` only when it matches the ULID check `^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$`. Do not copy a legacy numeric id into `companies.id`. An empty result means the company prerequisite is still unmet. Classification may continue with `target_company_ulid` empty. `proposed_loadable` stays blocked until a real ULID is recorded from that query.

## 6. Exception-register template

This section is a blank template. It is not a signed register. No disposition below is an approved exception. Approver and date stay empty. Individual both-true rows, zero-date rows, and null-date rows are not listed one by one. Doing that would invent a register the audit did not sign.

Columns for every later register row:

| Column | Rule |
| --- | --- |
| `exception_id` | Assigned when the register is opened. Blank here |
| `category` | One category from the table below |
| `catalog_company_id` | 24, 20, or 10 |
| `source_system` | `legacy_proedge`, `legacy_vtlhrm`, or `legacy_atlas` |
| `source_table` | `employees` |
| `source_pk` | Legacy `employees.id` |
| `source_emp_id_no` | Exact code text. Not the identity |
| `source_file_sha256` | The fingerprint in section 4 for that company’s file |
| `evidence` | Step 20G, or a later parse that reproduces it |
| `disposition` | Blank until a reviewer writes one |
| `approver` | Blank |
| `approval_date` | Blank |

| Template slot | Category | What Step 20G already identifies | Register state |
| --- | --- | --- | --- |
| T-DUP | Duplicate code group | Seven VTL groups and the 14 source keys in section 2 | Slots only. No member is authorized |
| T-NAME | No usable name | VTL `employees.id` 437, code `PAL1234` | Slot only |
| T-FLAGS | Both flags 1 | Proedge 19, Atlas 74, VTL 1,186. Named member: Atlas `employees.id` 7175, code `EB9091`, `discontinue_date` 2026-08-25 | Category slot. The other rows are not itemized here |
| T-ZERO | `0000-00-00` | Joining-date zeros: Proedge 1, Atlas 3, VTL 61. Leaving-date zeros: Proedge 1, Atlas 0, VTL 81. Named member: Proedge `employees.id` 1, code text `emp_id_no`, flags `0`/`0`, joining date zero | Category slot. Other zero-date keys are not itemized |
| T-NULL-JOIN | `joining_date` `NULL` | Proedge 0, Atlas 0, VTL 6 | Category slot. The six keys are not itemized |
| T-BOTH-FALSE | Flags `0`/`0` | Proedge 6, including `employees.id` 1. Atlas 0. VTL 0 | Category slot. Do not store `inactive` |
| T-SEP-NODATE | Flags `0`/`1` with no calendar leaving date | Pair counts exist: Proedge 91, Atlas 18, VTL 1. The leaving-date split of that pair was not counted | Slot reserved. Count **NOT VERIFIED** |
| T-HISTORY | History that cannot map to one approved episode | No episode table. `employment_history` text is not an episode source | Slot reserved. No text was parsed |

Duplicate source rows remain two source rows. A quarantined row is not an approved exception. Approved-exception count is 0.

## 7. Remaining prerequisites for a classification-only dry run

A classification-only run is the Step 20J pipeline in mode `gates_closed`. It writes an artifact outside the database and writes no target row. This package does not authorize that run.

| Prerequisite | Now |
| --- | --- |
| D1, D2, D3, D4, and D6 explicitly approved or revised | Not met. All five are **PROPOSED**. A `gates_closed` classification may still classify rows, and it must leave `proposed_load` at 0 while a gate is `proposed` |
| Source manifest for the named extract | Met for the three paths in section 4. A later run must refuse a different path, size, or SHA-256 |
| Target company ULIDs for catalog 24, 20, and 10 | **NOT VERIFIED**. Empty ULID blocks `proposed_loadable`. It does not block classification |
| Signed exception register | Not met. Section 6 is a template. Approved-exception count stays 0 |
| Runner that refuses a target write | Not built in this step |
| Laragon copies compared with these dumps | **NOT VERIFIED**. The named extract for any run that uses section 4 is these three files, not the Laragon databases |
| Overlap, date order, inactive pay, and `dis` | Not prerequisites for a classification that never emits `inactive` and never writes a second episode. They stay **UNRESOLVED** |

Dry-run reconciliation, if a later run is authorized, is `SOURCE COUNT = PROPOSED LOAD + QUARANTINED + APPROVED EXCEPTIONS` with `LOADED = 0`. Final migration approval uses a different equation, `SOURCE COUNT = LOADED + APPROVED EXCEPTIONS`, and requires quarantine = 0. Neither equation has been executed.

## 8. Execution statement

No dry run was executed. No migration ran. No database connection was opened. No dump was modified. No schema, configuration, application file, or target row was changed. Proedge, Atlas, and VTL are not migration-approved. Blank approval fields are not approvals.

APPROVAL PACKAGE PREPARED
