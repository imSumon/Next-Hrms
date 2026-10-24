# Universal HRMS — Step 20G Legacy Employee Identity and Exception Audit

Offline dump parse and document review. No database connection was opened. No dump was imported, decompressed in place, or modified. Counts below describe these dump files. They are not a census of a running database.

No company is migration-approved.

Labels:

- **VERIFIED FROM DUMP** — a `CREATE TABLE` or a complete `INSERT` parse of that dump.
- **DOCUMENTED ONLY** — an approved audit states it. This step did not re-read that source.
- **INFERRED** — a consequence of an approved rule, not a new business fact.
- **UNRESOLVED** — the dumps and approved documents do not settle it.
- **NOT VERIFIED** — not counted, or not re-checked, in this step.

## 1. Source files and documents inspected

| Source | Location | Used for |
| --- | --- | --- |
| Proedge dump | `D:\Project -All\Proedge HRMS\legacy-data\companies\proedgea_newproedgehrm.sql` (10,534,148 bytes, last write 2026-10-02). Header database `proedgea_newproedgehrm` | Schema and employee `INSERT` rows |
| Atlas dump | `D:\Project -All\Proedge HRMS\legacy-data\companies\proedgea_atlas.sql` (164,919,794 bytes, last write 2026-10-02). Header database `proedgea_atlas` | Schema and employee `INSERT` rows |
| VTL dump | `D:\Project -All\Proedge HRMS\legacy-data\companies\vtl\proedgea_vtlhrm.sql.gz` (105,425,407 bytes, last write 2026-10-09). Header database `proedgea_vtlhrm` | Schema and employee `INSERT` rows, read through a streaming gzip decoder |
| Step 20F schema verification | `docs/UNIVERSAL_HRMS_STEP20F_LEGACY_EMPLOYEE_SOURCE_SCHEMA_VERIFICATION.md` | Questions left open |
| Step 20E mapping design | `docs/UNIVERSAL_HRMS_STEP20E_EMPLOYEE_MIGRATION_MAPPING_AND_RECONCILIATION_DESIGN.md` | Approved copy rules and exception classes |
| Mapping workbook | `docs/UNIVERSAL_HRMS_MULTI_COMPANY_MIGRATION_MAPPING_WORKBOOK.md` sections 3 and 17 | Catalog and salary-key mapping |
| Mapping validation | `docs/UNIVERSAL_HRMS_MULTI_COMPANY_MIGRATION_MAPPING_VALIDATION.md` | Catalog identities 24 and 20 from a prior `legacy_master` read |
| Proedge August 2026 dry run | `docs/UNIVERSAL_HRMS_PROEDGE_AUGUST_2026_DRY_RUN_REPORT.md` | Catalog row 24 and the earlier 160-row baseline |
| Atlas closed-month audit | `docs/UNIVERSAL_HRMS_THIRD_REFERENCE_COMPANY_CLOSED_MONTH_AUDIT.md` | Catalog 10, catalog 26 as Grameen, and the EB9091 narrative |
| Target schema | `docs/UNIVERSAL_HRMS_TARGET_DATABASE_SCHEMA_SPECIFICATION.md` section 4.3 | `display_name` is required. No composition procedure |
| Yii `Employees` model | `backend/modules/pims/models/Employees.php` | Application labels and `getName()`. Not an approved migration rule |

`legacy_master` was not in these three dumps. Laragon was not started.

Parse method for every employee count:

- The `employees` `CREATE TABLE` supplied the column order.
- Each `INSERT` tuple was accepted only when it had the same 77 fields as that column list.
- A mismatch aborts that company’s employee parse. All three parses finished with zero mismatches.
- `NULL` is the unquoted SQL token. An empty string is `''`. Whitespace is a quoted value whose trim is empty and whose raw value is not `''`.
- Duplicate codes are exact string matches among values that are neither `NULL`, nor `''`, nor whitespace. A second count trims and case-folds those same values.
- `0000-00-00` is counted on its own. It is not folded into `NULL`.
- A name component is usable when it is neither `NULL`, nor `''`, nor whitespace. A row has no usable name when all three components are unusable.
- Salary tables were read only far enough to count `company_id`. Amounts were not retained.

## 2. Employee schema comparison

