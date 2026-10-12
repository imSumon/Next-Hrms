# Universal HRMS — AUD-01 Hourly Leave Functional Audit

Status: **SOURCE AUDIT COMPLETE**. No company database was queried. Stored hourly-leave rows for Proedge, VTL, and Atlas were not counted.

This audit does not approve a requirement and does not authorize development or employee migration.

## 1. Evidence

Read: the project control center, the Step 21E plan, requirements leave section, the Step 21 matrix, and the closed-month audits as already summarized. Those month audits describe day-status leave. They were not re-run.

Source read, not modified:

- `frontend\modules\ess\controllers\HourlyleaveController.php`
- `frontend\modules\ess\views\hourlyleave\indexhourly.php` and `index.php`
- `backend\modules\hourlyleave\controllers\HourlyleaveempappController.php` (`actionGenerateleavebalance`)
- `backend\modules\hourlyleave\models\HourlyLeaveEmpApp.php` and `HourlyLeaveSetting.php`

Searches of `backend\modules\attendance` and `backend\modules\payroll` found no reference to `hourly_leave`.

## 2. Workflow that the code implements

The employee panel is `ess/hourlyleave`. The module name checked in the session list is `hourlyleave`. Catalog module 30 is `hourlyleave`. Submodule 96 holds the setting controllers. Submodule 98 is the report. Submodule 97, the upload row, has no controller in the catalog and no upload action was found in this module.

| Step | Where | What the code does |
| --- | --- | --- |
| Open the hourly form | `actionIndexhourly` GET | Renders `indexhourly` when the session has a user level and the module list contains `hourlyleave` |
| Choose a type | `indexhourly.php` | Loads `hourly_leave_setting` where `short_code` is not `ML`. JavaScript shows the casual/annual hour list or the sick list |
| Submit | `actionIndexhourly` POST | Inserts one row into `hourly_leave_emp_app` on the session company connection |
| Notify | Same action | Emails `employees.boss` |
| Approve or reject | `actionLeaveapprover` | Sets status 3 or 4. Status 3 reduces the balance tables |
| Delete | `actionDeleteleave` | Deletes the row when `emp_id` matches the session employee |
| Admin balance | `actionGenerateleavebalance` | Creates missing balance rows for employees with `disconrinue = 0` |
| Report | `backend\modules\hourlyleave\views\default\leavereport.php` | Sums approved rows. It does not post payroll |

There is no ESS edit action. The pending-list edit link is commented out. Admin CRUD screens exist for the application, balance, annual balance, setting, year, and holiday tables. They were not treated as the employee request path.

`actionIndexdays` in the same controller writes **day** counts into the same `hourly_leave_emp_app` table. `actionLeaveapprovernms` updates `hrm_leave_emp_app`, the other leave table. Those are separate actions. They are not the hourly submit.

## 3. Verified request behavior

Table `hourly_leave_emp_app`, from the model: `emp_id`, `emp_id_no`, `leave_type`, `start_date`, `end_date`, `days`, `hours`, `mins`, `reason`, `status`, `year`, `posted`, `first_approver`, `leave_code`, `half_day`, `which_half`, `documents`, `remarks`, `first_approver_date`.

On hourly submit (`HourlyleaveController.php` around the insert in `actionIndexhourly`):

- `start_date` and `end_date` are both set from `start_date`. The form has one date field. A stored hourly request is a single date.
- Duration for AL and CL comes from `clhour`. Options run from `0:30` through `7:30` in 30-minute steps. Whole-hour options are stored as `1`, `2`, and so on, without a minute token.
- SL uses `slhour`. The only option is `4`.
- The balance check converts a day to 480 minutes (`balance_day * 8 * 60`), an hour to 60, and adds `balance_min`.
- AL and CL requested minutes are hours times 60 plus the minute token. SL requested minutes are hours times 60 only. The minute token is not added for SL.
- Pending rows (`status < 3`) are summed into the same minute total and subtracted before the new request is accepted.
- If the requested minutes exceed the balance, or exceed balance minus pending, the action redirects with `err=2` or `err=8`.
- If no balance row exists, the action does not insert and does not send an error redirect.
- `first_approver` is `employees.boss`. Status is 0. `half_day` stores `time_from` and `time_to` joined by a hyphen.
- `which_half` is the second piece of `start_date` split on `-`. An HTML date is `YYYY-MM-DD`, so this stores the month number.
- A setting whose third form token is `1` requires an uploaded file. The setting model names that flag `document_required`.
- The overlap query against existing applications is inside a comment. It does not run. The day action `actionIndexdays` still runs the equivalent check and excludes statuses 2, 4, and 6.

No code in this submit compares the clock span with a shift, rejects a span that passes midnight, or rejects a second row on the same date.

## 4. Verified approval, cancel, and balance behavior

`actionLeaveapprover` accepts `submit=firstapp` as status 3 and `submit=firstrej` as status 4. It requires the module name `hourlyleave` and a session user level. The lines read do not compare the session employee with `first_approver`.

Status 3, for leave code `AL`, updates `hourly_leave_emp_annual_leave`. Any other code updates `hourly_leave_emp_balance` for that code and year. The cut is `days * 480 + hours * 60 + mins`. The new balance and used totals are converted back with an 8-hour day. If the remaining hour has a fractional part, the stored minute value is 30. If it does not, the stored minute value is 0.

Status 4 does not change the balance tables.

The email text for status 3 says the request was approved by the second approver. The form path that sets status 3 is the first-approver button. Status labels on `index.php` show status 3 as `Approved` and status 2 as `Approved by 2nd approver`. This hourly action does not set status 1 or 2.

