# Universal HRMS — AUD-06 Joiner and Leaver Month Audit

Status: **SOURCE AUDIT COMPLETE**. No partial month was queried. The Proedge, VTL, and Atlas closed months stay full-month evidence. Payable-day and proration defaults stay unsigned.

## 1. Trace

One employee row holds `joining_date`, `discontinue_date`, `active`, and `disconrinue`. Salary generation and attendance processing do not read a second employment period.

| Stage | What the source does |
| --- | --- |
| Who is selected for attendance | Full-month process selects `active=1`. It does not require `disconrinue=0`. Proedge, Atlas, and CEVA actions. Example: `actionProcessproedge` line 5362, `actionProcessatlas` line 5977, `actionProcessceva` line 6668 |
| Days before joining | If `joining_date` falls inside the month, the loop starts on that day number and deletes `attendance_detail` from the first of the month up to, and not including, the joining date |
| Days after leaving | Only when `disconrinue` is 1 and `discontinue_date` falls inside the month, the loop ends on that day and deletes later days in the month. Otherwise the loop runs through the last calendar day |
| Leave on a processed day | Status 3 leave still writes `leave_code` onto `day_status` for a day the loop creates. AUD-05 |
| Overtime on a processed day | `ot_hours` is still the post-shift seconds when `eligible_for_ot` is 1. No separate joiner or leaver overtime formula was found in these actions |
| Who is selected for salary | `generatesalarynoview.php` lines 86 and 96: `active=1`, `joining_date` not `0000-00-00`, `joining_date` on or before month end, `disconrinue=0` |
| Joiner proration | Lines 167–233. Days from `joining_date` through month end, plus one. If that count is below the divisor and the salary head `partial` is 1, amount is `value / divisor * those days`. Other heads stay the full calculated amount |
| Divisor | If `companies.has_provident_fund` is greater than 0, the divisor is 30. Otherwise it is the calendar length of the payroll month |
| Leaver salary | `disconrinue=1` removes the employee from this generate. `discontinue_date` is not in the proration formula |
| Finalization | `salary_month_lock` with `month_lock` Yes for that month, year, and company id stops generate. Lines 45–50. Attendance `month_lock` Yes stops process. AUD-05 |

`EmployeesalaryController::actionGenerate` only renders these views. The amount math is in the view and in `ApusalaryComponent::getCalculativeSalaryByHeadDemo`, which substitutes `BS` and `GS` into `salary_rule` and evaluates it. That function does not read `joining_date` or `discontinue_date`.

## 2. Joining inside the month

The process actions listed above share the joining window. A joining date inside the month drops earlier day rows and does not create them on that run. Weekly off still comes from `employees.photo`, and holidays still come from `att_holiday_calendar`, for days the loop does create.

A joining date before the month does not shorten the month. A joining date after the month does not fail the full-month `active=1` query, and the in-month test then leaves the start day at 1. A future joiner who is `active=1` can receive a full attendance month from that path. A single-day run adds `joining_date <=` that day, so that narrower run does not select them.

Salary includes a joiner whose date is on or before month end and whose `disconrinue` is 0. A zero joining date is excluded there. Proration applies only to heads marked `partial` = 1, and only when the joining-to-month-end day count is shorter than the divisor. A head with `partial` not equal to 1 is paid in full in the same month.

This screen was not executed. No company’s `partial` flags or `has_provident_fund` value were read.

## 3. Leaving inside the month

Attendance uses `discontinue_date` only inside the `disconrinue == 1` branch, and only when that date is inside the processed month. A flag of 1 with a date in an earlier month still sets the end day to the last calendar day. A flag of 0 ignores `discontinue_date` and processes the whole month.

Salary generation omits every `disconrinue=1` row. It does not prorate a leaver by `discontinue_date`. A person who left mid-month and has the flag set can have attendance days through the leaving date and no row from this generate. A person who has a leaving date and a flag of 0 can have a full attendance month and a full salary calculation.

`actionUpdate` on `EmployeesalaryController` checks module access. The lines read do not test `disconrinue` or `discontinue_date`. Whether a screen hides that action was not re-audited.

A lock of Yes blocks a new generate or a new attendance process for that month. The closed-month audits already found that the studied months were not closed by those flags. Those audits were not repeated. Nothing in this source makes a separated employee’s old month immutable once the flag changes.

There is one `joining_date` and one `discontinue_date` on the employee row. The appraisal upload can overwrite them and store the previous value on a change row. Payroll and attendance in this trace read the current columns, not a series of periods. Rejoining as a second episode is not implemented in these calculations.

## 4. Period boundaries

Attendance and this salary generate use the requested calendar month and year. They do not read a financial-year table.