All three dumps define `employees` with 77 columns, `ENGINE=MyISAM`, and one index: `PRIMARY KEY (`id`)`. There is no `UNIQUE` key on `emp_id_no`, `dis`, or `old_id`. There is no `company_id` column on `employees`.

| Column | Proedge | Atlas | VTL | Status |
| --- | --- | --- | --- | --- |
| `id` | `int NOT NULL AUTO_INCREMENT` | same | same | **VERIFIED FROM DUMP**. Primary key |
| `emp_id_no` | `varchar(50) DEFAULT NULL` | same, with `latin1_swedish_ci` | `varchar(50) DEFAULT NULL` | **VERIFIED FROM DUMP**. Nullable. Not unique |
| `emp_first_name`, `emp_mid_name`, `emp_last_name` | each `varchar(128) DEFAULT NULL` | same, with `latin1_swedish_ci` | each `varchar(128) DEFAULT NULL` | **VERIFIED FROM DUMP** |
| `joining_date` | `date DEFAULT NULL` | same | same | **VERIFIED FROM DUMP** |
| `active` | `tinyint(1) DEFAULT '1'` | same | same | **VERIFIED FROM DUMP** |
| `disconrinue` | `tinyint(1) DEFAULT '0'` | same | same | **VERIFIED FROM DUMP**. VTL uses this spelling |
| `discontinue_date` | `date DEFAULT NULL` | same | same | **VERIFIED FROM DUMP** |
| `dis` | `int DEFAULT '0'` | same | same | **VERIFIED FROM DUMP** as a column. Meaning **UNRESOLVED** |
| `old_id` | `varchar(255) DEFAULT NULL` | same | same | **VERIFIED FROM DUMP** as a column. Not an identity key |
| `emp_unit` | `int DEFAULT NULL` | same | same | **VERIFIED FROM DUMP**. Legacy unit value. Not the catalog tenant |
| `employment_history` | `text`, no default shown | same | same | **VERIFIED FROM DUMP**. Free text on the employee row |

The identity names match. The tables are not identical. Atlas attaches `latin1_swedish_ci` to several string columns, uses table charset `utf8mb3`, and places `boss` later than Proedge and VTL. Proedge and VTL use table charset `latin1`. Those differences were already recorded in Step 20F and were present again in this parse.

Stored `active` and `disconrinue` values in these inserts are only `0` and `1`.

Ownership visible inside each dump is the database plus a one-row local `companies` table. That local table has `id` and `company_name`. It has no `previous_db_id`, `db_alias`, or `db_name` column.

| Dump | Local `companies` rows | `id` | `company_name` | `employees.emp_unit` |
| --- | --- | --- | --- | --- |
| Proedge | 1 | 5 | Pro-edge Associates Limited | 5 on 159 rows, 0 on 1 row |
| VTL | 1 | 5 | Viyellatex Group | 5 on all 1,420 rows |
| Atlas | 1 | 26 | Atlas Copco Bangladesh Ltd. | 26 on all 151 rows |

**VERIFIED FROM DUMP.** The same local id `5` is Proedge in one file and Viyellatex in another.

## 3. Display-name findings

Approved documents require a non-empty `employees.display_name` and treat an empty name as an exception. The workbook names the source role “name” and says “Copy”. The schema specification says `display_name` is the required name. None of those documents says how to combine `emp_first_name`, `emp_mid_name`, and `emp_last_name`, what to do with `NULL`, `''`, or whitespace, or what to store when every component is unusable.

`Employees::getName()` returns the three properties joined by spaces. That is legacy screen text. It keeps a separator when a component is empty, and it has no empty-result exception. It is not an approved migration rule.

Name census, using the definitions in section 1:

| Dump | Rows | No usable name | Any `NULL` component | Any `''` component | Any whitespace-only component | First unusable | Middle unusable | Last unusable |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Proedge | 160 | 0 | 0 | 48 | 0 | 0 | 46 | 2 |
| Atlas | 151 | 0 | 0 | 117 | 0 | 1 | 114 | 81 |
| VTL | 1,420 | 1 | 0 | 1,037 | 0 | 1 | 950 | 462 |

