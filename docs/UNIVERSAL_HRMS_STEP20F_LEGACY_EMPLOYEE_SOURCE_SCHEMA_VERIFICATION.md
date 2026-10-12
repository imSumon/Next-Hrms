# Universal HRMS — Step 20F Legacy Employee Source Schema Verification

Step 20E is complete. The target `employees` and `employment_episodes` tables exist. This step identifies the legacy employee source columns for Proedge, Atlas, and VTL. It does not load rows, reconcile counts, or approve a company for migration.

Schema verification and record validation are separate. Counts cited from earlier audits were not recounted here.

## 1. Documents and source artifacts reviewed

| Artifact | What it contributed |
| --- | --- |
| `docs/UNIVERSAL_HRMS_STEP20E_EMPLOYEE_MIGRATION_MAPPING_AND_RECONCILIATION_DESIGN.md` | Mapping roles. Atlas and VTL physical primary-key and employee-code names were unresolved. The physical name column and joining-date column were unresolved. VTL discontinue spelling was unconfirmed |
| `docs/UNIVERSAL_HRMS_MULTI_COMPANY_MIGRATION_MAPPING_VALIDATION.md` | Catalog identities: Proedge 24 `proedgea_newproedgehrm` / local `legacy_proedge`; VTL 20 `proedgea_vtlhrm` / local `legacy_vtlhrm`; Atlas 10 `proedgea_atlas` |
| `docs/UNIVERSAL_HRMS_PROEDGE_AUGUST_2026_DRY_RUN_REPORT.md` | Prior Proedge record findings for `employees.id`, `emp_id_no`, `active`, and `disconrinue`. Not recounted |
| `docs/UNIVERSAL_HRMS_THIRD_REFERENCE_COMPANY_CLOSED_MONTH_AUDIT.md` | Prior Atlas record findings, including `disconrinue`, `discontinue_date`, `emp_unit` 26, and EB9091. Not recounted |
| `docs/UNIVERSAL_HRMS_MULTI_COMPANY_MIGRATION_MAPPING_WORKBOOK.md` | Role labels (“employee code”, “legacy primary key”) without Atlas or VTL physical column names |
| `backend/modules/pims/models/Employees.php` | Shared Yii model for table `employees`. Application documentation of column names. Physical types below come from the dumps |
| `legacy-data/companies/proedgea_newproedgehrm.sql` | Local dump. Header database `proedgea_newproedgehrm`. mysqldump 8.0.46, host localhost |
| `legacy-data/companies/proedgea_atlas.sql` | Local dump. Header database `proedgea_atlas` |
| `legacy-data/companies/vtl/proedgea_vtlhrm.sql.gz` | Local gzip dump. Header database `proedgea_vtlhrm` |

`legacy-data/companies/proedgea_payroll_aug_13.sql` and `legacy-data/master/proedgea_payrollmaster.sql` were identified and not used as employee sources. The master dump was not scanned.

## 2. Source availability and connection safety

No database connection was opened.

Local Laragon data directories exist under `D:\laragon\data\mysql-8.4`: `legacy_proedge`, `legacy_atlas`, `legacy_vtlhrm`, `legacy_master`, and `legacy_proedge_aug2026_dryrun`. `mysqld` was not running. Port 3306 had no listener. Those directories were not opened, so this step does not claim they match the dump files.

The three dump files are local artifacts. Their headers name the catalog database names already documented for companies 24, 20, and 10. Reading `CREATE TABLE` from those files does not connect to a server and does not modify data. No credentials were used.

Salary and payroll row contents were not extracted. `employee_basic` was read only as `CREATE TABLE`, far enough to see that its `company_id` sits on a monthly basic-pay table.

## 3. Schema mapping matrix

The employee identity table in each dump is `employees`. Each statement ends with `PRIMARY KEY (`id`)` and `ENGINE=MyISAM`. There is no `UNIQUE` key on `emp_id_no`. There is no `company_id` column on `employees`.

