# Universal HRMS — AUD-02 Leave and Attendance Approval Audit

Status: **SOURCE AUDIT COMPLETE**. No company database was queried. The closed-month audits were not re-run.

Observed steps are legacy behavior. They do not sign BD-D16 or BD-D17. Employee migration stays blocked.

## 1. Evidence

- `frontend\modules\ess\controllers\LeaveController.php` — day leave submit, `actionLeaveapprover`, `actionDeleteleave`
- `frontend\modules\ess\controllers\AttendanceController.php` — `actionRequestattapproval`, `actionApproveatt`, `actionApprovelist`
- `frontend\modules\ess\views\attendance\requestattapproval.php` and `requestattapprovalBangla.php`
- `backend\modules\attendance\views\default\timecard.php` status labels
- `backend\modules\attendance\controllers\AttendancesalaryheadController.php` one leave read
- AUD-01 for the separate hourly path
- Prior closed-month statement that a Proedge August approval did not change the stored day, and that a VTL sample changed a displayed punch. Those months were not re-read.

Hourly leave remains the AUD-01 path. `actionLeaveapprovernms` updates `hrm_leave_emp_app` from the hourly controller and is not the day-leave form.

## 2. Day leave

### Submit

`LeaveController` post, module name `leave`, session user level required. The employee id is the session employee.

Stored on `hrm_leave_emp_app` with status 0:

| Field | Source |
| --- | --- |
| `start_date`, `end_date`, `year`, `reason`, `days` | The form |
| `leave_type`, `leave_code` | The selected setting |
| `boss_id` | `employees.boss` |
| `gboss_id` | `employees.g_father` |
| `half_day`, `which_half`, `post_pre`, `documents`, `replace_date` | The form, on the branches that set them |

Validation that runs before insert:

- End date before start date redirects with `err=105`.
- An existing row for the same employee whose range covers the start date, and whose status is not 2, 4, or 6, redirects with `err=5`.
- Balance and tenure checks sit above the insert. They were not re-audited beyond the insert that follows them.

The overlap test uses only the start date against existing ranges. It does not test the new end date against an existing start.

### Approval

`actionLeaveapprover` requires module `leave` and a session user level. The lines read do not compare the session employee with `boss_id` or `gboss_id`.

| Button | Session database | New status |
| --- | --- | --- |
| `firstapp` | `atlas` | 3 |
| `firstapp` | any other name in that branch | 1 |
| `firstrej` | all | 4 |
| `secapp` | `ceva`, `pale`, or `aesbd` | 3 |
| `secapp` | any other name in that branch | 2 |
| `secrej` | all | 5 |

Status 1 or 4 emails `gboss_id` that a second approval is needed. That mail is a notice. It is not an authorization check.

Only status 3 changes a balance. Code `EL` updates `leave_emp_annual_leave` used and balance by `days`. Any other code updates `leave_emp_balance` the same way. Status 1 and status 2 do not. This action does not update `attendance_detail` or `employee_salary`.

### Delete

`actionDeleteleave` deletes the row when `emp_id` equals the session employee. The lines read do not test status and do not restore a balance already deducted.

### What this does not prove

The attendance processor’s day-status leave, already described in the closed-month audits, is not this approver. This audit did not locate an `attendance_detail` write inside `actionLeaveapprover`.

`AttendancesalaryheadController` reads `hrm_leave_emp_app` by date and leave code. The query read does not filter `status`. That is one salary-head read, not a full payroll posting.

## 3. Attendance correction

### Submit

`actionRequestattapproval` requires module `attendance` and a session user level. The insert is into `attendance_approval_file`.

The English form posts hidden status `2`. The Bangla form contains hidden status `0` and, later in the file, hidden status `2`.

Columns written: `employee_id`, `reason`, `reson_detail`, `date`, `inout`, `status`, `boss_id`, `gboss_id`, `batchtoken`, `boss_id_no`, `gboss_id_no`, `post_date`, `posted_by`. `intime` and `outtime` are inserted as null. `batchtoken` comes from `batchtoken2` when the session name is `ceva`, `atlas`, `aesbd`, `aesbdos`, or `proedge`. Otherwise it comes from `batchtoken`.

Boss and grand-boss ids are posted by the form. The code also copies employee codes from the session boss and grand-boss when those session values exist.

For reason `Out station duty` or `Absent`, a `todate` on or after `date` inserts one row per day. Other reasons insert one row.

For session names `vtlnms`, `isml`, and `vtl`, when no `attendance_application_setting` row exists for the employee code, and the posted `ml` is 40, 38, 37, or empty, a fourth application in the same calendar month stops the request. The stop message says the monthly limit is finished. This count is not applied to `proedge` or `atlas` in that branch.

### Approval

`actionApproveatt` requires module `attendance`. It then branches on the session database name.

