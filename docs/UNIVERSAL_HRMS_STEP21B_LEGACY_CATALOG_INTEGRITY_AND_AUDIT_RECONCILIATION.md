# Universal HRMS — Step 21B Legacy Catalog Integrity and Audit Reconciliation

Status: **CATALOG INTEGRITY BASELINE DOCUMENTED**. This is an offline dump parse. A live database was not queried.

The baseline classifies catalog defects. It does not repair rows, approve a requirement, or approve an employee migration.

## 1. Evidence searched

Read:

- `docs/UNIVERSAL_HRMS_STEP21A_LEGACY_MODULE_SUBMODULE_INVENTORY.md`
- `docs/UNIVERSAL_HRMS_STEP21_REQUIREMENTS_COVERAGE_AND_GAP_REPORT.md`
- `docs/UNIVERSAL_HRMS_STEP21_REQUIREMENTS_TRACEABILITY_MATRIX.md`

Both documentation directories were searched for Step 1A–1E reports and for any markdown that states “99 submodule” or “416 controller” outside the Step 21 documents. No Step 1A, 1B, 1C, 1D, or 1E report is present in either directory, and no other project markdown states those counts. The claims survive only as the “previously reported” column in Step 21A.

The dump and the current Yii tree were read. Backup folders whose names start with `00` were used only as file-location evidence. They were not added to the catalog counts.

## 2. Provenance

| Item | Value |
| --- | --- |
| Verification date | 2026-10-11 |
| Dump path | `D:\Project -All\Proedge HRMS\legacy-data\master\proedgea_payrollmaster.sql` |
| Dump header | `-- Host: localhost    Database: proedgea_payrollmaster` |
| Size and last write | 3,635,806,039 bytes. Last write 2026-10-02 15:20:11. These are the Step 21A figures for this same file. This step did not recompute a hash |
| Method | Offline parse of `INSERT` statements. The same splitter as Step 21A was used: commas outside quotes, escaped quotes preserved, and the statement-ending `);` restored before the last field |
| Inserts found | One `INSERT` each for `modules`, `sub_modules`, `controllers`, `company_modules`, `user_companies_modules`, and `user_companies_module_controllers` |
| Field counts | Controllers 416 rows × 6 fields. `company_modules` 420 × 6. `user_companies_modules` 621 × 4. `user_companies_module_controllers` 9,773 × 8 |
| Live `legacy_master` | Not compared. MySQL was not started |
| Credentials | `companies.db_username` and `companies.db_password` were not extracted |

Parsing limit: the extractor stops at the first `);\n` after each `INSERT`. These six statements parsed to a uniform field count, including the last row. A second `INSERT` for any of these tables was not found by a count of the `INSERT INTO` lines for the three catalog tables in Step 21A. This step did not re-count `INSERT` lines for the assignment tables beyond extracting the first statement. Each assignment statement parsed to the row counts above.

The three catalog tables and the three assignment tables define a primary key on `id` and no foreign key. The checks below are value checks.

## 3. The three checks

A controller row passes only when all three are true:

- **A.** `controllers.module_id` equals a `modules.id`.
- **B.** `controllers.sub_module_id` equals a `sub_modules.id`.
- **C.** That submodule’s `module_id` equals the controller’s `module_id`.

Step 21A’s result stands: 397 rows pass and 19 do not. This step re-read those 19 tuples. The ids, names, titles, and failed checks match Step 21A. `cpriority` is added below.

`user_companies_module_controllers.controller_id` is `varchar`. A grant count below is the number of those rows whose `controller_id` text equals the controller id. Grant `module_id` is evidence of what was stored on the grant. It does not change the controller row.

## 4. All 19 controller rows

