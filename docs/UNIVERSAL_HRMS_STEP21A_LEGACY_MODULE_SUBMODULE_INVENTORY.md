# Universal HRMS — Step 21A Legacy Module, Submodule, and Controller Inventory

Status: **INVENTORY DOCUMENTED FROM THE MASTER DUMP**. A live database was not queried.

This inventory is a catalog of rows. It is not a behavior audit and it does not approve a requirement, a policy value, or an employee migration.

## 1. Source and verification method

| Item | Value |
| --- | --- |
| Verification date | 2026-10-11 |
| Live database | Not queried. Port 3306 had no listener, and no MySQL service was listed. MySQL was not started |
| Dump file | `D:\Project -All\Proedge HRMS\legacy-data\master\proedgea_payrollmaster.sql` |
| Dump size and timestamp | 3,635,806,039 bytes. Last write 2026-10-02 15:20:11 |
| Dump header | `-- Host: localhost    Database: proedgea_payrollmaster` |
| Tables | `modules`, `sub_modules`, `controllers`. One `INSERT` each |
| Method | Offline read of those three `INSERT` statements. Values were split on commas outside quotes. The statement-ending `);` was restored before the last field was read |
| Local folder `legacy_master` | Not opened. This dump was not compared with that copy |

The Yii master connection in `common\config\main-local.php` names `proedgea_payrollmaster`. That is the database name in the dump header. It is not, by itself, proof that a restored database named `legacy_master` still matches this file.

Each `CREATE TABLE` has a primary key on `id` and no foreign key. Relationship checks below are value checks. The schema does not enforce them.

Column layouts as dumped:

- `modules`: `id`, `mname`, `active` enum `Yes`/`No` default `Yes`, `mpriority`, `mtitle`, `icon`, `app_icon`. `AUTO_INCREMENT=33`.
- `sub_modules`: `id`, `module_id` default 0, `name`, `priority` default 0. `AUTO_INCREMENT=112`.
- `controllers`: `id`, `module_id` default 0, `cname`, `sub_module_id`, `cpriority` default 0, `ctitle`. `AUTO_INCREMENT=488`.

Parsed field counts: 25 module rows with 7 fields, 101 submodule rows with 4 fields, 416 controller rows with 6 fields.

## 2. Counts and the previously reported figures

| Figure | Previously reported | This dump parse |
| --- | ---: | --- |
| Modules | 25 | 25 rows. Ids 1, 2, 3, 4, 5, 6, 8, 9, 12, 13, 16, 18, 19, 20, 21, 22, 23, 24, 25, 27, 28, 29, 30, 31, 32. Absent ids 7, 10, 11, 14, 15, 17, 26 |
| Active status | Not restated here as a prior split | `Yes` 18, `No` 7. No other value |
| Submodules | 99 | **101 rows**. Max id 111. Absent ids 29, 31, 33, 34, 37, 40, 41, 50, 56, 78. The figure 99 was not reproduced |
| Controllers | 416 | 416 rows. Ids 2 through 487, with 70 ids in that span absent. Id 1 is absent |
| Relationships valid | All 416 | **397 of 416** pass the three checks in section 6. **19 do not**. Each table has a primary key on `id` and no foreign key |

Two submodule rows are structurally incomplete: id 36 points at module id 10, which has no `modules` row, and id 71 has `module_id` NULL. Subtracting those two from 101 equals 99. That arithmetic is recorded here. It is not evidence that an earlier audit used that filter.

Primary keys are unique in each table. `mname` is unique. The pair (`module_id`, `name`) is unique among submodules. Names and spellings are copied as stored, including `Personal files`, `Secretariate`, and the leading space on submodule 97.

The dump also contains `actions` (`AUTO_INCREMENT=8`, seven inserted rows). Those rows are not part of the 416.

## 3. Filesystem directories are a different inventory

On 2026-10-11 the backend folder `legacy-reference\hrms\backend\modules` had 25 directories whose names do not start with `00`, and Step 21 counted 346 `*Controller.php` files in those directories. That remains a filesystem count.

Both inventories have 25 entries. They are not the same 25.

| Only in the database catalog | Only in the filesystem directory list |
| --- | --- |
| `averydata` (id 20, active `No`) | `appadmin` |
| `wppfmanagement` (id 23, active `Yes`) | `reports` |

Catalog `cname` values include paths such as `employees/discontinue`. A catalog row is not a PHP class count. The 416 and the 346 must stay separate.

