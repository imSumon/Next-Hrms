# Universal HRMS — Step 20L Target Company ULID and Approval Verification

Read-only check of the local target database, plus a reconciliation of the Step 20K approval fields. No company row was inserted. No decision was approved. No company is migration-approved.

Classes:

- **VERIFIED** — read in this step from the named local source.
- **DOCUMENTED ONLY** — taken from the named earlier report and not re-measured here.
- **PROPOSED** — still a proposal. Not an approval.
- **NOT VERIFIED** — the required link or signature is absent.

## 1. Documents reviewed

The two project doc folders hold identical copies of each file below. SHA-256 prefixes match. No conflicting copy was found.

- `docs/UNIVERSAL_HRMS_STEP20K_EMPLOYEE_MIGRATION_APPROVAL_AND_SOURCE_MANIFEST.md`
- `docs/UNIVERSAL_HRMS_STEP20J_EMPLOYEE_MIGRATION_DRY_RUN_DESIGN.md`
- `docs/UNIVERSAL_HRMS_STEP20I_EMPLOYEE_MIGRATION_DECISIONS_AND_EVIDENCE_GAPS.md`
- `docs/UNIVERSAL_HRMS_STEP20H_EMPLOYEE_MIGRATION_BLOCKER_AND_DECISION_REGISTER.md`
- `docs/UNIVERSAL_HRMS_STEP20A_FOUNDATION_IMPLEMENTATION_REPORT.md`
- `docs/UNIVERSAL_HRMS_MULTI_COMPANY_MIGRATION_MAPPING_WORKBOOK.md`, as cited by Steps 20I and 20J

`legacy-reference\hrms\docs` and `D:\Project -All\Proedge HRMS\docs` agree on those files. `next-hrms\docs` contains only the Step 20E mapping design, and that file matches the two project copies. It does not contain a second Step 20K, 20J, 20I, 20H, or 20A text.

## 2. Target company mapping results and evidence

Connection, this step only:

| Check | Result | Class |
| --- | --- | --- |
| Host and port | `127.0.0.1`, port `5432` | **VERIFIED** |
| `current_database()` | `proedge_new_hrms` | **VERIFIED** |
| `inet_server_addr()` | `127.0.0.1/32` | **VERIFIED** |
| Statements issued | `SELECT` against `companies` and `information_schema.columns`, plus a session setting. No `INSERT`, `UPDATE`, `DELETE`, or DDL | **VERIFIED** |
| Legacy MySQL or production | Not opened | **VERIFIED** |

`companies` columns match the Step 20A catalog: `id`, `company_code`, `company_name`, `status`, `source_system`, `source_key`, `created_at`, `updated_at`.

| Measure | Result | Class |
| --- | ---: | --- |
| Rows in `companies` | 0 | **VERIFIED** |
| Rows with `source_key` in `24`, `20`, `10` | 0 | **VERIFIED** |
| Rows with `source_key` in `5` or `26` | 0 | **VERIFIED** |

No `id` value exists to map. Catalog 24 (Proedge), catalog 20 (Viyellatex), and catalog 10 (Atlas) have no target ULID in this database.

Missing prerequisite: three `companies` rows, each with `source_key` equal to `24`, `20`, or `10`, and each `id` a real ULID matching `^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$`. Those rows are not loaded. Dump-local ids 5 and 26 were not used as target ids. No ULID was generated.

Step 20C’s earlier rolled-back probe also left `companies` at 0. This query is the current local count. It is not a claim about any other database.

## 3. Approval status of D1, D2, D3, D4, and D6

Step 20K leaves approval choice, revised rule, approver, and approval date blank for each decision. Step 20I labels each rule **PROPOSED**. No later project record fills those fields. A recommendation, a blank template, and this verification are not approvals.

| Decision | Recorded rule | Approval choice | Approver | Approval date | Status |
| --- | --- | --- | --- | --- | --- |
| D1 Display name | Trim nonempty parts, join with one space, quarantine an empty name | Blank | Blank | Blank | **PROPOSED** |
| D2 Zero dates | `0000-00-00` stays a distinct exception. Not a target date. Not ordinary `NULL` | Blank | Blank | Blank | **PROPOSED** |
| D3 Both flags true | Do not infer `Open`, `inactive`, or `separated`. Quarantine status | Blank | Blank | Blank | **PROPOSED** |
| D4 Duplicate VTL codes | Quarantine the group. Keep every source row. No merge. No recode | Blank | Blank | Blank | **PROPOSED** |
| D6 Episodes | Create an episode only from supported evidence and an approved date/status rule | Blank | Blank | Blank | **PROPOSED** |

No-merge and no-recode remain the already approved identity principles from the employee-code decision. That earlier approval does not approve D4’s group-hold as a signed load rule, and it does not approve D1, D2, D3, or D6.

## 4. Source manifest verification status

The three Step 20K paths still exist. Byte size matches the Step 20K manifest. Hashes were not recalculated.

| File | Bytes now | Step 20K bytes | SHA-256 in Step 20K | This step |
| --- | ---: | ---: | --- | --- |
| `D:\Project -All\Proedge HRMS\legacy-data\companies\proedgea_newproedgehrm.sql` | 10,534,148 | 10,534,148 | `DC5A92065E291F981689A06423615B555C52CDF73BDC1A976344A5531E9B9534` | Size matches. Hash not recomputed |
| `D:\Project -All\Proedge HRMS\legacy-data\companies\proedgea_atlas.sql` | 164,919,794 | 164,919,794 | `239D2D131AF4337DDDB979468413CFEC5E2C2AC80FD7F8EF14278C2A6DB85D8D` | Size matches. Hash not recomputed |
| `D:\Project -All\Proedge HRMS\legacy-data\companies\vtl\proedgea_vtlhrm.sql.gz` | 105,425,407 | 105,425,407 | `DFCC698A971098C621AE5042E1557AD4A4A1B96150BBEBDF1D2398A677DBEF65` | Size matches. Hash not recomputed |

Path and size agreement is **VERIFIED**. The SHA-256 values remain **DOCUMENTED ONLY** from Step 20K. No dump was modified.

## 5. Remaining blockers

- Target ULIDs for catalog 24, 20, and 10 are **NOT VERIFIED**. `companies` has no rows.
- D1, D2, D3, D4, and D6 are **PROPOSED**. The Step 20J gates stay `proposed`. `proposed_load` stays blocked.
- The Step 20K exception section is a template. Approved-exception count is 0.
- Overlap, date order, inactive pay, and the meaning of `dis` stay **UNRESOLVED** and outside this pipeline.
- `companies.source_system` is still unnamed. Employee `source_system` values do not depend on it.
- No company is migration-approved. Reconciliation has not been executed.

## 6. Exact next recommended action

Do not load employees and do not run classification.

The next authorized step, and only that step, should create the three local `companies` rows: new ULIDs, `source_key` values `24`, `20`, and `10`, and names from the approved catalog crosswalk. Then repeat the read-only `companies` query in this report. Separately, a named approver must write APPROVE or REVISE, with name and date, on D1, D2, D3, D4, and D6 in the Step 20K matrix. Neither action is taken here.

## 7. Execution statement

One read-only session connected to local `proedge_new_hrms` at `127.0.0.1:5432`. It did not connect to legacy MySQL or production. It did not insert, update, or delete. It did not change schema, dumps, `.env`, configuration, or application code. It did not approve a decision or a company.

TARGET MAPPING NOT VERIFIED
