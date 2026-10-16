# Universal HRMS — AUD-11 ESS Dashboard and Overtime Identity

Date: 2026-10-16

Status: **SOURCE TRACE COMPLETE FOR THIS BATCH.** No request was sent. Source inspection is not runtime verification. Classifications are proposals, not approved requirements. No decision was signed. No company is migration-approved.

AUD-08 through AUD-10 were not repeated. Provident fund was not opened. The PIMS update from AUD-10 was not reopened. The catalog denominator stays 25 modules, 101 submodules, and 416 controller records.

## 1. Dashboard

File: `frontend\modules\ess\controllers\DashboardController.php`.

`getEmpInfo` is in `frontend\components\ApuComponent.php`, lines 87–102. The session-employee line is commented out. The method runs `SELECT * FROM employees where id=` plus the id it is given, on `session user.db`. It does not compare that id with `sesdata('employee_id')`. `getEmpInfodb`, lines 216–231, does the same on `Yii::$app->$db`.

| Action | Lines | Identity in the source | Write |
| --- | --- | --- | --- |
| `actionIndex` | 27–99 | User level required. No module-name test. Header employee is the session employee. If `user.dashboard_view` is `Yes` and that employee's `old_id` is `Yes`, the list is `searchorgo`. Otherwise it is `searchboss` | None found |
| `actionIndexnms`, `actionIndexworker`, `actionIndexisml`, `actionIndexismlw`, `actionIndexphis`, `actionIndexvsl`, `actionIndexvslw` | 102–434 | Same user-level test. Header employee is still `getEmpInfo` on the session connection. The list connection is hardcoded: `vtlnms`, `vtlw`, `isml`, `ismlw`, `phis`, `vsl`, `vslw`. Every one of these calls `searchorgo` | None found |
| `actionProfile`, `actionPersonal`, `actionEmployment`, `actionLeave`, `actionAttendance`, `actionAttendanceview`, `actionSalary`, `actionPromotion`, `actionOrganogram`, `actionPersonalfiles` | 437–711 | User level only. Each calls `getEmpInfo($_REQUEST['id'])`. Company display uses `sesdata('company_id')`. Attendance also accepts posted month and year | None found in the controller |
| The `db` copies: `actionProfiledb` through `actionPersonalfilesdb` | 715–964 | User level only. These pass `$_REQUEST['id']` and `$_REQUEST['db']` into `getEmpInfodb`, or pass both values to the view. `actionProfiledb` passes the two request values and does not call `getEmpInfodb` itself | None found in the controller |

`EmployeesSearch::searchboss`, lines 353–396, keeps `emp_unit` equal to `sesdata('company_id')` and keeps rows whose `boss` or `g_father` is `LIKE` the session employee id. The exact `boss` and `g_father` comparisons above that `LIKE` are commented out.

`searchorgo`, lines 150–183, sets `disconrinue = 0`. The `emp_unit` comparison is commented out. There is no boss comparison in the lines read.

Views that were opened:

- `views\dashboard\salary.php` prints `gross_salary` and `basic_salary` from the `empdata` array the controller passed.
- `views\dashboard\leave.php` reads leave balance and `hrm_leave_emp_app` for `$empdata['id']`.
- `views\dashboard\attendance.php` line 240 calls `getEmpAttandance` with `$empdata['id']`. Line 267 reads `attendance_approval_file` for `$empdata['emp_id_no']`. The present and late totals at lines 343–363 call `getColCountY` and `getColCountYLate` with `sesdata('employee_id')`.

No dashboard action in this file was found writing attendance, leave, overtime, or payroll.

## 2. Overtime

File: `frontend\modules\ess\controllers\OtmanagementController.php`.

Every action requires the session module list to contain `attendance`, then a user level. The header employee, where rendered, is `sesdata('employee_id')`. The writes below assign `$vtlwdb` from the session and then replace it with `Yii::$app->vtlw` before the SQL.

