# Universal HRMS — AUD-20 ESS Assessment 360 Identity Reads

Date: 2026-10-16

Status: **SOURCE TRACE COMPLETE FOR THIS BATCH.** No request was sent. No rating was saved. Source inspection is not runtime verification. Classifications are proposals, not approved requirements. No decision was signed. No company is migration-approved. This is not a PMS design.

Controller: `frontend\modules\ess\controllers\Assessment360degreeController.php`. Submit actions exist and were not traced. Provident fund, recruitment, and `PmsController` were not opened. AUD-01 through AUD-19 were not repeated. The catalog denominator stays 25 modules, 101 submodules, and 416 controller records.

## 1. Read actions

Every action below requires the module string `assessment360degree` and a user level. A missing level redirects to `/site/index`. A missing module redirects to `/ess`, except the app panel of `actionIndex`, which prints `Locked` and stops. The connection used by the queries that were read is the session object `user.db`. No action in this file selects a database from the request. No `emp_unit` predicate was found. There is no search model. The controller does not import an ActiveRecord class.

| Action | Lines | What the read path shows |
| --- | --- | --- |
| `actionIndex` | 14–64 | Renders `index`, `index_apps`, or, when the user level is `customer`, `indexcustomer`. The controller does not query |
| `actionPms360viewbo` | 203–245 | Loads `pms_360_periods` by `$_REQUEST['id']`. Loads `pms_360_emp_period_entry` only where `emp_id` is `sesdata('employee_id')` and `period_id` is that id. Renders `pms360viewbo` when a row exists |
| `actionPms360superviserbolist` | 247–268 | Renders `pms360superviserbolist`. The view queries |
| `actionPms360superviserviewbo` | 375–418 | Loads the period by `$_REQUEST['id']` and `pms_360_emp_period_entry` by `$_REQUEST['pepe']`. The session employee is read and is not in that `where` |
| `actionPms360colleaguebolist` | 420–441 | Renders `pms360colleaguebolist` |
| `actionPms360colleagueviewbo` | 550–593 | Same period-and-`pepe` load as the supervisor view |
| `actionPms360peersbolist` | 595–616 | Renders `pms360peersbolist` |
| `actionPms360peersviewbo` | 725–768 | Same period-and-`pepe` load |
| `actionPms360customerbolist` | 773–795 | Layout `mainext`. Renders `pms360customerbolist` |
| `actionPms360customerviewbo` | 888–933 | Layout `mainext`. Same period-and-`pepe` load |
| `actionPms360customerbolistinternal` | 936–957 | Renders the same `pms360customerbolist` view |
| `actionPms360viewparameters` | 959–981 | Renders `pms360viewparameters`. That view lists `pms_360_behavioral_objectives` for `$_REQUEST['year']`. It does not query an employee |

`actionPms360submit`, `actionPms360supervisersubmit`, `actionPms360colleaguesubmit`, `actionPms360peerssubmit`, and `actionPms360customersubmit` write ratings. They are outside this read audit.

## 2. Lists

`index.php` line 28 lists every row of `pms_360_periods` on `user.db`. For each period it then loads the signed-in employee's own row:

- line 36, `pms_360_emp_period_entry` where `emp_id` is the session employee, `period_id` is the period, and `employee_completed` is `Yes`
- line 37, `pms_360_period_start` for the same employee and period
- lines 111–115, entries where `superviser`, `colleague`, `peers`, or internal `customer` equals the session employee

The links pass the period id, not another employee's id (`index.php` lines 75, 80, 95, 123, 128, 133, 139). `index_apps.php` repeats the own-entry and the supervisor, colleague, and peers filters. It has no customer block in the lines read. `indexcustomer.php` line 57 lists entries where `customer` equals the session employee and links to `pms360customerbolist` with the period id.

The role lists filter the entry table and then show the subject's name:

| View | Entry filter | Link |
| --- | --- | --- |
| `pms360superviserbolist.php` line 19 | `superviser` equals the session employee and `period_id` is the requested id. Line 13 stops when the period responder list does not contain `Supervisor` | Submit and view links pass `id` and `pepe` equal to the entry id (lines 89 and 137) |
| `pms360colleaguebolist.php` line 18 | `colleague` equals the session employee. Line 13 stops unless the responder list contains `Colleague` | Lines 86 and 131 |
| `pms360peersbolist.php` line 18 | `peers` equals the session employee. Line 13 stops unless the responder list contains `Peers` | Lines 86 and 131 |
| `pms360customerbolist.php` line 19 | `customer` equals the session employee. Line 13 stops unless the responder list contains `Customer` | Lines 89 and 136 |