`actionDeleteleave` deletes the row when `emp_id` equals the session employee. The lines read do not test status. They do not add the duration back to the balance. The notification query uses `boss_id`. The insert writes `first_approver`. When the session database name is `isml`, the delete email is skipped.

## 5. Attendance and payroll

No statement in `actionIndexhourly`, `actionLeaveapprover`, or `actionDeleteleave` writes `attendance_detail` or `employee_salary`.

`backend\modules\attendance` and `backend\modules\payroll` do not reference `hourly_leave`.

`actionUnit` and `actionUnitms` count public holidays from `leave_holidays` and read `emp_shift_calendar` for day status `H`. They return a day count for the day form. They are not called by the hourly insert.

The closed-month audits remain the evidence for day-status leave. They are not evidence that an hourly request changed a day result, late, early, absence, or a salary amount. Whether any studied month contains `hourly_leave_emp_app` rows was not queried.

## 6. Eligibility generator

`actionGenerateleavebalance` selects employees with `disconrinue = 0`. The year label switches at July: before July it is the previous year through the current year; from July it is the current year through the next year. Proration uses months to 30 June.

- `ML` is created only when `emp_sex` is `Female`, using `max_days`.
- `AL` is created on `hourly_leave_emp_annual_leave`, prorated when the month count is under 12.
- Other codes are created on `hourly_leave_emp_balance` with the same proration.
- The employee form hides `ML` and does not hide `AL`. A commented block in the view would have hidden `AL` below a tenure threshold. That block is not active.

`eligible_period` is described on the setting model as `100` for prorata and `0` for none. The generator uses the month count, not that column.

## 7. Companies

The duration, overlap, and balance code has no branch on the company alias. The connection is the session company database, so each company has its own rows. That is not a measured difference in rules.

Verified code branches:

| Session database name | Behavior |
| --- | --- |
| `atlas` | Approval mail is also sent to `companies.company_email` |
| any other name in that mail block | Approval mail uses a BCC variable that this action does not assign in the lines read |
| `isml` | Delete mail is not sent |
| any other name on delete | Delete mail is sent, including a fixed BCC address written in the source |

Proedge, VTL, and Atlas stored hourly-leave volumes, balances, and day effects are **unknown**. The three closed-month audits did not read these tables.

## 8. Classification

| Finding | Class | Status |
| --- | --- | --- |
| A company can define leave types with a code, a maximum, and a document flag | CONFIGURABLE | Observed in `hourly_leave_setting`. Not a new approval |
| An employee can request a duration on one date, with a reason, through one approver | LEGACY ONLY as this screen. Not yet a signed universal rule | The in-scope text `LEAVE-CORE-002` is a date range. It does not name hours |
| Approved leave can change a balance stored in days, hours, and minutes | LEGACY ONLY | Uses a fixed 8-hour day and writes minutes as 0 or 30 |
| Approved hourly leave writes the attendance day | Not observed | UNKNOWN as a business desire. Absent in this code |
| Payroll reads hourly leave | Not observed | UNKNOWN as a business desire. Absent in this code |
| Overlap is rejected | Not in force on the hourly submit | The check is commented out. Do not treat that as an approved policy |
| Who may approve, and whether a second step exists | BUSINESS DECISION | Matches the still-unsigned approval step list |
| Whether a type is paid | BUSINESS DECISION | BD-D06 remains unsigned. This code does not touch salary |
| Female-only ML and July–June proration | LEGACY ONLY | Generator behavior. Not approved as the universal rule |
| Month number stored in `which_half` | LEGACY ONLY | Defect in the hourly insert. The report groups by that column |

Unsafe behavior verified in this code, without a repair:

- The hourly overlap check does not run.
- The approver action does not check that the signed-in employee is `first_approver`.
- Request values are concatenated into SQL.
- Delete removes any of the employee’s rows and does not restore a balance already deducted.
- Delete looks up `boss_id` after the insert stored `first_approver`.
- Status 3 is stored while the mail text says the second approver approved.
- A fractional hour remainder is stored as 30 minutes or as 0.

## 9. Effect on the universal requirement

Hourly leave does **not** replace `LEAVE-CORE-004`. The day-leave path can still mark a day. This module does not.

Hourly leave is **not** added to the first-release `shall` list by this audit. A future owner can add a configurable duration on one work date. Until that decision is signed:

- Do not default the day length to 8 hours.
- Do not default the minute remainder to 30.
- Do not default payroll or the day result to ignore hourly leave, and do not default them to consume it.
- Keep BD-D06 and the approval step list unsigned.

Proposed acceptance checks, not approved tests:

- A request stores one work date, a duration, a from-time, and a to-time, and leaves the day result unchanged unless a signed rule names the change.
- A second request that overlaps the stored clock span is rejected or allowed only under a signed rule.
- The signed-in approver is the assigned approver.
- Status 3 reduces the matching balance by the requested minutes, and a later delete restores that balance.
- Payroll and attendance queries in a test company do not change unless the signed rule says they do.

## 10. Remaining gaps

| Gap | Why it is still open |
| --- | --- |
| Row counts for Proedge, VTL, and Atlas | No database was opened |
| Whether any company turns the module on | `company_modules` was not queried for module 30 |
| Shift-cross and period-cross outcomes | The insert never reads the shift |
| Pay effect | No salary write, and BD-D06 is unsigned |

## 11. Statement

No database was connected. MySQL was not started. No schema, application code, or environment file was changed. The closed-month audits were not rewritten.

AUD-01 SOURCE AUDIT COMPLETE