| id | cname | ctitle | cpriority | module_id | Module row | sub_module_id | Submodule row | Submodule parent | Failed checks | Grants | Classification |
| ---: | --- | --- | ---: | --- | --- | --- | --- | --- | --- | --- | --- |
| 122 | notifications | Notifications | 1 | 10 | Absent | 36 | Present, name `Notification` | 10, absent | A. C fails because the parent module row is absent | 0 | Unresolved |
| 123 | notificationdetail | Notification detail | 2 | 10 | Absent | 36 | Present, name `Notification` | 10, absent | A. C fails because the parent module row is absent | 0 | Unresolved |
| 136 | attlog | Attendance Logs | 3 | NULL | No | 52 | Present, `Attendance Manage` | 5, `attendance` | A and C | 36, all `module_id` 5 | Classified |
| 205 | pfminvestmentemploan | Employees Loan | 10 | NULL | No | NULL | No | None | A and B. C has no submodule to compare | 0 | Unresolved |
| 213 | pfminvestmentemploaninterestrate | Emp Loan Yearly Interest | 8 | NULL | No | NULL | No | None | A and B. C has no submodule to compare | 0 | Unresolved |
| 214 | pfminvestmentemploanperiod | Emp Loan Period Data | 9 | NULL | No | NULL | No | None | A and B. C has no submodule to compare | 0 | Unresolved |
| 267 | hrmleaveempapp/leavebalanceupdatecma | Generate CMA Leave Balance (All) | 7 | 4 | Present, `leave` | 71 | Present, name `CMA` | NULL | C | 19, all `module_id` 4 | Partially classified |
| 268 | leaveempannualleavecma | Employee Annual Leave Balance (CMA) | 4 | 4 | Present, `leave` | 71 | Present, name `CMA` | NULL | C | 19, all `module_id` 4 | Partially classified |
| 270 | leaveempannualleavecma/upload | Upload Annual Leave Balance (CMA) | 3 | 4 | Present, `leave` | 71 | Present, name `CMA` | NULL | C | 17, all `module_id` 4 | Partially classified |
| 276 | hrmleaveempapp/leavebalanceupdateatlas | Generate Atlas Leave Balance (All) | 9 | NULL | No | 30 | Present, `Leave` | 4, `leave` | A and C | 4, all `module_id` 4 | Classified |
| 287 | organogramdepartment | Dep Organo (Position) | 1 | NULL | No | 74 | Present, `Organogram` | 1, `organizationsettings` | A and C | 2, all `module_id` 1 | Classified |
| 300 | organogramemployee/createorgano | Create Emp Organogram | 3 | NULL | No | 74 | Present, `Organogram` | 1, `organizationsettings` | A and C | 2, all `module_id` 1 | Classified |
| 325 | default/indexmonthly | PF Monthly Calculation & Reports | 6 | NULL | No | 38 | Present, `PF Management` | 12, `pfmanagement` | A and C | 11, all `module_id` 12 | Classified |
| 339 | cname | ctitle | 0 | 0 | Absent | 0 | Absent | None | A and B | 0 | Classified as a placeholder |
| 351 | pfmmonthlyexistingmemberfund | Monthly Existing Member fund | 6 | NULL | No | 84 | Present, `PF Contribution` | 12, `pfmanagement` | A and C | 10, all `module_id` 12 | Classified |
| 352 | pfmmonthlyoutgoingmemberfund | Monthly Outgoing Member Fund | 7 | NULL | No | 84 | Present, `PF Contribution` | 12, `pfmanagement` | A and C | 10, all `module_id` 12 | Classified |
| 365 | default/transferlocationdata | Transfer Location Data | 20 | NULL | No | 54 | Present, `Attendance Data Upload & Download` | 5, `attendance` | A and C | 4, all `module_id` 5 | Classified |
| 433 | cname | ctitle | 0 | 0 | Absent | 0 | Absent | None | A and B | 0 | Classified as a placeholder |
| 472 | cname | ctitle | 0 | 0 | Absent | 0 | Absent | None | A and B | 0 | Classified as a placeholder |

Grant rows for submodule 71 total 55, which is 19 + 19 + 17. No other controller id is stored on those grants.

### 4.1 What “classified” means

Classified means the failed check is identified and the related dump rows agree on one existing module, or the stored names are the literals `cname` and `ctitle`. The controller row is still stored as dumped. Classification is not a correction.

For the eight null-module rows 136, 276, 287, 300, 325, 351, 352, and 365, the submodule parent and every matching grant use the same existing module id. Current PHP agrees with that module:

| id | Current PHP location |
| ---: | --- |
| 136 | `backend\modules\attendance\controllers\AttlogController.php` |
| 276 | `actionLeavebalanceupdateatlas` in `backend\modules\leave\controllers\HrmleaveempappController.php` |
| 287 | `backend\modules\organizationsettings\controllers\OrganogramdepartmentController.php` |
| 300 | `actionCreateorgano` in `backend\modules\organizationsettings\controllers\OrganogramemployeeController.php` |
| 325 | `actionIndexmonthly` in `backend\modules\pfmanagement\controllers\DefaultController.php` |
| 351 | `backend\modules\pfmanagement\controllers\PfmmonthlyexistingmemberfundController.php` |
| 352 | `backend\modules\pfmanagement\controllers\PfmmonthlyoutgoingmemberfundController.php` |
| 365 | `actionTransferlocationdata` in `backend\modules\attendance\controllers\DefaultController.php` |

`default/indexmonthly` also exists on catalog controller 446, module 13, submodule 39, title `GF Monthly Calculation & Reports`. Grants for that same `cname` split as controller 325 under module 12 (11 rows), controller 446 under module 13 (3 rows), and controller id 349 (1 row). Controller id 349 is not a row in `controllers`. The GF method `actionIndexmonthly` also exists under `gfmanagement`. The two catalog rows stay separate.

### 4.2 Partially classified CMA rows

Rows 267, 268, and 270 pass check A. Module 4 is `leave`. Their grants also store module 4. Check C fails because submodule 71, name `CMA`, has `module_id` NULL. The parent of `CMA` remains unresolved.

Current code:

- `LeaveempannualleavecmaController.php` is under `backend\modules\leave\controllers` and contains `actionUpload`.
- The string `leavebalanceupdatecma` does not occur in the current `backend` PHP tree.
- `actionLeavebalanceupdateatlas` does occur, on the leave controller named above.

### 4.3 Unresolved rows

Rows 122 and 123 point at module 10. That `modules` row is absent. Submodule 36 exists and also points at 10. No `company_modules`, `user_companies_modules`, or `user_companies_module_controllers` row uses module id 10. No `NotificationsController` or `NotificationdetailController` is in the current tree. The name of the missing module is unresolved.

Rows 205, 213, and 214 store NULL for both foreign ids and have no grant rows. PHP classes of the same names exist under `backend\modules\pfmanagement\controllers`. That folder corresponds to catalog module 12, `pfmanagement`. The catalog rows themselves still have no module id and no submodule id, so the catalog link stays unresolved. The folder is not a substitute for the missing ids.

Rows 339, 433, and 472 are classified only as placeholder rows. They are not a workflow.

## 5. Related catalog discrepancies

### 5.1 Missing module ids

| modules.id | `modules` row | Submodule parent references | Assignment `module_id` / `modules_id` | Result |
| ---: | --- | --- | --- | --- |
| 7 | Absent | None | 0 | Id gap only. Historical name unresolved |
| 10 | Absent | Submodule 36 | 0 | Dangling parent for submodule 36 and controllers 122 and 123. Module identity unresolved |
| 11 | Absent | None | 0 | Id gap only. Historical name unresolved |
| 14 | Absent | None | 0 | Id gap only. Historical name unresolved |
| 15 | Absent | None | 0 | Id gap only. Historical name unresolved |
| 17 | Absent | None | 0 | Id gap only. Historical name unresolved |
| 26 | Absent | None | 2 company rows, 1 user-module row, 3 controller-grant rows | Dangling grants. Module identity unresolved |

Assignment counts use the module-id column only: `company_modules.modules_id`, `user_companies_modules.module_id`, and `user_companies_module_controllers.module_id`.

The two `company_modules` rows are id 1740 (`company_id` 29, `modules_id` 26) and id 1802 (`company_id` 57, `modules_id` 26). Master `companies.id` 29 is `VIYELLATEX SPINNING LTD`. Master `companies.id` 57 is `VTL.SS`. These are master catalog company ids. They are not target company ULIDs, and they are not `modules.id` 26.

The three module-26 grant rows store `sub_module_id` 78 and `controller_id` 294, 295, and 296, with `cname` `supportservice`, `supportservice/upload`, and `supportservice/reports`. Submodule 78 is one of the absent submodule ids from Step 21A. Controller ids 294, 295, and 296 are not rows in `controllers`. A `supportservice` folder exists at `backend\modules\00no need modules00\supportservice`, including `SupportserviceController.php`. That folder is unused-code evidence beside those cnames. It is not a `modules` row, so it does not name module 26.