**VERIFIED FROM DUMP.** Unusable components in these files are empty strings. Proedge’s 46 empty middle names and 2 empty last names are on different rows. Atlas and VTL overlap, so those three columns cannot be added into the “any empty” total.

The one VTL row with no usable name is `employees.id` 437, `emp_id_no` `PAL1234`, all three name fields `''`.

Recommended decision, **UNRESOLVED** until approved:

- Build `display_name` from `emp_first_name`, then `emp_mid_name`, then `emp_last_name`.
- Drop a component that is `NULL`, `''`, or whitespace.
- Join the remaining components with one space.
- When nothing remains, do not invent a name. The row stays an empty-name exception and is not loaded until a reviewer supplies a name or approves exclusion.

## 4. `dis` and `old_id`

Both columns exist on `employees` in every dump. Neither is indexed. Neither is part of the primary key.

### `dis`

Physical definition: `int DEFAULT '0'`. The default does not forbid `NULL`. Stored values:

| Dump | `NULL` | `0` | `1` | Any other value |
| --- | --- | --- | --- | --- |
| Proedge | 3 | 155 | 2 | none in this parse |
| Atlas | 65 | 86 | 0 | none in this parse |
| VTL | 1 | 1,419 | 0 | none in this parse |

The Yii label is `Earn Leave Balance` when the session alias is `vtl`, `vtlw`, or `vtlnms`. For every other alias the label is `Dis`. The employee form is a text input. The VTL dump’s values are `0` or `NULL`, which does not demonstrate a leave balance. The column is not `active` and not `disconrinue`.

**VERIFIED FROM DUMP** for type, default, lack of an index, and the value census. Business meaning is **UNRESOLVED**. It cannot safely support identity mapping.

### `old_id`

Physical definition: `varchar(255) DEFAULT NULL`. The employee form, when the field is enabled, offers `Yes` and `No`. The attribute label is `Only Dashborad`.

| Dump | `NULL` | `''` | Whitespace | `Yes` | `No` | Equal to `employees.id` | Other non-empty values |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Proedge | 158 | 2 | 0 | 0 | 0 | 0 | 0 |
| Atlas | 34 | 0 | 0 | 0 | 117 | 0 | 0 |
| VTL | 0 | 0 | 0 | 1 | 1,419 | 0 | 0 |

`No` repeats. The column is not unique. Proedge does not use the Yes/No vocabulary that Atlas and VTL use. **VERIFIED FROM DUMP.** `old_id` is not a stable identity key and must not be copied into `source_key`.

## 5. Offline exception counts

These counts are **VERIFIED FROM DUMP**. They match the Proedge dry-run baseline of 160 rows and the Atlas audit baseline of 151 rows. That agreement is between this dump and those earlier reports. It is not a claim about the current live database. The VTL row total 1,420 matches the workbook’s earlier `eligible_for_ot` population note. The VTL duplicate-code census is new.

| Measure | Proedge | Atlas | VTL |
| --- | --- | --- | --- |
| Employee rows | 160 | 151 | 1,420 |
| Distinct `id` | 160 | 151 | 1,420 |
| `id` `NULL` or duplicate groups | 0 | 0 | 0 |
| `emp_id_no` `NULL` | 0 | 0 | 0 |
| `emp_id_no` `''` | 0 | 0 | 0 |
| `emp_id_no` whitespace only | 0 | 0 | 0 |
| Distinct non-empty `emp_id_no` | 160 | 151 | 1,413 |
| Exact duplicate code groups | 0 | 0 | 7 |
| Rows inside those groups | 0 | 0 | 14 |
| Trimmed case-folded duplicate groups | 0 | 0 | 7 |
| No usable name | 0 | 0 | 1 |
| `joining_date` `NULL` | 0 | 0 | 6 |
| `joining_date` `0000-00-00` | 1 | 3 | 61 |
| `joining_date` ISO `YYYY-MM-DD` other than zero | 159 | 148 | 1,353 |
| `active` / `disconrinue` = `1` / `0` | 44 | 59 | 233 |
| `0` / `1` | 91 | 18 | 1 |
| `1` / `1` | 19 | 74 | 1,186 |
| `0` / `0` | 6 | 0 | 0 |
| `discontinue_date` `NULL` | 43 | 64 | 367 |
| `discontinue_date` `0000-00-00` | 1 | 0 | 81 |
| `discontinue_date` other ISO date | 116 | 87 | 972 |
| `employment_history` non-empty text | 51 | 0 | 11 |

