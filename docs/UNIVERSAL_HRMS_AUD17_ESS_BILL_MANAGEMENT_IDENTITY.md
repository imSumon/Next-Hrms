# Universal HRMS — AUD-17 ESS Bill Management Identity

Date: 2026-10-16

Status: **SOURCE TRACE COMPLETE FOR THIS BATCH.** No request was sent. Source inspection is not runtime verification. Classifications are proposals, not approved requirements. No decision was signed. No company is migration-approved.

Controller: `frontend\modules\ess\controllers\BillmanagementController.php`. The file contains eight actions. All eight were read. Provident fund was not opened. The catalog denominator stays 25 modules, 101 submodules, and 416 controller records.

## 1. Actions

Every action except the external list requires a user level and redirects `External` to `logoutext` before the module test. The module name is `billmanagement`. `actionApprovelistext` does not contain that `External` redirect. It still requires the module name and a user level.

Two connection names appear. Raw SQL uses `session user.db`. Model calls use `Yii::$app` plus `sesdata('dbname')`. Whether those two names are the same connection was not established.

| Action | Lines | What the source shows |
| --- | --- | --- |
| `actionIndex` | 26–91 | A post redirects to `empbills` with the posted category and bill count. A normal render does not pass an employee. `views\billmanagement\index.php` lines 5 and 130–133 load the session employee and list `bill_employee_bills` for that `emp_id_no`, plus the requested month and year |
| `actionBillfilter` | 93–137 | Posts month, year, and category back to the index route. A non-post request calls `die()` |
| `actionBillfilterhis` | 139–183 | Posts month and year to `approvehistory`. A non-post request calls `die()` |
| `actionDelete` | 186–219 | Deletes `bill_employee_bills` where `id` is `$_REQUEST['lid']`, then deletes `bill_employee_bills_data` where `emp_bill_id` is that same id. No employee comparison and no status test |
| `actionEmpbills` | 220–404 | Sets `emp_id_no` from `getEmpInfo(sesdata('employee_id'))` on `bill_employee_bills` and `bill_employee_bills_data`. Status is set to `New`. Approver ids come from posted `fabeta` and `sabeta`. A file field is stored under `DOCUMENT_ROOT/hrmsdata/billfiles/` using the original name. `save()` is called. The model rules were not read |
| `actionApprovelist` | 407–542 | The view, lines 4 and 16–26, lists bills whose `internal_first_approver` or `internal_second_approver` equals `sesdata('employee_id')`, and whose status is `New` or `First Approved`. Lines 112–113 link Approve and Reject with `bid`, `wo`, and `st`. The action loads `BillEmployeeBills` by `$_REQUEST['bid']` and saves `First Approved`, `First Rejected`, `Second Approved`, or `Second Rejected`. It does not compare the approver column or the previous status |
| `actionApprovehistory` | 543–588 | Renders the session employee. The history view body was not read |
| `actionApprovelistext` | 591–719 | Same status write as the internal list. The view uses `external_first_appover` or `external_second_appover` and still sets `$eid` from `sesdata('employee_id')` |

The index view line 160 shows Delete only when status is `New`. `actionDelete` does not repeat that test. No audit insert was found. No statement in this controller names `attendance_detail` or `employee_salary`.

## 2. Findings

| ID | What the source shows | What it does not establish | Proposal | Inference, labeled as such |
| --- | --- | --- | --- | --- |
| BILL-01 | Create writes the session employee number. The index list reads that same number | Model validation, because the rules were not read | **KEEP** the session employee on create and on the index list | None |
| BILL-02 | Delete removes the bill and its field rows by `lid`. The view link is limited to status `New` | A caller outside this view | **REDESIGN** the missing employee and status tests. The view condition is not the action condition | Another id could delete a row the screen does not link. Not executed |
| BILL-03 | Both approval actions save a new status for `bid`. The views filter by approver and current status. The actions do not | Whether every Approve link is built only inside that filtered list. The link is in the filtered view. The action accepts the query parameters | **REDESIGN** the missing approver and status tests | A signed-in user with the module could request another `bid`. Not executed |
| BILL-04 | Files keep the original name. The two database names are selected separately | An extension allow-list. Whether `sesdata('dbname')` equals `user.dbname` | **UNRESOLVED** for the file rule and for the two connection names | None |

Nothing in this batch is **REMOVE**, **ENHANCE**, or **CONFIGURE**. No exploit was demonstrated.

## 3. Runtime

No bill was created, approved, rejected, or deleted. No file was uploaded. No mail was sent. The findings are source-traced.

## 4. Statement

No application code, schema, test, configuration, database, or Git state was changed. The legacy tree was not modified. No gate was signed.

AUD-17 SOURCE TRACE COMPLETE FOR THIS BATCH