### 5.2 Submodule 36 and submodule 71

Submodule 36, name `Notification`, priority 1, parent module 10. The parent row is absent. It has two controller rows, 122 and 123, and no grant rows. Unresolved.

Submodule 71, name `CMA`, priority 2, parent NULL. It has controller rows 267, 268, and 270. The controller module and the grants say module 4. The submodule parent cell itself is NULL. The parent remains unresolved.

### 5.3 Modules with no submodule and no catalog controller

| id | mname | active | Catalog children | Directory |
| ---: | --- | --- | --- | --- |
| 23 | wppfmanagement | Yes | No submodule and no controller | No `wppfmanagement` directory under `backend\modules`, including the unused-module folders searched by name |
| 28 | attendancealllog | No | No submodule and no controller | `backend\modules\attendancealllog` exists and contains `DefaultController.php` |

Both are empty catalog modules. `wppfmanagement` is an active catalog row with no children and no located code. `attendancealllog` is an inactive catalog row whose Yii folder contains a default controller that is not a `controllers` row. Neither fact makes the other source the correct inventory.

### 5.4 Catalog names and directory names

These are naming differences. They are not failed checks A–C.

| Name | Catalog | Directory | Reading |
| --- | --- | --- | --- |
| `averydata` | Module 20, active `No`. Submodule 58 `PAL-D` has 2 controllers. Submodule 68 `Avery Data` has 0 | Not a top-level module directory. A folder exists at `backend\modules\00no need modules00\averydata` | Catalog row plus unused-folder code. Not a broken parent link |
| `wppfmanagement` | Module 23, active `Yes`, no children | Not found in this tree | Catalog row with no located code. Purpose unresolved |
| `appadmin` | No `modules` row | Top-level directory. It contains `ModulesController`, `SubmodulesController`, `ControllersController`, and `CompanymodulesController` | The catalog-maintenance screens live in a Yii module that is not itself a catalog module |
| `reports` | No `modules` row. Payroll submodule 20 is named `Reports` and is a different object | Top-level directory with `DefaultController.php` only | A Yii module name and a payroll submodule title. Not the same record |

## 6. Historical claims

| Claim recorded by Step 21A as previously reported | Latest dump parse | Effect |
| --- | --- | --- |
| 25 modules | 25 `modules` rows | Reproduced |
| 99 submodules | 101 `sub_modules` rows | Not reproduced |
| 416 controllers | 416 `controllers` rows | Reproduced |
| All 416 controller relationships valid | 397 pass checks A–C. 19 do not | Contradicted |

Why 99 cannot be reproduced: no source report states the filter, the grouping, or the query. The dump contains 101 submodule rows, including id 36 and id 71. Removing those two exceptional rows equals 99. That is arithmetic. It does not establish the original counting method.

No Step 1A–1E file was found, so none was edited or deleted. Step 21B is the superseding addendum for the 99-submodule claim and for the claim that all 416 relationships were valid. Step 21A remains the row inventory. Its counts are unchanged. The current coverage report and the inventory now point here.

## 7. Baseline

| Item | Baseline |
| --- | --- |
| Catalog size | 25 modules, 101 submodules, 416 controllers |
| Link checks | 397 pass. 19 fail |
| Classified failures | 8 null-module rows whose submodule parent and grants agree, plus 3 placeholder rows |
| Partially classified | 3 CMA rows. Module 4 is present. Submodule 71’s parent is NULL |
| Unresolved controller rows | 122, 123, 205, 213, 214 |
| Unresolved module identities | 10 and 26, because other rows still reference them. Ids 7, 11, 14, 15, and 17 are absent and unreferenced in the assignment module-id columns |
| Filesystem versus catalog | `appadmin`, `reports`, `averydata`, `wppfmanagement`, and `attendancealllog` stay different lists |

## 8. Remaining gap and next step

A read-only comparison with the local database named `legacy_master` has not been done. When that server is already running, compare these controller ids, submodule ids 36 and 71, and module ids 7, 10, 11, 14, 15, 17, and 26 with the dump. Do not treat a PHP path as a repaired `module_id`.

## 9. Statement

No database was connected. MySQL was not started. No schema, migration, application code, or environment file was changed. Historical audit reports were not overwritten.

CATALOG INTEGRITY BASELINE DOCUMENTED