`actionPms360customerbolistinternal` uses that same customer view. The index badge at `index.php` line 115 also requires `customer_internal_external = 'Internal'`. The list view does not repeat `Internal`. Both queries still require `customer` to be the session employee.

No notification file outside this view folder was found that builds these links. Copies under `views\multiraterassessment\0` repeat some of the same links. That controller was not opened.

## 3. Detail views

`pms360viewbo.php` line 15 loads `pms_360_emp_period_entry` where `emp_id` is the session employee and `period_id` is the requested id. Lines 92 and 105 load that employee's ratings. The page prints the session employee's own rating, a check mark for the other responder columns, the average, and `final_rating`. A requested period id does not by itself select another employee's entry.

The four rater views load the entry by `pepe` only:

- `pms360superviserviewbo.php` line 16
- `pms360colleagueviewbo.php` line 16
- `pms360peersviewbo.php` line 16
- `pms360customerviewbo.php` line 17

Each view then loads `employees` with `select *` where `id` is that entry's `emp_id`, and loads `pms_360_emp_period_bo_rating` and `pms_360_emp_period_kpi_rating` for that same subject and the requested period. The session employee is assigned and is not added to those `where` clauses. The rendered fields that were read are the subject's name, department, designation, and rating columns. Pay, bank, TIN, and passport fields were not printed in these views. The rater pages print the matching role's number and a check mark for the other roles. `final_rating` is inside a comment on those four pages.

The entry query does not require `period_id` to equal `$_REQUEST['id']`. The rating queries use the requested period and the subject from `pepe`. Those two ids are not compared in the lines read.

## 4. Findings

| ID | What the source shows | What it does not establish | Proposal |
| --- | --- | --- | --- |
| A360-01 | The own-assessment list and `actionPms360viewbo` / `pms360viewbo.php` load the entry and ratings for the session employee | A company-unit predicate. Scope is the session connection | **KEEP** |
| A360-02 | The four role lists load entries only when the matching role column equals the session employee. The links pass that entry's id | That the view action repeats the role test | **KEEP** the list filters |
| A360-03 | Each rater view loads `pms_360_emp_period_entry` by `pepe` and then the subject's employee and ratings. The session employee is not in the `where` | A route filter outside the action. Nothing was requested | **REDESIGN** the missing role test on those four views. This is source text. It is not an executed access |
| A360-04 | `actionPms360viewparameters` lists objectives for a requested year. It does not load an employee | Whether those objective rows differ by company inside one connection. Whether `pepe.period_id` must match the requested period. The submit actions | **UNRESOLVED** |

Nothing in this batch is **CONFIGURABLE**, **BUSINESS DECISION**, or **REMOVE**. The responder tokens `Supervisor` and `Superviser` both appear in the views. Whether the stored period value matches the token that stops the supervisor list was not established. No exploit was demonstrated.

## 5. Coverage after AUD-20

AUD-08 through AUD-20 are bounded identity traces of named ESS actions. They are not a pass over 416 controllers. The repeated read pattern is a list that names the session employee and a later view that loads a requested id. Further one-controller audits will mostly restate that pattern.

The ESS controllers still not opened are `GfmanagementController`, `MultiraterassessmentController`, `PmsController`, and `RecruitmentController`. Provident fund, gratuity, recruitment, and PMS content stay deferred. Already named unread slices, not new controllers, are the admin service book, mobile-bill approval, the unread Atlas approval bodies, salary card, and tax 108.

The efficient next documentation step is one matrix of those remaining non-deferred read actions: record only whether the list `where` names the session employee and whether the view `where` repeats it. Do not open another full controller audit until that matrix says a file behaves differently. Do not start that matrix in this batch.

## 6. Statement

No application code, schema, test, configuration, database, or Git state was changed. The legacy tree was not modified. The identity register and the active control center gained this batch. No gate was signed.

AUD-20 SOURCE TRACE COMPLETE FOR THIS BATCH
