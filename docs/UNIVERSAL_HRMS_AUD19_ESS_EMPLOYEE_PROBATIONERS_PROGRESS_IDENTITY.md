# Universal HRMS — AUD-19 ESS Employee Probationers Progress Identity

Date: 2026-10-16

Status: **SOURCE TRACE COMPLETE FOR THIS BATCH.** No request was sent. No row was inserted, updated, or deleted. Source inspection is not runtime verification. Classifications are proposals, not approved requirements. No decision was signed. No company is migration-approved.

Controller: `frontend\modules\ess\controllers\EmployeeprobationersprogressController.php`. The admin PIMS controller of the same name was not opened. Provident fund was not opened. AUD-01 through AUD-18 were not repeated. The catalog denominator stays 25 modules, 101 submodules, and 416 controller records.

## 1. Actions

| Action | Lines | Gate | What the source shows |
| --- | --- | --- | --- |
| `actionIndex` | 24–94 | Module list must contain `pims`. A user level is required | The render passes `getEmpInfo(sesdata('employee_id'))`. On post, it inserts `employee_probationers_progress` from posted fields, including `emp_id`, `emp_id_no`, and `status`. It then updates `employees.education_info`, `employment_history`, and `year_of_experience` where `id` is that posted `emp_id`. The connection is `session user.db` |
| `actionIndexx` | 101–113 | No user-level or module test in the action | `EmployeeProbationersProgressSearch::search` on `Yii::$app` plus `sesdata('dbname')`. `emp_id` is filtered only when the query supplies it |
| `actionView` | 120–128 | None in the action | `findModel` then the view |
| `actionCreate` | 135–149 | None in the action | `load` of the post and `save`. The action does not set `emp_id` from the session |
| `actionUpdate` | 157–171 | None in the action | `findModel($id)`, then `load` and `save` |
| `actionDelete` | 179–187 | None in the action | `findModel($id)->delete()`. No status test |
| `actionPpapplication` | 209–267 | Module list must contain `pms`. A user level is required | A post updates `employee_probationers_progress` where `id` is posted `lid`. When `who` is `1st`, it stores the posted `status` and the appraiser comment fields. Otherwise it stores `g_management_decision`, the posted `status`, and `h_management_remarks`. A non-post renders `ppapplication` with request `id` and `w` |
| `actionPpapplicationview` | 270–291 | Module `pms` and a user level | Renders `ppapplicationview` with request `id` and `w` |

`findModel`, lines 196–206, calls `EmployeeProbationersProgress::findOne($id)` after `EmployeeProbationersProgressSearch::setDb`. The search class extends the model, and `setDb` writes `self::$_db` on the model, so that call selects `sesdata('dbname')`. The find does not compare `emp_id`. `VerbFilter` is imported and no `behaviors()` method is present, so this file does not add a verb limit.

`views\employeeprobationersprogress\index.php` lines 5–6 and 218 list rows for the session employee's `emp_id_no`. The open list excludes status `Closed`. A second list keeps status `Closed`. Lines 52–67 put the session employee id, employee number, appraiser numbers, and status `New` in hidden fields. Lines 187–192 label `New`, `Appraiser Responded`, `Management Decision`, and `Closed`.

`ppapplication.php` lines 5 and 27–28 load the row by `$lid` and post status `Appraiser Responded` or, when `who` is `2nd`, `Management Decision`. `ppapplicationview.php` line 5 loads the row by `$lid` and does not compare the session employee. Line 7 then loads the employee with `getEmpInfoByIDNo` for that row's `emp_id_no`.

The index view shows the new form only when `emp_type` is `Probationer` and the open-row query returns no rows (`index.php` lines 37–40). `actionIndex` does not repeat either test. `views\default\index.php` lines 203 and 253, and the same queries in `notifications.php`, list rows whose `appraiser_id` or `second_appraiser_id` equals the session `emp_id_no`. The review action does not compare those fields to the session employee. The links pass the row id and `w` of `1st` or `2nd`.

The raw insert does not call `save()` or the model rules. The model rules require `emp_id`, `emp_id_no`, `name`, `date_of_joining`, `job_title`, and `department`. A commented `getDb` that would return `vtl` is not active. The model defines no relations. `actionDelete` calls `delete()` on that one row. No second table and no file delete were found in the action. No link to `actionDelete` was found in the probation views or in the two notification views above.

`actionIndex` and the review views use the session object `user.db`. The CRUD actions use `sesdata('dbname')`. `actionPpapplication` reads `user.dbname` into `$dbname` and does not use that variable. Whether those names are the same connection was not established. None of the probation queries add an `emp_unit` predicate.

## 2. Findings

| ID | What the source shows | What it does not establish | Proposal | Inference, labeled as such |
| --- | --- | --- | --- | --- |
| PRB-01 | The ESS index lists the session employee number and separates `Closed` | A company-unit predicate. The query has none beyond the session connection | **KEEP** that list filter | None |
| PRB-02 | The index post stores the posted employee and status, then updates that id on `employees` | Whether a browser sends only the hidden session values | **REDESIGN** the missing binding. The view prefill is not the action rule | A different posted id could change another employee's education fields. Not executed |
| PRB-03 | View, update, delete, and `actionIndexx` use the id on the named connection. Delete has no status test. Those four actions have no module test in the file | A route filter outside the action | **REDESIGN** | None |
| PRB-04 | Review writes the posted status for `lid` and does not compare `appraiser_id` or the previous status. The notification lists do filter those appraiser fields. The view shows four status labels | An allowed order among those labels. The action does not code one | **REDESIGN** the missing checks. **UNRESOLVED** as a lifecycle rule | Do not treat the four labels as a signed probation policy |
| PRB-05 | The forms that were read post `New`, `Appraiser Responded`, or `Management Decision`. No statement in the files that name this table assigns the literal `Closed` | A writer of `Closed` outside those files. A caller of `actionDelete`. Whether `user.db` and `sesdata('dbname')` are the same connection | **UNRESOLVED** | The admin PIMS controller of the same name was not opened. Appadmin `actionProbationers` in AUD-03 opens a company. It is not this ESS workflow |

Nothing in this batch is **REMOVE** or **CONFIGURE**. The module string is `pims` on the form and `pms` on the review. That split was observed. It is not a signed module boundary and it is not a reason to open PMS content. No exploit was demonstrated.

## 3. Runtime

No probation form was submitted. No employee row was changed. No review was saved. The findings are source-traced.

## 4. Statement

No application code, schema, test, configuration, database, or Git state was changed. The legacy tree was not modified. The identity register and the active control center gained this batch. No gate was signed.

AUD-19 SOURCE TRACE COMPLETE FOR THIS BATCH