## 4. Module rows

Inactive rows are kept. `mpriority` is stored as dumped, including shared values 19 and 20.

| id | mname | mtitle | active | mpriority | icon | app_icon | Catalog controllers | Behavior evidence already on file |
| ---: | --- | --- | --- | ---: | --- | --- | ---: | --- |
| 1 | organizationsettings | Organization Setting | No | 1 | fa-gears | organizationsettings.png | 26 | Inventory, plus the company-catalog mapping already recorded. Screens not audited. |
| 2 | pims | PIMS | No | 2 | fa-users | pims.png | 15 | Inventory, plus the employee-row census. Other PIMS screens not audited. |
| 3 | payroll | Payroll | Yes | 3 | fa-money | payroll.png | 104 | Inventory, plus salary and paid-overtime rows in the three studied months. The other catalog controllers were not audited. |
| 4 | leave | Leave | Yes | 4 | fa-street-view | leave.png | 21 | Inventory, plus leave-day effects in the three studied months. The request workflow was not audited end to end. |
| 5 | attendance | Attendance | Yes | 5 | fa-clock-o | attendance.png | 29 | Inventory, plus the three closed-month attendance audits. Not every catalog controller was traced. |
| 6 | pms | PMS | Yes | 6 | fa-cubes | pms.png | 48 | Inventory only. No behavior audit of this module is on file. |
| 8 | recruitment | Recruitment | Yes | 8 | fa-user-plus | recruitment.png | 7 | Inventory only. No behavior audit of this module is on file. |
| 9 | documents | File Cabinet | Yes | 9 | fa-files-o | documents.png | 4 | Inventory only. No behavior audit of this module is on file. |
| 12 | pfmanagement | PF Management | Yes | 12 | fa-calculator | pfmanagement.png | 40 | Inventory only. Atlas September recorded a PF amount match. That match did not audit this module. |
| 13 | gfmanagement | GF Management | Yes | 13 | fa-credit-card | gfmanagement.png | 22 | Inventory only. No behavior audit of this module is on file. |
| 16 | atlasbills | Atlas Bills | Yes | 14 | fa-tasks | atlasbills.png | 4 | Inventory only. The workbook already names Atlas Bills as an optional extension, off by default. Bill workflows were not audited. |
| 18 | nmsdata | NMS Data | No | 16 | fa-database | nmsdata.png | 2 | Inventory only. The workbook already names NMS as a client extension. The calculation was not audited. |
| 19 | tonicdata | Tonic Data | No | 17 | fa-database | tonicdata.png | 4 | Inventory only. No behavior audit of this module is on file. |
| 20 | averydata | Avery Data | No | 20 | fa-database | averydata.png | 2 | Inventory only. No behavior audit of this module is on file. |
| 21 | mobilebillmanagement | Mobile Bill | Yes | 19 | fa-mobile | mobilebillmanagement.png | 2 | Inventory only. No behavior audit of this module is on file. |
| 22 | lunchmanagement | Lunch Management | Yes | 19 | fa-coffee | lunchmanagement.png | 5 | Inventory only. No behavior audit of this module is on file. |
| 23 | wppfmanagement | Workers Profit Participation Fund | Yes | 20 | fa-cubes | wppfmanagement.png | 0 | Inventory only. No behavior audit of this module is on file. |
| 24 | personalfiles | Personal files | Yes | 18 | fa-paste | personalfiles.png | 13 | Inventory only. No behavior audit of this module is on file. |
| 25 | billmanagement | Bill Management | Yes | 21 | fa-book | billmanagement.png | 7 | Inventory only. No behavior audit of this module is on file. |
| 27 | assessment360degree | 360 Degree Assessment | Yes | 23 | fa-clock-o | pms.png | 11 | Inventory only. No behavior audit of this module is on file. |
| 28 | attendancealllog | Attendance All Log | No | 25 | fa-clock-o | NULL | 0 | Inventory only. The shared punch log used in those months is not this module row. |
| 29 | proedgedata | Proedge Data | No | 24 | fa-clock-o | NULL | 8 | Inventory only. No behavior audit of this module is on file. |
| 30 | hourlyleave | Leave Management (Hourly) | Yes | 26 | fa-street-view | leave.png | 8 | Inventory only. No behavior audit of this module is on file. |
| 31 | dataarchive | Data Archive | Yes | 27 | fa-clock-o | NULL | 3 | Inventory only. No behavior audit of this module is on file. |
| 32 | multiraterassessment | Multi Rater Assessment | Yes | 28 | fa-clock-o | mra.png | 15 | Inventory only. No behavior audit of this module is on file. |