| Session name | Who may change the row | What is written |
| --- | --- | --- |
| `atlas` | Session employee equals `boss_id` or `gboss_id` | Status becomes 1. If an `attendance_detail` row exists, punches and status are rewritten |
| `ceva` | Session employee equals `gboss_id` | Same day rewrite, status 1 |
| `ceva` | Session employee equals `boss_id` | Status only: 2 becomes 3, 3 becomes 4, 98 becomes 3. The day row is not rewritten on this branch |
| Any other name, including `proedge` and `vtl` | Session employee equals `boss_id` or `gboss_id` | Same status-only map as the CEVA boss branch. No `attendance_detail` update |

The Atlas and CEVA grand-boss rewrite:

| `inout` and reason | Day fields written |
| --- | --- |
| `OUT` and reason `Out punch miss`, with `batchtoken` | `employee_early_punch` becomes the request time. Status `Present`. If `eligible_for_ot` is 1 and the time is after shift end, `ot_hours` is the second difference. `day_status` is cleared |
| `OUT` and that reason, without `batchtoken` | Out punch becomes the shift end. Status `Present` |
| `OUT` otherwise | Out punch becomes the shift end. Status `Present` |
| `IN` | In punch becomes the shift start. Status `Present` |
| `INOUT` | In punch becomes the shift start and out punch becomes the shift end. Status `Present` |

No branch writes `employee_salary`. The raw punch table is not updated in this action.

`actionApprovelist`, for session names `isml` and `vtl`, sets status `99` on a boss queue row at status 2 after a date derived from the next non-holiday shift dates, or two calendar days when that shift lookup does not return two rows. That run is a lock of the application row. It does not rewrite the day.

Timecard labels in `timecard.php` map status 2 to Applied, 3 to 1st Approved, 4 to 2nd Approved, 1 to HR Approved, 10 to 1st Manager Reject, 15 to 2nd Manager Reject, 20 to HR Reject, 99 to Locked, and 98 to Unlocked. The approve action read here sets 1, 3, 4, and 99. It does not set 10, 15, or 20.

### Relation to the closed months

The Proedge August finding that an approval did not change the stored day agrees with the non-Atlas, non-CEVA branch: `proedge` updates only `attendance_approval_file`.

The VTL displayed-punch change is not produced by this `vtl` branch, which also updates only the application status. That display change remains a closed-month observation from a path this audit did not re-open.

## 4. Authorization summary

| Action | Check that is present | Check that is absent |
| --- | --- | --- |
| Day-leave submit | Module `leave`, session user, employee id from the session | — |
| Day-leave approve or reject | Module `leave`, session user | The session employee is not compared with `boss_id` or `gboss_id` |
| Day-leave delete | Module `leave`, row `emp_id` matches the session employee | Status is not tested. Balance is not restored |
| Attendance submit | Module `attendance`, session user | The posted `boss_id` is not checked against `employees.boss` |
| Attendance approve | Module `attendance`, and the session employee must match `boss_id` or `gboss_id` as the alias branch requires | A second distinct person is not required before Atlas or CEVA grand-boss writes the day |

Request values are concatenated into SQL in both controllers.

## 5. Classification

| Observed rule | Class | Approval state |
| --- | --- | --- |
| Day leave stores a date range, a type, a day count, and boss plus grand-boss ids from the employee row | LEGACY ONLY | Not a signed step list |
| Atlas first approval jumps to status 3 and can deduct balance. Other names stop at status 1, which does not deduct | CONFIGURABLE alias behavior | LEGACY ONLY. Do not copy the alias into the product |
| `ceva`, `pale`, and `aesbd` second approval reaches status 3. Other names stop at status 2, which does not deduct | Same | Same |
| Balance deduction is days against `leave_emp_annual_leave` or `leave_emp_balance` | LEGACY ONLY | Pay effect stays BD-D06 |
| Atlas and CEVA grand-boss approval can set the day to Present and replace in and out with shift times, or with the requested out time | LEGACY ONLY | Which fields may change stays BD-D16 |
| `proedge` and `vtl` approval in this action changes application status only | LEGACY ONLY | Agrees with the Proedge August day result. Does not explain the VTL display change |
| VTL and ISML can lock an application at status 99 from the approval list | LEGACY ONLY | Not a signed close rule |
| VTL, ISML, and VTLNMS can stop a fifth monthly application for selected management levels | CONFIGURABLE candidate | Not approved |
| Hourly leave | Separate | AUD-01. One approver, different tables |

## 6. Proposed checks

These are not approved tests.

- The signed-in approver is the assigned approver for that stage.
- A named stage list, after BD-D17 is signed, is the only path that deducts a balance or changes a day.
- A day change, after BD-D16 is signed, writes a new day-result version and does not edit the raw punch.
- Reject and delete do not leave a deducted balance in place.
- Payroll reads an approved outcome only, unless a signed rule says otherwise.

## 7. Still open

BD-D16 and BD-D17 are unsigned. The VTL displayed-punch writer was not identified in this action. Status codes 10, 15, and 20 are labeled on the timecard and were not set by `actionApproveatt`. No company row count was taken.

## 8. Statement

No database was connected. MySQL was not started. No schema, application code, or environment file was changed.

AUD-02 SOURCE AUDIT COMPLETE
