# Universal HRMS — AUD-13 ESS Hourly Leave Identity

Date: 2026-10-16

Status: **SOURCE TRACE COMPLETE FOR THIS BATCH.** No request was sent. Source inspection is not runtime verification. Classifications are proposals, not approved requirements. No decision was signed. No company is migration-approved.

Controller: `frontend\modules\ess\controllers\HourlyleaveController.php`. AUD-01 through AUD-12 were not repeated. Provident fund was not opened. The catalog denominator stays 25 modules, 101 submodules, and 416 controller records.

`getEmpInfo` was read in AUD-11. It returns the employee row for the id it is given and does not force the session employee. `getHourlyLeaveAppInfo`, in `frontend\components\ApuComponent.php` lines 605–620, returns `hourly_leave_emp_app` for the id it is given on `session user.db`. The session-employee line in that helper is commented out.

## 1. Actions

Most actions require the session module list to contain `hourlyleave` and require a user level. The exceptions are named below. Writes use `session user.db` unless the action says otherwise. No statement in this controller mentions `attendance_detail` or `employee_salary`.

| Action | Lines | Identity and effect |
| --- | --- | --- |
| `actionIndex` | 19–67 | Renders the session employee. `views\hourlyleave\index.php` lines 237 and 316 list `hourly_leave_emp_app` where `emp_id` is that session id |
| `actionIndexdays` | 69–219 | Inserts `hourly_leave_emp_app` with `emp_id` taken from `sesdata('employee_id')`, status 0, and `first_approver` from that employee's `boss`. Overlap and balance queries use the same session id. A required file is stored under `DOCUMENT_ROOT/hrmsdata/leavefiles/` using the original name |
| `actionIndexhourly` | 308–487 | Same session employee. Inserts hours and minutes instead of days. The end date is set from the start date |
| `actionLeaveapplication` | 575–608 | Passes request `id` and `w` to the view. `views\hourlyleave\leaveapplication.php` lines 3–5 load that application and then `getEmpInfo` of its `emp_id`. No comparison with the session employee in those lines |
| `actionLeavestatus` | 610–637 | Renders the session employee. `views\hourlyleave\leavestatus.php` lines 30–36: when the session employee id is `103`, the query selects active employees with no boss filter. Otherwise it selects employees whose `boss` or `g_father` equals the session id. The year label differs when the database name is `ceva` |
| `actionLeaveapprover` | 647–689, then 709–822 | On post, sets status 3 for `firstapp` and 4 for `firstrej`, then updates `hourly_leave_emp_app` where `id` is `$_REQUEST['id']`. The `first_approver` column is read at line 833 for the message, after the update. When the new status is 3, the action subtracts the request from `hourly_leave_emp_annual_leave` or `hourly_leave_emp_balance` for the application's employee |
| `actionLeaveapprovernms` | 919–977 | Connection is `Yii::$app` plus `$_REQUEST['db']`. On post, the first decision uses posted `st` or `rst`. The second decision sets status 2 or 5. The update is `hrm_leave_emp_app` where `id` is `$_REQUEST['id']`. No employee comparison |
| `actionDeleteleave` | 1009–1098 | Loads `hourly_leave_emp_app` by `$_REQUEST['lid']`. Deletes that row only when its `emp_id` equals the session employee. No status test in those lines. For a database name other than `isml`, it sends mail |
| `actionLeaveapplications` | 1119–1162 | Requires `user_level` equal to `clientAdmin`. No module-name test. `HourlyLeaveEmpAppSearch::search`, lines 58–69, filters `emp_id` only when that search field is supplied. It does not force the session employee or `emp_unit` |
| `actionUnit` | 1166–1194 | No user-level or module test in the action. Counts `leave_holidays` between the request dates and prints a day count |
| `actionUnitms` | 1198–1231 | No user-level or module test in the lines read. Reads `emp_shift_calendar` for `$_REQUEST['eee']` |
| `actionApprovelist` and `actionApprovelistview` | 1294–1372 | Both require the module and a user level. On post, each updates `leave_annual_plan` where `id` is posted `aseid`, setting `responsible_person` from posted `emp` and `status` to `Pending` |

Create and the index list are reads and writes for the session employee. Delete is a write with an ownership comparison. Approval, the application view, the admin list, the two unit helpers, and the annual-plan update are not limited to that employee in the lines read.

## 2. Findings

| ID | What the source shows | What it does not establish | Proposal | Inference, labeled as such |
| --- | --- | --- | --- | --- |
| HLV-01 | Index, day create, and hourly create bind `emp_id` to the session employee on the session connection. The index lists that same id | Whether the upload name can replace another file. The upload was not run | **KEEP** the session binding on these three paths | None |
| HLV-02 | Delete compares the row's `emp_id` with the session employee before `DELETE` | Whether an approved row should be deletable. No status condition is present | **KEEP** the ownership comparison. **UNRESOLVED** as a status rule | None |
| HLV-03 | `actionLeaveapprover` updates status and then the balance by request id. It does not compare `first_approver` with the session employee before the update | Whether the screen only shows that user's approvals. The calling list was not opened | **REDESIGN** the missing approver comparison. The module check does not identify the approver | A signed-in user with the module could post another request id. Not executed. No day-result or payroll write was found, so an attendance or payroll effect is **UNRESOLVED** |
| HLV-04 | `actionLeaveapprovernms` updates `hrm_leave_emp_app` on the connection named by `$_REQUEST['db']`, and takes the first status from the post | Whether `db` is limited to a known alias. The action does not list allowed names | **REDESIGN** the request connection and the missing ownership test | The table is the day-leave application table, not `hourly_leave_emp_app`. Not executed |
| HLV-05 | The application view loads any hourly-leave id. The `clientAdmin` list does not force an employee or company. Leave status makes employee id `103` a list of all active employees | Who id `103` is, and whether that list is an approved exception | **REDESIGN** those three reads. **UNRESOLVED** for the meaning of id `103` | The wider list is what the query says. It was not executed |
| HLV-06 | `actionUnit` and `actionUnitms` have no level or module test in the lines read. The annual-plan actions update `leave_annual_plan` by posted id | A route filter outside the action | **REDESIGN** the missing checks. The plan update is not an hourly-leave ownership check | None |
| HLV-07 | Leave status uses a July year split except when the database name is `ceva`, which uses the calendar year | A signed leave-year policy | **CONFIGURE** the year label. Do not copy the alias test as the product rule | None |

Nothing in this batch is **REMOVE** or **ENHANCE**. The create path already binds the employee. The approval path has no approver comparison to tighten.

## 3. Runtime

No leave request was submitted, approved, or deleted. No mail was sent. No balance row was changed. Every finding above is source-traced.

## 4. Next batch

ESS `TicketingController`, limited to whether its reads and `actionDeleteleave` stay on the session employee. Do not open provident fund. Do not expand PIMS. Do not repeat AUD-01 through AUD-13.

## 5. Statement

No application code, schema, test, configuration, database, or Git state was changed. The legacy tree was not modified. The active control center gained one completed row. No gate was signed.

AUD-13 SOURCE TRACE COMPLETE FOR THIS BATCH
