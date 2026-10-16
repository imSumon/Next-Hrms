# Universal HRMS — AUD-07 Approval Callers and Payroll Reads

Status: **SOURCE TRACE COMPLETE**. This record adds caller and payroll-read evidence to AUD-02 and AUD-06. It does not replace either audit. No company database was queried. MySQL was not started.

Observed steps are legacy behavior. They do not sign BD-D16, BD-D17, BD-D07, BD-D13, or BD-D14. They do not authorize an attendance or payroll design. Employee migration stays blocked. No company is migration-approved.

## 1. Evidence

Legacy application root: `legacy-reference\hrms\`.

- `frontend\modules\ess\controllers\AttendanceController.php` — `actionAttendanceapprover`, `actionAttendanceapprovernms`
- `frontend\modules\ess\views\attendance\approvelist.php`
- `frontend\modules\ess\views\attendance\attendanceapplicationsfls.php`
- `frontend\modules\ess\views\attendance\attendanceapplicationnms.php`
- `frontend\modules\ess\views\attendance\attendanceapplicationstore.php`
- `frontend\modules\ess\views\attendance\attendanceapplicationnmsV.php`
- `frontend\modules\ess\views\attendance\attendanceapplication.php` — leave links, recorded so they are not treated as attendance approval
- `backend\modules\payroll\controllers\EmployeesalaryController.php` — `actionGenerate`
- `backend\modules\payroll\views\employeesalary\generatesalarynoview.php`
- `backend\modules\payroll\controllers\PayrollreportsController.php` — `actionSalaryregister`, `actionSinglepayslip`
- `backend\modules\payroll\views\payrollreports\salaryregister.php`
- `backend\modules\payroll\views\payrollreports\salaryregistervtlms.php`
- `backend\modules\payroll\views\payrollreports\singlepayslip.php`
- `backend\components\ApuattendanceComponent.php` — `getColCountY`, `getColCountYLate`
- `backend\modules\payroll\controllers\EmployeeovertimeController.php` — `actionUpload`
- AUD-02 for `actionApproveatt` and the Proedge/`vtl` status-only branch
- The earlier attendance-to-payroll dependency trace for reprocessing and the maternity-leave count

Copies under `frontend\modules\ess\views\attendance\0\` repeat some `attendanceapprovernms` links, including absolute host URLs. They are backup copies and are not the current screens.

A search of `frontend\modules` and `backend\modules` found no link whose path is `attendanceapprover` without `nms`. A later repository search, excluding vendor, backups, published assets, and that `0` folder, also found no such link.

## 2. Where the actions live

`AttendancedetailController` does not define `actionAttendanceapprover`.

| Action | Location | What it writes |
| --- | --- | --- |
| `actionAttendanceapprover` | `AttendanceController.php` line 1127 | `attendance_approval_file.status` on the session connection, from request parameter `s` (line 1145) |
| `actionAttendanceapprovernms` | `AttendanceController.php` line 1159 | The same status column, on the connection named by request parameter `db` (line 1195) |

`actionAttendanceapprover` requires a session user level and the `attendance` module. It does not compare the session employee with `boss_id` or `gboss_id`. It does not read a month lock. It does not update `attendance_detail`.

`actionAttendanceapprovernms` requires a session user level. The module check is commented out at lines 1167–1174. Status `1` or `10` stamps `first_approver_date`. Status `2` or `15` stamps `second_appover_date` (lines 1187–1195). It does not update `attendance_detail`. It does not read a month lock.

## 3. Callers and status values

### Normal Proedge, Viyellatex, and Atlas list

`approvelist.php` lines 105 and 238 link to `approveatt?id=` and `rejectatt?id=`. Those links do not send `s`. They use the session connection.

AUD-02 already describes `actionApproveatt`. For session names `proedge` and `vtl`, that action changes application status only: 2 becomes 3, 3 becomes 4, and 98 becomes 3. It does not set status `1` and does not rewrite the day. This trace does not change that finding.

No current screen in the searched modules was found that sends status `1` to `actionAttendanceapprover` for the `proedge` or `vtl` session. A normal Proedge or Viyellatex workflow that reaches status `1` was not found.

### Screens that do send `s`

These links call `attendanceapprovernms`, not `attendanceapprover`. The `db` value is an alias on the link. It is not company id 24, 20, or 10, and `vtlnms` is not the `vtl` session name used by `actionApproveatt`.

| View | Lines | Approve `s` | Reject `s` | `db` |
| --- | --- | --- | --- | --- |
| `approvelist.php`, only when the session database is `isml` (line 249) | 341–343 | 1 | 10 | `sfls` |
| `approvelist.php`, same `isml` block | 459–460 | 2 | 15 | `sfls` |
| `attendanceapplicationsfls.php` | 56, 60 | 1, then 2 | 10, then 15 | `sfls` |
| `attendanceapplicationnms.php` | 56, 60 | 1, then 2 | 10, then 15 | `vtlnms` |
| `attendanceapplicationstore.php` | 57, 61 | 3, then 4 | 10, then 15 | `palstorew` |
| `attendanceapplicationnmsV.php` | 56, 60 | 3, then 4 | 10, then 15 | `vsl` |

`attendanceapplication.php` lines 57 and 60 link to `ess/leave/leaveapprover`. That is the day-leave action already covered by AUD-02. It is not an attendance-approval caller.

Status `20` is labeled on the timecard in AUD-02 and was not found as a submitted `s` on these callers.

## 4. Application row and day row

A correction application is a row in `attendance_approval_file`. The calculated day is a row in `attendance_detail`. The actions in section 2 update the application row only.

The earlier dependency trace remains in force and is not repeated here. Reprocessing reads an application when `attendance_approval_file.status = 1`, then deletes and rebuilds the current day row. Status `3` and status `4` from the Proedge and `vtl` branch of `actionApproveatt` are not that status. Because no normal Proedge or Viyellatex screen found here sets status `1`, that reprocessing read has no matching normal approval button for those two session names.

AUD-02 still stands for the Atlas and CEVA grand-boss branch of `actionApproveatt`: that branch can set status `1` and can rewrite an existing day row. A missing day stays missing. This trace did not reopen that action.

## 5. Payroll and payslip reads for company ids 24, 20, and 10

`EmployeesalaryController::actionGenerate` (line 68) renders `generatesalarynoview` when that request flag is set (lines 77–80). The action has no branch on company id 24, 20, or 10.

`generatesalarynoview.php` line 117 is the only `attendance_detail` read in that file. It counts `attendance_status='ML'`. The file does not mention `ot_hours`, `employee_overtime`, or `total_ot`. The earlier trace remains in force: that maternity count is applied only for the `icwlw` and `valw` aliases, and the generate path then deletes and rewrites `employee_salary`. Company ids 24, 20, and 10 are not those aliases.

Payslip selection: `PayrollreportsController::actionSinglepayslip` (line 1898) renders `singlepayslipqtec` when `companyid` is 74, and `singlepayslip` otherwise (lines 1904–1910). `singlepayslip.php` reads `employee_salary` at lines 151 and 215. It does not read `attendance_detail`.

Salary register selection: `actionSalaryregister` (line 1727) renders `salaryregistervtlms` when `companyid == 20` (lines 1735–1737). Company ids 10 and 24 are not special-cased, so they receive `salaryregister` (line 1756). `salaryregister.php` has no read of `attendance_detail`, `ot_hours`, or `late`.

Company 20’s register displays attendance beside stored salary amounts. It does not write payroll.

| Display | Source | Lines |
| --- | --- | --- |
| Counts of `attendance_status` values `EL`, `CL`, `SL`, `ML`, `OL`, `P`, and `A` | `ApuattendanceComponent::getColCountY` | View lines 264–278. Helper line 1298 |
| Count of `day_status='L'` | `getColCountYLate` | View line 279. Helper line 1318 |
| Sum of `attendance_detail.late` where `attendance_status='P'`, printed as whole hours | Direct query | View lines 284–286 |
| Earning amounts | `salary_head_total` from the salary rows | View line 296 |

`EmployeeovertimeController::actionUpload` (line 311) stores hours from an uploaded CSV into `employee_overtime` (`total_ot_hour` at line 360). It does not read `attendance_detail`. `generatesalarynoview.php` does not read `employee_overtime`.

## 6. Relation to AUD-02

AUD-02 section 3 describes `actionApproveatt`. Its Proedge and `vtl` status-only finding stays as written.

AUD-02 section 7 says status codes 10, 15, and 20 are labeled on the timecard and were not set by `actionApproveatt`. That sentence stays true. This trace identifies a different action, `actionAttendanceapprovernms`, whose screens submit 1, 2, 3, 4, 10, and 15 for the aliases in section 3. Status 20 is still without a located writer.

AUD-02 did not claim that `actionAttendanceapprover` has no callers. This trace supplies that negative result for the searched modules.

## 7. Gate impact

No gate changes.

| Gate | After this trace |
| --- | --- |
| BD-D16 | Unsigned. The approved mechanism remains original, requested, approved, and final values, with the raw punch left unedited. The field list stays unsigned |
| BD-D17 | Unsigned. A fixed boss and grand-boss sequence stays prohibited |
| BD-D07 | Unsigned. Absence and joiner or leaver pay stay blocked. The company 20 register displays absence and late; it does not approve a deduction formula |
| BD-D13 | Unsigned. A month-lock flag is not adopted as an attendance cutoff |
| BD-D14 | Unsigned. The approved snapshot rule stays. This trace does not add a regeneration block |
| D1–D6 | Unchanged. No employee load is authorized |

Attendance, leave, overtime, and payroll implementation stay blocked on the control-center list. SEC-CORE-004 stays unimplemented.

## 8. Confirmed, unresolved, and proposed

Confirmed:

- The client `s` writer is on ESS `AttendanceController`, not on `AttendancedetailController`.
- The non-`nms` action has no located screen caller in the searched tree.
- Proedge and Viyellatex normal buttons call `approveatt` and, on the AUD-02 branch, do not set status `1` and do not change the day.
- Status `1` screen links that were found target other aliases through `attendanceapprovernms` and still do not change the day.
- Stored generation and the default payslip for company ids 24, 20, and 10 do not read overtime, late, or absence. Company 20’s register displays those day-result figures next to stored heads.

Unresolved:

- Whether a person can open `attendanceapprover` or `attendanceapprovernms` by typing the URL. Both actions accept client `s` and `id`. The lines read do not check that the session employee owns the application.
- Whether `vtlnms`, `sfls`, `palstorew`, or `vsl` are the same legal entities as company ids 24, 20, or 10. Source aliases alone do not establish that.
- Whether a salary-head file outside `actionGenerate`, `actionSalaryregister`, and `actionSinglepayslip` reads attendance for those three company ids. The controllers inspected here do not branch to such a file for ids 24 and 10, and the company 20 branch is the display register above.
- No automated test covers these legacy paths. The Laravel application has no attendance or payroll tables.

Proposal, not an approved requirement: keep attendance and payroll unbuilt until BD-D16 and BD-D17 are signed, and do not copy the Proedge status map or the company 20 register columns into the product as default rules.

## 9. Statement

No database was connected. MySQL was not started. No schema, application code, test, or environment file was changed. AUD-02 was not rewritten.

AUD-07 SOURCE TRACE COMPLETE