Rows and distinct codes are separate. VTL has 1,420 rows and 1,413 distinct codes because seven codes each appear on two rows. Exact and trimmed case-folded duplicate counts are the same, so those seven groups are not spacing or case variants of each other.

VTL duplicate codes, exact text, with both `employees.id` values:

| `emp_id_no` | Rows | `employees.id` |
| --- | --- | --- |
| `10024` | 2 | 255, 100497 |
| `15575` | 2 | 270, 398 |
| `132058` | 2 | 100348, 100427 |
| `6119` | 2 | 100470, 100472 |
| `10023` | 2 | 100480, 100637 |
| `10027` | 2 | 100582, 100638 |
| `10034` | 2 | 100586, 100639 |

Both-true rows whose `discontinue_date` is `NULL`: Proedge 0, Atlas 5, VTL 137. The intersection of both-true with `0000-00-00` was not counted separately. VTL samples include that zero date on both-true rows.

Proedge `employees.id` 1 has `emp_id_no` text `emp_id_no`, `active` 0, `disconrinue` 0, `joining_date` `0000-00-00`, and `emp_unit` 0. That is the dry-run template row, confirmed in this dump. Its name has at least one usable component. The name text is omitted here.

Whether `0000-00-00` is registered as a missing date is **UNRESOLVED**. Recommended decision: treat it as missing, because it is not a calendar date and the approved rule does not invent a date. Keep the zero-date count visible beside the `NULL` count.

## 6. Atlas `EB9091`

One Atlas employee row matches code `EB9091` exactly.

| Field | Value |
| --- | --- |
| `employees.id` | 7175 |
| `emp_id_no` | `EB9091` |
| `active` | 1 |
| `disconrinue` | 1 |
| `discontinue_date` | 2026-08-25 |
| `joining_date` | 2021-05-02 |
| `emp_unit` | 26 |
| `dis` | 0 |
| `old_id` | `No` |

**VERIFIED FROM DUMP** for those fields. The earlier audit’s September attendance and the absence of a September salary row were not re-read in this pass. Those two points stay **DOCUMENTED ONLY**. The both-flags conflict remains an explicit exception. This step does not choose `Open`, `inactive`, or `separated`.

`EB9091` is one of 74 Atlas rows with both flags true. The pair is a common stored state in this dump, not a single damaged row.

## 7. Company ids versus salary keys

| Claim | Evidence | Class |
| --- | --- | --- |
| Catalog 24 is Pro-edge Associates Limited, alias `proedge`, database `proedgea_newproedgehrm`, `previous_db_id` 5 | Dry-run report prints that `legacy_master.companies` row | **DOCUMENTED ONLY** |
| Catalog company 5 is UL VS Bangladesh Ltd., a different company from catalog 24 | Same dry-run paragraph. The catalog row itself is not reprinted there | **DOCUMENTED ONLY** |
| Catalog 20 is Viyellatex Group, alias `vtl`, database `proedgea_vtlhrm` | Mapping validation, from its prior catalog query | **DOCUMENTED ONLY** |
| Catalog 10 is Atlas Copco Bangladesh Ltd., alias `atlas`, database `proedgea_atlas` | Third-company audit | **DOCUMENTED ONLY** |
| Catalog 26 is Grameen Telecom Trust, a different company from Atlas | Third-company audit. `legacy_master` was not opened here | **DOCUMENTED ONLY** |
| Workbook maps salary `company_id` 5, 5, and 26 onto catalog 24, 20, and 10 | Workbook section 3. Approved as a mapping rule in Step 20E | **DOCUMENTED ONLY** as the rule. The master catalog was not re-read |
| Dump header databases are `proedgea_newproedgehrm`, `proedgea_atlas`, and `proedgea_vtlhrm` | Dump headers | **VERIFIED FROM DUMP** |
| Local `companies.id` is 5, 5, and 26, with the names in section 2 | Those three `companies` inserts | **VERIFIED FROM DUMP** |
| `employees.emp_unit` repeats 5, 5, and 26 as in section 2 | Employee inserts | **VERIFIED FROM DUMP** |

