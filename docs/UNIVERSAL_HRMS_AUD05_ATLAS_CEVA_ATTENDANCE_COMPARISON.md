# Universal HRMS — AUD-05 Atlas and CEVA Attendance Comparison

Status: **SOURCE COMPARISON COMPLETE**. No CEVA month was available. No database was queried. No CORE requirement changes.

## 1. Gate

The design question is whether `actionProcessatlas` or `actionProcessceva` is a different attendance engine from the Proedge process action, in a way that would add a core rule, a new configurable family, or a company-boundary safeguard.

That question can change the configurable grace values already left unsigned. It does not require a new full month audit. A CEVA operated month is not in the local databases named by the third-company audit, so operated CEVA results stay UNKNOWN. Absence of that month is not proof that CEVA never processed attendance.

## 2. What each source supports

| Step | Atlas action `actionProcessatlas` | CEVA action `actionProcessceva` | Proedge action, for comparison |
| --- | --- | --- | --- |
| Company database | Session alias via `setDb` on the day, employee, shift, leave, lock, approval, and card-map models. Lines 5903–5910 | Same. Lines 6594–6601 | Same. Lines 5292–5299 |
| Raw punches | `MIN(date)` and `MAX(date)` from `all_att_log` on `Yii::$app->db`, filtered by the company card number and the shift punch window | Same | Same |
| Work date | The loop date. The punch clock is not rewritten into the work date | Same | Same |
| Weekly off | `explode` of `employees.photo` | Same | Same |
| Shift | `employees.shift_id` | Same | Same |
| Holiday | `att_holiday_calendar` for that month | Same | Same |
| Late grace | 31 minutes after shift start. Line 6067 | 16 minutes after shift start. Line 6758 | 16 minutes after shift start. Line 5456 |
| Early out | Out punch before 31 minutes ahead of shift end. Lines 6069 and 6246 | Out punch before shift end. Line 7010 | Out punch before shift end. Line 5709 |
| Incomplete punch | Approval row at status 1 for `IN`, `OUT`, or `INOUT` can replace the day punch with the shift time | Same status 1 test | Same status 1 test |
| Leave | `hrm_leave_emp_app` status 3 writes `leave_code` onto `day_status`. Line 6470 | Same. Line 7079 | Same. Line 5778 |
| Overtime quantity | If `eligible_for_ot` is 1, `ot_hours` is the second difference after shift end | Same | Same |
| Payroll | This action does not write `employee_salary` | Same | Same |
| Lock | `attendance_month_lock` with `month_lock` Yes for that month and year stops the run. Line 5966 | Same. Line 6657 | Same. Line 5355 |
| Location overlay | `location_access_data.company_name` is the literal `proedge`. Line 6202 | Same literal. Line 6892 | Same literal. Line 5591 |

Recalculation deletes the employee-day in `attendance_detail` and inserts a new row. It does not update `all_att_log`.

## 3. Operated evidence, kept separate

Atlas September 2026, from `UNIVERSAL_HRMS_THIRD_REFERENCE_COMPANY_CLOSED_MONTH_AUDIT.md`:

- Attendance exists for the month. The lock row is January 2025, not September 2026.
- `shift_id` on the day matches `employees.shift_id`. The roster is empty. `photo` is `Friday,Saturday`.
- Calculated overtime, payable overtime, and an overtime pay head are absent in that month.
- The report concluded Atlas does not create a third attendance engine. Clocks, the late label, the head list, and report buttons differ and were classed as configuration or extension.

This comparison does not prove that September was produced by `actionProcessatlas`. The 31-minute constants are in that action. The September audit did not measure grace minutes.

CEVA operated attendance and payroll: UNKNOWN. The third-company audit states there is no local CEVA database. The CEVA action matching the Proedge action is source evidence only.

VTL remains the factory month already audited. It is not an Atlas or CEVA result.

## 4. Material or cosmetic

| Difference | Effect on the universal design |
| --- | --- |
| 16-minute late grace on the Proedge and CEVA actions, 31-minute late grace and 31-minute early buffer on the Atlas action | CONFIGURABLE values already inside the unsigned grace, late, and early gap. Not a new core engine |
| Separate action names and views | LEGACY ONLY routing. The session alias selects the database. The action name is not a tenant id |
| `company_name='proedge'` inside the Atlas and CEVA location query | LEGACY ONLY defect in the location text. AUD-04 already separated `location_area` from `all_att_log.area`. It does not move the day rows to the Proedge database, because those models use `setDb` on the session alias |
| Shared MIN/MAX punch, photo weekend, employee shift, status-3 leave code, status-1 approval substitution, lock flag, and OT seconds | Supports the existing conclusion: one processing shape, with company values. No core revision |

No requirement class changes. BD-D01, BD-D02, BD-D04, and BD-D05 stay unsigned. BD-D13 and BD-D14 stay unsigned. PUNCH-APPROVED is unchanged.

## 5. Proposed checks

These checks are not approved requirements.

- Grace minutes and the early-out threshold are company configuration. The numbers 16 and 31 are not a universal default.
- A lock flag for a different month does not close the month being processed. Atlas September already shows a January 2025 lock beside an unlocked September 2026.
- Location text is not company attribution. Company attribution of the day remains the session company database.
- A CEVA operated month, if one is later loaded, is compared with this source. It is not assumed from the Proedge action.

## 6. Further investigation

Reading these three actions again will not change the architecture. A loaded CEVA month could still show that production used another path. That possibility stays open. It does not justify another source pass now.

## 7. Next step

AUD-06, joiner and leaver pay, is the next item in the Step 21E plan. DB-01 still waits until MySQL is already running. DEC-01 stays unsigned.

## 8. Statement

No database was connected. MySQL was not started. No application code, schema, migration, or environment file was changed.

AUD-05 SOURCE COMPARISON COMPLETE