`leave_financial_year.start_month` is read when a leave application screen opens (`HrmleaveempappController` around line 204). That is a leave-year boundary. AUD-01 already recorded a July–June proration on the hourly generator. The day-leave balance loop around line 1256 measures months since `joining_date` as total days divided by 30, and withholds `EL` until 12 such months unless the setting’s `max_days` is not greater than 1. Management level `MS` and company id 20 uses present days divided by 15. Those are leave-balance rules, not the salary divisor.

Bonus generation (`EmployeebonusController::actionGeneratevtl`) counts days from `joining_date` to a posted cutoff divided by 30.417, excludes `disconrinue=1`, and has a branch for session company ids 77 and 38. That is a bonus tenure test, not a payroll-month proration.

January–December, June–May, and March–February were not found as stored financial-year settings in this pass. They remain examples. A company financial year is a requirement that still needs a signed value. It is not an approved policy, and it is not the payroll period or the leave year.

## 5. Company evidence

| Evidence | What it supports | What it does not support |
| --- | --- | --- |
| Proedge, Atlas, and CEVA process actions | The same joining and leaving window in source | An operated partial month |
| Closed-month reports | Full months for Proedge August, VTL September, and Atlas September | A joiner or leaver payslip |
| `icwlw` and `valw` after salary insert | Maternity days with `attendance_status` `ML` reduce calculative heads by `(amount / calendar days) * (calendar days − ML days)`. Lines 1190–1212 | A Proedge, VTL, or Atlas rule. Those aliases are not in that `if` |
| Bonus company ids 77 and 38 | A tenure branch in bonus code | Catalog tenants 24, 20, and 10 |

Matching source among the three process actions is not a matching production month.

## 6. Classification

| Finding | Class |
| --- | --- |
| A day result exists only for days the run creates | CORE shape already specified. The legacy window is not the signed rule |
| Skip days before `joining_date` when it falls in the month | LEGACY ONLY until an owner signs the joining-month rule |
| Cut days after `discontinue_date` only when `disconrinue` is 1 and the date is inside the month | LEGACY ONLY. The flag pair stays the unsigned D3 question. Do not infer `separated` from the date alone |
| Full-month attendance for `active=1` even when the person already left, if the date is outside the month | LEGACY ONLY |
| Prorate `partial=1` heads from joining date through month end, divisor 30 when `has_provident_fund` is greater than 0, otherwise the calendar length | LEGACY ONLY. Not a universal divisor |
| Omit `disconrinue=1` from salary generate instead of prorating the leaving date | LEGACY ONLY |
| Absence pay and the payable-day basis | BUSINESS DECISION. BD-D07 is unsigned. This audit does not choose it |
| `ML` reduction for `icwlw` and `valw` | LEGACY ONLY |
| Leave year from `leave_financial_year`, hourly July–June, and payroll calendar month | Separate periods. A company financial year remains a requirement to validate, not an approved setting |
| Second employment period | UNKNOWN in these calculations. One row of dates is what they read |

## 7. Proposed checks

These checks are not approved requirements.

- Days before a signed joining date are not payable and are not attendance days.
- Days after a signed last working date are not payable. The legacy flag is not that date by itself.
- The divisor and which heads prorate are company configuration after an owner signs them. 30 and the calendar length are not defaults adopted here.
- A leaver is either included and prorated, or excluded, by a signed rule. This generate’s exclusion is not that rule.
- A locked payroll snapshot does not change when attendance is rebuilt. The legacy Yes flag is not that snapshot, which the payroll traceability row already says.
- Financial year, leave year, and payroll month stay separate fields.

## 8. Files inspected

- `AttendancedetailController.php` joining and leaving blocks in `actionProcess`, `actionProcessproedge`, `actionProcessatlas`, and `actionProcessceva`
- `ApuattendanceComponent.php` around line 2829, which sets the same style of start and end day for an ISML path and does not require `disconrinue` before using `discontinue_date`
- `payroll\views\employeesalary\generatesalarynoview.php`
- `EmployeesalaryController::actionGenerate` and `actionUpdate`
- `ApusalaryComponent::getCalculativeSalaryByHeadDemo`
- `HrmleaveempappController` leave-year and joining-month balance lines cited above
- `EmployeebonusController::actionGeneratevtl`
- Closed-month, Step 20R, AUD-01, and AUD-05 statements. Those months were not re-read row by row.

## 9. Still open

No operated joining month or leaving month was counted. Which heads are `partial`, and which companies have `has_provident_fund` above 0, are unread. BD-D07, D3, and D6 stay unsigned. A company financial year is not selected.

## 10. Next step

AUD-01 through AUD-06 are complete as source audits. SCOPE-01 stays deferred until an owner adds a module. DB-01 still waits until MySQL is already running. DEC-01 stays unsigned.

## 11. Statement

No database was connected. MySQL was not started. No application code, schema, migration, or environment file was changed.

AUD-06 SOURCE AUDIT COMPLETE
