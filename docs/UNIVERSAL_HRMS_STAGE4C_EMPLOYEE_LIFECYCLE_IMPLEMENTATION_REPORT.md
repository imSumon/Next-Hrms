# Universal HRMS — Stage 4C Employee Lifecycle Implementation Report

Date: 2026-10-12

Status: **COMPLETE FOR THIS STAGE**. No migration was added. No payroll calculation was started. No legacy employee was imported.

## 1. Baseline

Inspected Stage 4A and Stage 4B code, the employee and episode migrations, and the membership middleware before editing.

Implemented and verified before this stage, by `php artisan test`: **27 passed**, 125 assertions.

- ULID identity, company-scoped immutable employee code, and no code reuse after separation.
- Episode statuses `Open`, `inactive`, and `separated`. At most one Open episode. An Open episode cannot store a last working date.
- Login, active membership, and `can_manage_employees` for writes.
- No organization assignment table. The detail page already said so.
- Joining date exists only on `employment_episodes.joining_date`. There is no second start date on `employees`.

Gaps that were safe to close:

- An existing `inactive` or `separated` episode could be saved again as `Open`, which clears the last working date on that same row. That transition is not approved.
- The detail page did not show the identity, timestamps, or the fact that joining date is only on the episode.
- Tests did not cover a failed optional episode rolling back the new employee, a cross-company episode write, retargeting another employee, a view-only episode update, or the absence of a delete route.

Left for a decision, with no new rule:

- Episode date overlap, date order, inactive pay, and legacy `active` / `disconrinue`.
- Whether a future joining date changes attendance or payroll eligibility.
- Salary divisor, proration, and financial year.
- Who may assign a branch, department, or location. No assignment table exists.

## 2. Changes

`EmploymentEpisodeRegistry` refuses to set an existing non-Open episode back to `Open`. The message says that reopening the same episode, or creating a new one as the required path, is not approved. Creating a new episode is unchanged and still obeys the one-Open rule. The screen does not close another episode or invent a rejoin.

The employee detail page shows the identity, recorded time, and updated time. It states that joining date and last working date live on the episode, and that this identity has no salary or payable-day figure. Episode rows show their updated time.

No route deletes an employee or an episode. `DELETE /employees/{id}` returns 405.

Files:

- `next-hrms/app/Employees/EmploymentEpisodeRegistry.php`
- `next-hrms/resources/views/employees/show.blade.php`
- `next-hrms/resources/views/employees/episodes/edit.blade.php`
- `next-hrms/public/css/hrms.css`
- `next-hrms/tests/Feature/EmployeeLifecycleTest.php`

## 3. Unchanged

Employee code rules, ULID generation, company context, and `can_manage_employees` are unchanged. Overlap and date order are still not rejected. `inactive` still has no pay effect on this screen. Organization and location assignment was not added. No payroll field was added.

## 4. Schema

No migration. Payroll-ready facts already on the approved tables are: employee ULID, company, employee code, episode ULID, episode `joining_date`, `last_working_date`, `episode_status`, and both tables' timestamps. The episode foreign key is `(company_id, employee_id)`.

`joining_date` is the episode date. It is not a separate employee joining date, and this stage did not add one.

## 5. Security

Guest access still redirects to login. Writes still require an active membership and `can_manage_employees`. A posted `company_id` or `employee_id` on an episode is rejected. An episode in another company is not updated.

## 6. Tests

Command: `php artisan test` in `next-hrms`.

Before the change: **27 passed**, 125 assertions.

After the change: **31 passed**, 147 assertions.

The four new tests cover the ULID and the rolled-back invalid episode, the refused return to Open, cross-company and cross-employee episode writes, a view-only update, and HTTP 405 on delete. The database was `proedge_new_hrms_test`.

## 7. Decisions still required

- Whether a separated or inactive episode may become Open again on the same row, or only by a new episode.
- Episode overlap, date order, and the pay effect of `inactive`.
- Joiner and leaver proration, including BD-D07. The legacy divisors are not adopted.
- The employee assignment schema. `location_nodes` and `organization_nodes` are not an assignment.
- The universal role list. `can_manage_employees` stays a module grant.

## 8. Confirmation

No legacy employee data was imported. No production database was accessed. No legacy source code was modified. No unresolved policy was adopted as a rule. The return to Open was left unavailable rather than resolved.

## 9. Next stage

Decide the reopen-or-new-episode question and the assignment schema before adding payroll inputs.

STAGE 4C COMPLETE FOR THIS STAGE