Controller rows whose `module_id` is not one of those 25: module_id 10 has 2, module_id 0 has 3, module_id NULL has 11.

## 5. Submodule rows and controller counts

| id | module_id | Parent module | name | priority | Controllers |
| ---: | --- | --- | --- | ---: | ---: |
| 1 | 1 | organizationsettings | Basic Settings | 1 | 6 |
| 2 | 1 | organizationsettings | Other Settings | 2 | 9 |
| 3 | 1 | organizationsettings | Bank Settings | 3 | 2 |
| 4 | 2 | pims | PIMS | 1 | 7 |
| 5 | 3 | payroll | Payroll Basic | 1 | 10 |
| 6 | 3 | payroll | Appraisal | 2 | 5 |
| 7 | 3 | payroll | Provident Fund | 3 | 8 |
| 8 | 3 | payroll | Gratuity | 4 | 4 |
| 9 | 3 | payroll | Incentive | 5 | 4 |
| 10 | 3 | payroll | Benefits | 6 | 2 |
| 11 | 3 | payroll | Bonus | 7 | 5 |
| 12 | 3 | payroll | Tax | 8 | 4 |
| 13 | 3 | payroll | Shift Overtime | 9 | 3 |
| 14 | 3 | payroll | Officiating | 10 | 3 |
| 15 | 3 | payroll | Lunch Disturbance | 11 | 3 |
| 16 | 3 | payroll | Overtime | 12 | 2 |
| 17 | 3 | payroll | Reimbursement | 13 | 2 |
| 18 | 3 | payroll | Loan | 14 | 5 |
| 19 | 3 | payroll | Reports Setting | 24 | 2 |
| 20 | 3 | payroll | Reports | 25 | 1 |
| 21 | 3 | payroll | Lunch | 17 | 3 |
| 22 | 3 | payroll | Loan with Interest | 18 | 3 |
| 23 | 3 | payroll | Advance | 19 | 2 |
| 24 | 3 | payroll | Additional Module 1 | 20 | 2 |
| 25 | 3 | payroll | Additional Module 2 | 21 | 2 |
| 26 | 3 | payroll | Additional Module 3 | 22 | 2 |
| 27 | 3 | payroll | Additional Module 4 | 23 | 2 |
| 28 | 3 | payroll | Dynamic Module | 15 | 2 |
| 30 | 4 | leave | Leave | 1 | 12 |
| 32 | 6 | pms | PMS Data View | 4 | 13 |
| 35 | 9 | documents | File Cabinet | 1 | 4 |
| 36 | 10 | no modules row | Notification | 1 | 2 |
| 38 | 12 | pfmanagement | PF Management | 1 | 7 |
| 39 | 13 | gfmanagement | GF Settings | 1 | 6 |
| 42 | 1 | organizationsettings | Investment scheme | 5 | 1 |
| 43 | 1 | organizationsettings | Access Log | 6 | 1 |
| 44 | 1 | organizationsettings | Company Users | 4 | 3 |
| 45 | 2 | pims | PIMS Data Upload | 2 | 4 |
| 46 | 2 | pims | PIMS Reports | 3 | 4 |
| 47 | 18 | nmsdata | NMS Data | 1 | 2 |
| 48 | 19 | tonicdata | Tonic Data | 1 | 4 |
| 49 | 16 | atlasbills | Atlas Bills | 1 | 4 |
| 51 | 4 | leave | Leave Reports | 4 | 2 |
| 52 | 5 | attendance | Attendance Manage | 1 | 10 |
| 53 | 5 | attendance | Attendance Process & Reports | 2 | 13 |
| 54 | 5 | attendance | Attendance Data Upload & Download | 3 | 8 |
| 55 | 6 | pms | PMS | 1 | 9 |
| 57 | 4 | leave | Leave Data Upload | 3 | 5 |
| 58 | 20 | averydata | PAL-D | 1 | 2 |
| 59 | 12 | pfmanagement | PF Data | 4 | 6 |
| 60 | 12 | pfmanagement | PF Investment | 3 | 8 |
| 61 | 21 | mobilebillmanagement | Mobile Bill | 1 | 1 |
| 62 | 21 | mobilebillmanagement | Mobile Bill Report | 2 | 1 |
| 63 | 24 | personalfiles | EPF All files | 1 | 7 |
| 64 | 3 | payroll | MRB PF Statement | 26 | 2 |
| 65 | 24 | personalfiles | Service Book | 2 | 6 |
| 66 | 22 | lunchmanagement | Lunch Management | 1 | 4 |
| 67 | 22 | lunchmanagement | Lunch Report | 2 | 1 |
| 68 | 20 | averydata | Avery Data | 1 | 0 |
| 69 | 3 | payroll | GPOS & Tonic DR. | 27 | 3 |
| 70 | 3 | payroll | OT Management | 28 | 4 |
| 71 | NULL | NULL | CMA | 2 | 3 |
| 72 | 3 | payroll | Kantar Appreciation | 29 | 5 |
| 73 | 3 | payroll | Earn Leave Encashment | 30 | 2 |
| 74 | 1 | organizationsettings | Organogram | 7 | 6 |
| 75 | 25 | billmanagement | Bill Setting | 1 | 2 |
| 76 | 25 | billmanagement | Bills | 3 | 2 |
| 77 | 25 | billmanagement | Internal / External Setting | 2 | 3 |
| 79 | 3 | payroll | Tax Return 108 (A) | 31 | 1 |
| 80 | 8 | recruitment | Recruitment Requests | 2 | 1 |
| 81 | 8 | recruitment | Recruitment Settings | 1 | 2 |
| 82 | 8 | recruitment | Recruitment Data View | 3 | 4 |
| 83 | 12 | pfmanagement | PF Secretariate Approval | 5 | 5 |
| 84 | 12 | pfmanagement | PF Contribution | 2 | 7 |
| 85 | 12 | pfmanagement | PF Secretariate Reports | 6 | 10 |
| 86 | 13 | gfmanagement | GF Reports | 2 | 0 |
| 87 | 13 | gfmanagement | GF Investment | 3 | 10 |
| 88 | 13 | gfmanagement | GF Data | 4 | 6 |
| 89 | 27 | assessment360degree | 360 Data | 3 | 4 |
| 90 | 27 | assessment360degree | 360 Degree Assessment | 1 | 6 |
| 91 | 27 | assessment360degree | 360 Degree Report | 2 | 1 |
| 92 | 3 | payroll | QTEC | 31 | 4 |
| 93 | 29 | proedgedata | Upload Data | 1 | 4 |
| 94 | 29 | proedgedata | Process Data | 2 | 3 |
| 95 | 29 | proedgedata | Reports | 3 | 1 |
| 96 | 30 | hourlyleave | Hourly Leave Settings | 1 | 7 |
| 97 | 30 | hourlyleave | `Hourly Leave  Data Upload` | 2 | 0 |
| 98 | 30 | hourlyleave | Hourly Leave Report | 3 | 1 |
| 99 | 6 | pms | 360 Degree | 2 | 6 |
| 100 | 6 | pms | EDP | 3 | 9 |
| 101 | 6 | pms | 360 Degree Data View | 5 | 3 |
| 102 | 6 | pms | EDP Data View | 6 | 5 |
| 103 | 31 | dataarchive | Archiving | 1 | 1 |
| 104 | 31 | dataarchive | Attendance Data | 2 | 1 |
| 105 | 31 | dataarchive | Payroll Data | 3 | 1 |
| 106 | 3 | payroll | Qtec Report | 32 | 1 |
| 107 | 3 | payroll | Atlas Report | 33 | 1 |
| 108 | 6 | pms | Increment | 7 | 3 |
| 109 | 32 | multiraterassessment | Multi Rater Assessment Data | 3 | 6 |
| 110 | 32 | multiraterassessment | Multi Rater Assessment | 1 | 7 |
| 111 | 32 | multiraterassessment | MRA Reports | 2 | 2 |