`employee_basic.company_id` in these dumps:

| Dump | Rows | `company_id` counts |
| --- | --- | --- |
| Proedge | 313 | 5 on 313 |
| Atlas | 272 | 26 on 269, 0 on 3 |
| VTL | 1,319 | 5 on 1,317, 0 on 2 |

`employee_salary.company_id`:

| Dump | Rows | What is stored |
| --- | --- | --- |
| Proedge | 58,399 | 5 on every row |
| VTL | 865,781 | 5 on 865,769 rows, 6 on 7 rows, 20 on 5 rows |
| Atlas | 1,745,317 | Many values. 26 is 109,994 rows. Other stored values and row counts: 0 (1), 8 (14,944), 14 (11,399), 15 (2,143), 16 (81,308), 18 (967,379), 19 (261,993), 20 (18,670), 24 (3,673), 25 (11,290), 27 (10,368), 28 (19,380), 30 (52,404), 31 (67,740), 32 (1,079), 33 (19,530), 34 (48), 35 (62,507), 36 (3,045), 37 (13,081), 38 (11,565), 39 (672), 40 (1,104) |

**VERIFIED FROM DUMP.** This is the full salary history inside each file, not the single audited month. The September Atlas slice of 1,239 rows at `company_id` 26 remains the third-company audit’s month finding.

`employee_basic.emp_official_id` was compared with `employees.emp_id_no` by exact string match. Proedge: 0 of 122 distinct non-empty official ids match. Atlas: 0 of 81. VTL: 8 of 365. A trimmed comparison was not done. `emp_official_id` is not a safe substitute for `emp_id_no`. That equality stays **UNRESOLVED** after normalization.

Salary keys are not tenant identifiers, on this evidence:

- Local id 5 names Proedge in one dump and Viyellatex in another. **VERIFIED FROM DUMP.**
- Atlas salary rows store many `company_id` values inside the Atlas database. One number from that column does not select the company. **VERIFIED FROM DUMP.**
- Approved audits say catalog 5 and catalog 26 are different companies from Proedge, VTL, and Atlas. **DOCUMENTED ONLY** here, because `legacy_master` was not re-read.
- The target tenant remains catalog 24, 20, or 10 under the approved workbook rule.

The five VTL salary rows stored with `company_id` 20, and the seven stored with `6`, do not identify a second catalog tenant. Their meaning is **UNRESOLVED**.

## 8. Employment-history limitations

`employees.employment_history` is a `text` column. Proedge has non-empty text on 51 rows, VTL on 11, Atlas on 0. The text was not parsed. No approved parser or approval policy exists for turning that text into episodes.

Step 20F already showed `epf_service_book_second_part` with text joining and discontinue dates keyed by `emp_id_no`, and organogram tables keyed by reporting ids. Those definitions do not prove an employment-episode timeline. Department, designation, location, and shift columns remain attributes. They were not read as episode boundaries.

What the schema proves: each current `employees` row can carry one `joining_date`, one `discontinue_date`, and an optional free-text history. What it does not prove: how many employment episodes a person had, or that a populated text cell is a structured episode list.

## 9. Remaining business decisions and migration blockers

- Display-name composition is **UNRESOLVED**. The recommendation is in section 3. VTL `employees.id` 437, code `PAL1234`, is an empty-name exception under that recommendation.
- Treating `0000-00-00` as a missing joining date is **UNRESOLVED**. Counts are in section 5. Proedge id 1 is the zero-date row in that dump.
- Both flags true is common: 19 Proedge rows, 74 Atlas rows, 1,186 VTL rows. Automatic `Open`, `inactive`, or `separated` stays prohibited. Episode status for that pair is a migration blocker until a reviewer rule or per-row disposition exists. Atlas id 7175, code `EB9091`, stays inside that blocker.
- Proedge `0` / `0` remains 6 rows, including id 1. That pair is not stored as `inactive`.
- VTL has seven duplicate `emp_id_no` groups. No merge and no recode.
- `dis` meaning is **UNRESOLVED**. `old_id` is not an identity key.
- Catalog rows 5 and 26 were not re-read from `legacy_master`. Their “different company” status stays **DOCUMENTED ONLY**.
- These counts are dump counts. The stopped Laragon copies were not compared.
- No company is migration-approved.