| Role | Proedge | Atlas | VTL | Physical type in all three dumps |
| --- | --- | --- | --- | --- |
| Table | `employees` | `employees` | `employees` | MyISAM |
| Primary key | `employees.id` | `employees.id` | `employees.id` | `int NOT NULL AUTO_INCREMENT` |
| Employee code | `employees.emp_id_no` | `employees.emp_id_no` | `employees.emp_id_no` | `varchar(50) DEFAULT NULL` |
| Name parts | `emp_first_name`, `emp_mid_name`, `emp_last_name` | same column names | same column names | each `varchar(128) DEFAULT NULL` |
| Joining date | `employees.joining_date` | `employees.joining_date` | `employees.joining_date` | `date DEFAULT NULL` |
| Active flag | `employees.active` | `employees.active` | `employees.active` | `tinyint(1) DEFAULT '1'` |
| Discontinued flag | `employees.disconrinue` | `employees.disconrinue` | `employees.disconrinue` | `tinyint(1) DEFAULT '0'` |
| Leaving date | `employees.discontinue_date` | `employees.discontinue_date` | `employees.discontinue_date` | `date DEFAULT NULL` |
| Ownership | Database `proedgea_newproedgehrm` | Database `proedgea_atlas` | Database `proedgea_vtlhrm` | No tenant column on `employees` |

`emp_unit` is `int DEFAULT NULL` on `employees` in all three dumps. It is a legacy column on the employee row. It is not the catalog company id.

The shared Yii model names these same columns. The dumps are the physical source for types. The model’s PHP `int` annotations for `active` and `disconrinue` are wider than the dump type `tinyint(1)`.

### Definitions that differ across the three dumps

The identity columns above match. The tables are not identical:

| Item | Proedge | Atlas | VTL |
| --- | --- | --- | --- |
| Table charset | `latin1` | `utf8mb3` | `latin1` |
| `grade` default | `'0'` | `NULL` | `'0'` |
| `shift_id` default | `'5'` | `NULL` | `'5'` |
| `tax_loaction_id` | `int DEFAULT NULL` | `int NOT NULL` | `int DEFAULT NULL` |
| `weekly_holiday` default | `NULL` | `NULL` | `'Friday'` |
| `emp_passport_no` length | `varchar(255)` | `varchar(50)` | `varchar(255)` |

Column order also differs. Atlas places `boss` and `g_father` later in the table than Proedge and VTL. Same names in the identity set are evidenced per dump. They are not assumed from one company onto another.

### Other columns and tables seen, and not used as identity

| Object | What the dump shows | Use in this step |
| --- | --- | --- |
| `employees.employment_history` | `text` on the employee row in all three dumps | A text column. It is not an employment-episode table |
| `employees.dis` | `int DEFAULT '0'` in all three dumps | A different column from `disconrinue`. Meaning unresolved |
| `employees.old_id` | `varchar(255) DEFAULT NULL` in all three dumps | Present. It is not the legacy primary key |
| `employees.discontinue_type` | `varchar(100) DEFAULT NULL` | Present beside the leaving date |
| `employees.discontinue_description` | `text` | Present beside the leaving date |
| `employee_basic` | `id`, `employee_id`, `emp_official_id`, `basic_month`, `basic_year`, `tatal_basic`, `company_id`. Primary key `id`. InnoDB. Same shape in all three dumps | Monthly basic-pay table. `company_id` here is not a catalog tenant id. Stored values were not read |
| `employee_officiating` | Monthly officiating amounts, `emp_official_id`, `company_id` | Pay table. Not an episode table |
| `employee_work_location` | `company_id`, `code_name`, `type` enum of Unit, Floor, Section, Sub Section, Line | Location catalog. It has no employee key |
| `employees_for_organogram` | `emp_id`, `emp_id_no`, reporting ids | Reporting snapshot. Not an episode table |
| `organogram_employee` | `boss_id`, `emp_id`, `parent` | Reporting link. Not an episode table |
| `epf_service_book_second_part` | `emp_id_no`, `joining_date` text, `discontinue_date` text, `discontinue_reason` text. Same shape in all three dumps | Service-book form keyed by employee code. Dates are text. Not adopted as employment episodes |