Controllers attached to these 101 submodule ids: 410. Controllers with `sub_module_id` NULL: 3. Controllers with `sub_module_id` 0: 3. Those three groups sum to 416.

Submodule rows with zero controllers: id 68 `Avery Data` (module 20), id 86 `GF Reports` (module 13), id 97 ` Hourly Leave  Data Upload` (module 30).

Module rows with no submodule and no controller: id 23 `wppfmanagement`, id 28 `attendancealllog`.

## 6. Relationship exceptions

A controller passes when all three are true: `module_id` matches a `modules.id`, `sub_module_id` matches a `sub_modules.id`, and that submodule’s `module_id` equals the controller’s `module_id`.

397 controllers pass. 19 do not.

| controllers.id | module_id | cname | sub_module_id | ctitle | Why it does not pass |
| ---: | --- | --- | --- | --- | --- |
| 122 | 10 | notifications | 36 | Notifications | module_id 10 is not a modules.id |
| 123 | 10 | notificationdetail | 36 | Notification detail | module_id 10 is not a modules.id |
| 136 | NULL | attlog | 52 | Attendance Logs | module_id is NULL; controller module_id NULL differs from submodule module_id 5 |
| 205 | NULL | pfminvestmentemploan | NULL | Employees Loan | module_id is NULL; sub_module_id is NULL |
| 213 | NULL | pfminvestmentemploaninterestrate | NULL | Emp Loan Yearly Interest | module_id is NULL; sub_module_id is NULL |
| 214 | NULL | pfminvestmentemploanperiod | NULL | Emp Loan Period Data | module_id is NULL; sub_module_id is NULL |
| 267 | 4 | hrmleaveempapp/leavebalanceupdatecma | 71 | Generate CMA Leave Balance (All) | submodule 71 has a NULL module_id |
| 268 | 4 | leaveempannualleavecma | 71 | Employee Annual Leave Balance (CMA) | submodule 71 has a NULL module_id |
| 270 | 4 | leaveempannualleavecma/upload | 71 | Upload Annual Leave Balance (CMA) | submodule 71 has a NULL module_id |
| 276 | NULL | hrmleaveempapp/leavebalanceupdateatlas | 30 | Generate Atlas Leave Balance (All) | module_id is NULL; controller module_id NULL differs from submodule module_id 4 |
| 287 | NULL | organogramdepartment | 74 | Dep Organo (Position) | module_id is NULL; controller module_id NULL differs from submodule module_id 1 |
| 300 | NULL | organogramemployee/createorgano | 74 | Create Emp Organogram | module_id is NULL; controller module_id NULL differs from submodule module_id 1 |
| 325 | NULL | default/indexmonthly | 38 | PF Monthly Calculation & Reports | module_id is NULL; controller module_id NULL differs from submodule module_id 12 |
| 339 | 0 | cname | 0 | ctitle | module_id 0 is not a modules.id; sub_module_id 0 is not a sub_modules.id |
| 351 | NULL | pfmmonthlyexistingmemberfund | 84 | Monthly Existing Member fund | module_id is NULL; controller module_id NULL differs from submodule module_id 12 |
| 352 | NULL | pfmmonthlyoutgoingmemberfund | 84 | Monthly Outgoing Member Fund | module_id is NULL; controller module_id NULL differs from submodule module_id 12 |
| 365 | NULL | default/transferlocationdata | 54 | Transfer Location Data | module_id is NULL; controller module_id NULL differs from submodule module_id 5 |
| 433 | 0 | cname | 0 | ctitle | module_id 0 is not a modules.id; sub_module_id 0 is not a sub_modules.id |
| 472 | 0 | cname | 0 | ctitle | module_id 0 is not a modules.id; sub_module_id 0 is not a sub_modules.id |

Ids 339, 433, and 472 store the literal names `cname` and `ctitle` with both foreign ids 0. They are retained.

Step 21B re-read these 19 tuples and classified them. The row count and the failed checks in this table are unchanged. See `docs/UNIVERSAL_HRMS_STEP21B_LEGACY_CATALOG_INTEGRITY_AND_AUDIT_RECONCILIATION.md`.

## 7. What this inventory does not establish

- It does not list company assignment for the 25 modules. Step 21B checked assignment rows only for the missing module ids.
- It does not audit screens, approvals, or calculations.
- It does not replace the filesystem module list, and the filesystem list does not replace this catalog.
- It does not close D1–D6, the meaning of `dis`, episode overlap, date order, inactive pay, or D01–D18.
- It does not approve any company for employee migration.
- A later read-only query of the local audit database can confirm these rows. That query was not run.

## 8. Statement

No production database was connected. MySQL was not started. No schema, migration, application code, or environment file was changed. Historical audit reports were not overwritten.

INVENTORY DOCUMENTED FROM THE MASTER DUMP