`source_key` remains `employees.id`. `employee_code` remains the exact `emp_id_no` text. Target `employees.id` remains a new ULID.

## 10. Recommended next step

Approve or reject the display-name rule in section 3, and decide whether `0000-00-00` is a missing date. Then build the empty exception register from the dump counts in this report, including the seven VTL duplicate groups, VTL id 437, Proedge id 1, and the both-flags rows, without loading employees. Do not approve Proedge, Atlas, or VTL for migration while both-flags episode status and the display-name rule are still open.

## 11. Viyellatex duplicate-code disposition worksheet

Added 2026-10-17 from the same Viyellatex dump named in section 2, `proedgea_vtlhrm`. This section does not choose a canonical row. CODE-APPROVED and NOMERGE-APPROVED stay in force. D4 stays a proposed group hold. Every group below is unresolved. A row becomes the retained record only when an authorized owner writes that choice, the evidence used, and the date.

Row A is the lower `employees.id` and row B is the higher id. That order is storage order. It is not a validity order. All fourteen rows have local `emp_unit` 5, which is Viyellatex inside this database, not catalog company 20. `active` 1 with `disconrinue` 1 stays the stored pair. D3 does not turn that pair into a status. `0000-00-00` stays an unresolved date. Attendance and salary counts are stored activity on the numeric id. They do not select the valid employee. The punch map is keyed by `emp_code`, so it does not belong to one of the two ids.

| Code | Row | `employees.id` | Name | Joining date | Department | Designation | `active` / `disconrinue` | Leaving date | Type | Shift id | Boss id | Branch id | Attendance rows | Attendance span | Salary rows | Salary span |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| `10024` | A | 255 | MOHAMMAD IQRAMUL HOSSAIN | 2008-08-09 | 4 COMMERCIAL | 31 EXE | 1 / 1 | `0000-00-00` | Resignation | 0 | 0 | 4 | 0 | — | 53 | 2014-07 through 2014-09 |
| `10024` | B | 100497 | Abu Yousuf | 2023-11-13 | 5 ACCOUNTS & FINANCE | 7 EXECUTIVE | 1 / 1 | NULL | empty | 14 | 100297 | 29 | 452 | 2023-12-01 through 2025-02-24 | 378 | 2023-12 through 2025-01 |
| `15575` | A | 270 | ABU NILU MOSTAFA JAMAL | 2011-07-02 | 20 IT | 32 SR EXE | 1 / 1 | 2016-07-01 | Resignation | 0 | 264 | 4 | 320 | 2015-09-01 through 2016-07-16 | 492 | 2014-07 through 2016-06 |
| `15575` | B | 398 | S M BORHAN UDDIN | 2014-01-11 | 6 DYEING | 7 EXECUTIVE | 1 / 1 | 2016-07-31 | Resignation | 0 | 506 | 2 | 353 | 2015-09-01 through 2016-08-17 | 430 | 2014-12 through 2016-07 |
| `132058` | A | 100348 | Md. Fahaduzzaman | 2021-05-02 | 70 AAMRA IT | 7 EXECUTIVE | 1 / 1 | NULL | empty | 14 | NULL | 28 | 395 | 2021-09-01 through 2022-09-30 | 324 | 2021-10 through 2022-09 |
| `132058` | B | 100427 | Probir Protim Roy | 2022-09-01 | 70 AAMRA IT | 7 EXECUTIVE | 1 / 1 | NULL | empty | 14 | NULL | 28 | 1,490 | 2022-09-01 through 2026-09-29 | 1,296 | 2022-09 through 2026-08 |
| `6119` | A | 100470 | SHAMIM AHMED | 2023-05-27 | 5 ACCOUNTS & FINANCE | 7 EXECUTIVE | 1 / 1 | NULL | Long Absent | 19 | 100455 | 6 | 5 | 2023-05-27 through 2023-05-31 | 0 | — |
| `6119` | B | 100472 | CHOYON ROY | 2023-06-01 | 5 ACCOUNTS & FINANCE | 41 TRAINEE OFFICER | 1 / 1 | 2023-11-30 | Resignation | 18 | 100455 | 6 | 186 | 2023-06-01 through 2023-12-03 | 135 | 2023-06 through 2023-10 |
| `10023` | A | 100480 | Ariful Islam | 2023-08-13 | 5 ACCOUNTS & FINANCE | 7 EXECUTIVE | 1 / 1 | NULL | empty | 14 | 100456 | 29 | 779 | 2023-08-13 through 2025-09-30 | 702 | 2023-08 through 2025-09 |
| `10023` | B | 100637 | Ariful Islam | 2023-08-13 | 5 ACCOUNTS & FINANCE | 7 EXECUTIVE | 1 / 0 | NULL | empty | 14 | NULL | 29 | 374 | 2025-01-03 through 2026-10-08 | 324 | 2025-10 through 2026-09 |
| `10027` | A | 100582 | LIMON ROY | 2025-01-01 | 5 ACCOUNTS & FINANCE | 29 TRAINEE | 1 / 1 | NULL | empty | 14 | NULL | 29 | 242 | 2025-02-01 through 2025-09-30 | 189 | 2025-03 through 2025-09 |
| `10027` | B | 100638 | LIMON ROY | 2025-01-01 | 5 ACCOUNTS & FINANCE | 7 EXECUTIVE | 1 / 0 | NULL | empty | 14 | NULL | 29 | 373 | 2025-10-01 through 2026-10-08 | 324 | 2025-10 through 2026-09 |
| `10034` | A | 100586 | PROSHONJIT BAUL | 2025-01-01 | 5 ACCOUNTS & FINANCE | 29 TRAINEE | 1 / 1 | NULL | empty | 14 | NULL | 29 | 242 | 2025-02-01 through 2025-09-30 | 189 | 2025-03 through 2025-09 |
| `10034` | B | 100639 | PROSHONJIT BAUL | 2025-01-01 | 5 ACCOUNTS & FINANCE | 7 EXECUTIVE | 1 / 0 | NULL | empty | 14 | NULL | 29 | 373 | 2025-10-01 through 2026-10-08 | 324 | 2025-10 through 2026-09 |