| Action | Lines | What the source shows |
| --- | --- | --- |
| `actionIndex` | 14–56 | Renders the session employee, or redirects to the request form with posted `date` and `df` |
| `actionOtrequestform` | 59–443 | Inserts `ot_daily_request` from posted fields, including `status`, `line_manager`, `hod`, and `poh`. Looks up each posted `employee1` through `employee5` value as `emp_id_no` on `vtlw` and inserts `ot_daily_request_hours`. No comparison with the session employee |
| `actionOtrequestform2` | 453–470 | Renders the session employee. No insert in this action |
| `actionOtrequestformedit` | 479–538 | Updates `ot_daily_request` where `id` is posted `old_id`, deletes that request's hour rows, then inserts posted employees again. No session-employee comparison |
| `actionOtrequestformedit2` | 869 | Same attendance-module and `vtlw` opening as the edit above. The later inserts were not line-read past the opening |
| `actionOtrequestformhod` | 977–1035 | Updates every hour row for posted `old_id`, then sets the request `status` to 2. No comparison with the session employee |
| `actionOtrequestformhodreject` | 1053–1085 | Loads the request by `$_REQUEST['id']`. Updates it to status 0 only when `hod` equals the session employee's `emp_id_no` |
| `actionOtrequestformpoh` | 1097–1155 | Sets `poh_approved_hours` and hour `status` to 3 or 0, then sets the request `status` to 3. The variable `final_approved_hours` is assigned and is not in that `UPDATE`. No session-employee comparison |
| `actionOtrequestformpohreject` | 1173–1205 | Same shape as the HOD reject, comparing `poh` with the session `emp_id_no` |
| `actionOtrequestformprint` | 1216–1234, and `views\otmanagement\otrequestformprint.php` lines 6–13 | The view loads the session employee and selects `ot_daily_request` for the request date, that employee's `emp_id_no` as `line_manager`, and the organogram id from `$_REQUEST['wh']` |

No statement in this controller mentions `attendance_detail`, `employee_salary`, or `employee_overtime`. A later copy from `final_approved_hours` or `poh_approved_hours` into payroll or the day result was not found here. That relationship is **UNRESOLVED**. No audit-table insert was found in these actions.

## 3. Findings

| ID | What the source shows | What it does not establish | Proposal | Inference, labeled as such |
| --- | --- | --- | --- | --- |
| DASH-01 | The main index header uses the session employee. `searchboss` also limits `emp_unit` to the session company | Whether `old_id = Yes` is an intended wide-list flag | **KEEP** the session header and the `searchboss` company filter | None |
| DASH-02 | Direct-report equality is commented out. The active test is `LIKE` on `boss` and `g_father` | Whether a short id is stored inside a longer id | **ENHANCE** the report match. The exact comparison is already present as a comment | A `LIKE` can match a different id that contains the same digits. That was not executed |
| DASH-03 | `searchorgo` has no active company or boss filter. Seven index actions always use it on a hardcoded connection | Whether `vtlnms`, `vtlw`, `isml`, `ismlw`, `phis`, `vsl`, or `vslw` are company ids 24, 20, or 10 | **REDESIGN** the missing row filter. **UNRESOLVED** as a company map | The list can be wider than the signed-in employee's reports. Not executed |
| DASH-04 | Profile, salary, leave, and attendance pages load `$_REQUEST['id']`. The `db` pages also take `$_REQUEST['db']`. `getEmpInfo` returns the full employee row for that id | A caller outside this controller that might still pass the session id | **REDESIGN** these detail actions. They do not repeat the list's boss test | A signed-in user can request another id. Not executed. The attendance footer counts still use the session employee, so that one total is not the requested id |
| OT-01 | Print selects the request whose `line_manager` is the session `emp_id_no`. Both reject actions compare `hod` or `poh` with that number | The print view's later rows were not fully read | **KEEP** those three comparisons | None |
| OT-02 | Create, edit, HOD approval, and POH approval write posted ids on `Yii::$app->vtlw`. Create stores the posted `status` | A form that might only offer the user's own employees. The form was not opened | **REDESIGN** the missing ownership test on those four writes | The reject check shows the code can compare the approver. The approve actions do not use that comparison. Not executed |
| OT-03 | This controller does not write the day result or a salary table | Whether another job copies approved hours into payroll | **UNRESOLVED** | Do not treat overtime approval as a payroll write from this file |

Nothing in this batch is **REMOVE** or **CONFIGURE**. The alias names are an unresolved map, not a verified company policy.

## 4. Runtime

None of these actions was requested. No file was created. No row was updated. The findings are source-traced only.

## 5. Next batch

ESS `AdminController` in `frontend\modules\ess\controllers\AdminController.php`, limited to whether `actionProfile`, `actionPayslip`, `actionSalarycard`, and `actionSalcer` stay on the session employee. Do not open PF. Do not expand PIMS. Do not repeat AUD-01 through AUD-11.

## 6. Statement

No application code, schema, test, configuration, database, or Git state was changed. The legacy tree was not modified. The active control center gained one completed row. No gate was signed.

AUD-11 SOURCE TRACE COMPLETE FOR THIS BATCH