A scan of `CREATE TABLE` bodies found `joining_date` or `discontinue_date` also on fund, maternity, and candidate tables in some dumps. Those tables were not adopted as the employee episode source. No dump table was found whose shape is an employment episode of one person at one company.

## 4. Evidence and confidence

Verification method for every **VERIFIED** row is the `CREATE TABLE` statement in the local dump named in section 2. No `INFORMATION_SCHEMA` query was run. No employee row was read. Earlier audit counts stay prior findings.

| Company | Table.column | Physical type | Evidence | Status |
| --- | --- | --- | --- | --- |
| Proedge | `employees.id` | `int NOT NULL AUTO_INCREMENT`, primary key | `proedgea_newproedgehrm.sql` | **VERIFIED** |
| Proedge | `employees.emp_id_no` | `varchar(50) DEFAULT NULL`, not unique | same dump | **VERIFIED** |
| Proedge | `emp_first_name`, `emp_mid_name`, `emp_last_name` | `varchar(128) DEFAULT NULL` | same dump | **VERIFIED** |
| Proedge | `employees.joining_date` | `date DEFAULT NULL` | same dump | **VERIFIED** |
| Proedge | `employees.active` | `tinyint(1) DEFAULT '1'` | same dump | **VERIFIED** |
| Proedge | `employees.disconrinue` | `tinyint(1) DEFAULT '0'` | same dump | **VERIFIED** |
| Proedge | `employees.discontinue_date` | `date DEFAULT NULL` | same dump | **VERIFIED** |
| Atlas | `employees.id` | `int NOT NULL AUTO_INCREMENT`, primary key | `proedgea_atlas.sql` | **VERIFIED** |
| Atlas | `employees.emp_id_no` | `varchar(50) DEFAULT NULL`, not unique | same dump | **VERIFIED** |
| Atlas | `emp_first_name`, `emp_mid_name`, `emp_last_name` | `varchar(128) DEFAULT NULL` | same dump | **VERIFIED** |
| Atlas | `employees.joining_date` | `date DEFAULT NULL` | same dump | **VERIFIED** |
| Atlas | `employees.active` | `tinyint(1) DEFAULT '1'` | same dump | **VERIFIED** |
| Atlas | `employees.disconrinue` | `tinyint(1) DEFAULT '0'` | same dump | **VERIFIED** |
| Atlas | `employees.discontinue_date` | `date DEFAULT NULL` | same dump | **VERIFIED** |
| VTL | `employees.id` | `int NOT NULL AUTO_INCREMENT`, primary key | `proedgea_vtlhrm.sql.gz` | **VERIFIED** |
| VTL | `employees.emp_id_no` | `varchar(50) DEFAULT NULL`, not unique | same dump | **VERIFIED** |
| VTL | `emp_first_name`, `emp_mid_name`, `emp_last_name` | `varchar(128) DEFAULT NULL` | same dump | **VERIFIED** |
| VTL | `employees.joining_date` | `date DEFAULT NULL` | same dump | **VERIFIED** |
| VTL | `employees.active` | `tinyint(1) DEFAULT '1'` | same dump | **VERIFIED** |
| VTL | `employees.disconrinue` | `tinyint(1) DEFAULT '0'` | same dump. Spelling is `disconrinue` | **VERIFIED** |
| VTL | `employees.discontinue_date` | `date DEFAULT NULL` | same dump | **VERIFIED** |
| All three | Catalog company 24, 20, 10 | Catalog ids and database names | Mapping validation document | **DOCUMENTED ONLY** in this step. The master dump and `legacy_master` were not re-read |
| All three | Salary keys 5 and 26 | Prior audits assign 5 to Proedge and VTL salary rows and 26 to Atlas salary rows | Earlier audits. `employee_basic.company_id` exists; its values were not read | **DOCUMENTED ONLY** for the values. The column’s presence on the pay table is **VERIFIED** |
| All three | Duplicate codes, empty names, missing joining dates, flag-pair counts | Prior Proedge and Atlas audits | Those reports | **DOCUMENTED ONLY**. Not recounted |
| Atlas | EB9091 both flags | `active` = 1 and `disconrinue` = 1, `discontinue_date` 2026-08-25, September attendance, no September salary | Third-company audit | **DOCUMENTED ONLY**. The row was not re-read |
| All three | Laragon `legacy_proedge`, `legacy_atlas`, `legacy_vtlhrm` match these dumps | Data directories exist. Server was stopped | Directory listing only | **NOT AVAILABLE** |
| All three | `employees.dis` meaning | Column exists | Dumps | **UNRESOLVED** |
| All three | `employees.old_id` meaning | Column exists | Dumps | **UNRESOLVED** |
| All three | `employee_basic.emp_official_id` equals `employees.emp_id_no` | Both columns exist | Dumps show types only | **UNRESOLVED** |
| All three | How the three name columns become one `display_name` | Three nullable `varchar(128)` columns | Dumps | **UNRESOLVED** composition rule |
| All three | Employment-episode table | Text `employment_history` and the service-book table exist | Dumps | **NOT AVAILABLE** as a confirmed episode table |