Punch-card map rows for these codes. The map has no employee id.

| `emp_code` | Map rows | Card numbers | Other employee code on the same card |
| --- | --- | --- | --- |
| `10024` | 1 | `10024` | none in this dump |
| `15575` | 1 | `118441` | none in this dump |
| `132058` | 2 | `132058` | `03129399` also maps to card `132058` |
| `6119` | 2 | `6119` | none in this dump |
| `10023` | 2 | `10023` | none in this dump |
| `10027` | 2 | `10027` | none in this dump |
| `10034` | 2 | `10034` | none in this dump |

Stored bosses, for the ids that are not 0 or NULL: 264 code `14134`; 506 code `015650`; 100297 code blank on that row, name stored as DIRECTOR AMIR HASANAT; 100455 code `6118`; 100456 code `10001`. No employment-history text is stored on any of the fourteen rows.

Conflicts that block a choice from these fields alone:

- `10024`, `15575`, `132058`, and `6119` store different names on the two rows.
- `10023` stores the same name and joining date, with discontinue 1 on A and 0 on B. Attendance on the two ids overlaps from 2025-01-03 through 2025-09-30.
- `132058` attendance overlaps in September 2022. `15575` attendance overlaps from 2015-09-01 through 2016-07-16.
- `10027` and `10034` store the same name and joining date. The stored attendance spans meet at 2025-10-01 and do not overlap. The designation text differs, and the discontinue flag differs. That pattern still does not identify the valid row.
- Two map rows for one code, and card `132058` on a third code, do not assign the card to row A or row B.

Owner options for each group, and for no other outcome: retain row A and leave row B an unresolved exception; retain row B and leave row A an unresolved exception; or retain neither and leave both unresolved. No merge, no delete, and no recode.

| Code | Disposition | Owner | Date | Evidence the owner cites |
| --- | --- | --- | --- | --- |
| `10024` | Unresolved | | | |
| `15575` | Unresolved | | | |
| `132058` | Unresolved | | | |
| `6119` | Unresolved | | | |
| `10023` | Unresolved | | | |
| `10027` | Unresolved | | | |
| `10034` | Unresolved | | | |

IDENTITY AUDIT COMPLETE