## 5. Remaining unknowns

- The stopped Laragon copies were not compared with these dumps.
- Catalog rows 24, 20, and 10 were not re-read from `legacy_master` or the master dump.
- Stored salary `company_id` values 5 and 26 were not re-read. They remain prior findings and are not tenant identifiers.
- Duplicate `emp_id_no` groups, empty names, null `joining_date`, and flag-pair counts were not recounted. The dump allows null `emp_id_no` and does not declare it unique, which is a schema fact, not a census.
- `employees.dis` and `employees.old_id` have no confirmed meaning.
- `employee_basic.emp_official_id` was not compared with `employees.emp_id_no`.
- The rule that concatenates `emp_first_name`, `emp_mid_name`, and `emp_last_name` into `display_name` is not decided.
- `epf_service_book_second_part` stores text dates against an employee code. It is not confirmed episode history.
- Atlas EB9091 was not re-read.

## 6. Migration implications

Target `employees.id` stays a new ULID. The legacy identity copied into `employees.source_key` is `employees.id` from the company dump: Proedge, Atlas, and VTL each use that integer primary key. `employees.emp_id_no` is the business code copied into `employees.employee_code`. The code is not the identity.

These rules stay in force:

- No automatic identity merges.
- No silent recoding of `emp_id_no`.
- Duplicate codes, empty names, missing `joining_date`, and conflicting `active` / `disconrinue` values stay explicit exceptions.
- Atlas EB9091 stays a documented both-flags exception until an approved resolution. This step did not resolve it.
- An unclear flag pair is not stored as `inactive`.
- `employee_basic.company_id`, salary keys 5 and 26, and `emp_unit` do not select the target company. Catalog 24, 20, and 10 remain the documented tenants.
- `employment_history` text, organogram tables, work-location catalogs, and the service-book table do not create `employment_episodes` rows.
- No company is migration-approved because these columns are now named.

`source_system` values already designed for employee rows remain `legacy_proedge`, `legacy_atlas`, and `legacy_vtlhrm`. `source_system` on the company row itself stays unresolved, as in Step 20E.

## 7. Recommended next step

Compare these dump definitions with the Laragon copies `legacy_proedge`, `legacy_atlas`, and `legacy_vtlhrm` in a read-only session, once that local server is running and the connection is confirmed local. Until that comparison, treat the dump `CREATE TABLE` statements as the schema evidence.

The empty exception register from Step 20E is still unbuilt. Build it without loading rows. Do not approve Proedge, Atlas, or VTL for migration from this review.

PARTIALLY VERIFIED
