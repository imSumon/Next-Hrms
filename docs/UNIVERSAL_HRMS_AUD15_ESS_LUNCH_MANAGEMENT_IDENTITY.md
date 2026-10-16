# Universal HRMS — AUD-15 ESS Lunch Management Identity

Date: 2026-10-16

Status: **SOURCE TRACE COMPLETE FOR THIS BATCH.** No request was sent. Source inspection is not runtime verification. Classifications are proposals, not approved requirements. No decision was signed. No company is migration-approved.

Controller: `frontend\modules\ess\controllers\LunchmanagementController.php`. This batch is `actionIndex`, `actionDelete`, and `actionDeletea`. The commented bill actions that follow line 302 were not opened. AUD-01 through AUD-14 were not repeated. Provident fund was not opened. The catalog denominator stays 25 modules, 101 submodules, and 416 controller records.

## 1. `actionIndex`

Lines 15–222. The action requires the session module list to contain `lunchmanagement` and a user level. The connection is `session user.db`. It reads the first `lunch_management_setting` row. There is no company-id predicate on that query. The session connection is the database boundary in these lines.

When `lunch_type` is `Monthly` and the request is a post:

- Lines 42–61 insert `lunch_management` from posted `emp_id`, `first_approver`, and `second_approver` when posted `aid` is `0`. Status is set to 0 in the statement.
- Lines 66–80 update `lunch_management` where `id` is posted `aid` when `aid` is not `0`. The update does not compare `emp_id`.
- Lines 87–89 then look up employees by `emp_id_no` equal to those posted values. The mail block at lines 126–138 is commented out.

When `lunch_type` is anything else and the request is a post, lines 167–174 insert `lunch_management_daily` from posted `emp_id` and `full_name`, with status `New`.

The render passes `getEmpInfo(sesdata('employee_id'))`. The controller does not replace the posted `emp_id` with that session value. `sesdata('employee_id')` is not used in the insert or update.

`views\lunchmanagement\index.php` lines 95–97 and 214 put the session employee's `emp_id_no` in a hidden `emp_id` field and list `lunch_management` rows for that number. The hidden `aid` at line 102 is the enrollment row found for that number. `views\lunchmanagement\index_daily.php` lines 47–57 and 114 do the same for `lunch_management_daily`. Those list queries are in the views. The post actions do not repeat them.

## 2. `actionDeletea`

Lines 225–261. The same module and user-level tests apply. Line 239 loads `sesdata('employee_id')` into `$emp_id`. The lines that follow do not read `$emp_id`. Line 241 loads `lunch_management` by `$_REQUEST['lid']`. Line 251 deletes that id. There is no status test and no second table. No link to `deletea` was found under `views\lunchmanagement`.

## 3. `actionDelete`

Lines 263–300. The same module and user-level tests apply. Line 277 loads the session employee and does not use it. Line 280 loads `lunch_management_daily` by `$_REQUEST['lid']`. Line 290 deletes that id. `$_REQUEST['ee']` is not read.

`views\lunchmanagement\index_daily.php` lines 132–134 show a delete link only when the listed row's `status` is `New`. The link includes `lid` and a broken `ee` fragment. The action does not test `status`.

## 4. Findings

| ID | What the source shows | What it does not establish | Proposal | Inference, labeled as such |
| --- | --- | --- | --- | --- |
| LCH-01 | The screens list and prefill `emp_id_no` from the session employee. The module name is required | A route filter beyond the action | **KEEP** that view filter and the module test | None |
| LCH-02 | Monthly insert and daily insert store the posted `emp_id`. The monthly update changes `lunch_management` by posted `aid` with no employee comparison | Whether a browser submits only the hidden session value. The action does not check it | **REDESIGN** the missing comparison on the insert and the update | A different posted number or row id could be written. Not executed |
| LCH-03 | Both deletes load the session employee and then delete by `lid` without comparing the row. `actionDelete` also ignores status. The daily view hides the link unless status is `New` | A caller for `actionDeletea`. None was found in the lunch views | **REDESIGN** the missing row comparison. **UNRESOLVED** for a status rule and for who calls `deletea` | The view condition is not the action condition. Not executed |
| LCH-04 | Monthly or daily behavior follows the first settings row on the session connection | A company-id rule inside that table. The query has none | **CONFIGURE** the type switch. Do not treat the first row as a signed product policy | None |

Nothing in this batch is **REMOVE** or **ENHANCE**. The view filter is already present. The write and delete actions do not have a comparison to tighten. No audit insert was found. No exploit was demonstrated.

## 5. Runtime

No lunch row was inserted, updated, or deleted. No mail was sent. The mail statements are inside a comment. The findings are source-traced.

## 6. Next batch

ESS `MobilebillmanagementController`, limited to whether its reads and deletes stay on the session employee. Do not open provident fund. Do not repeat AUD-01 through AUD-15.

## 7. Statement

No application code, schema, test, configuration, database, or Git state was changed. The legacy tree was not modified. The active control center gained one completed row. No gate was signed.

AUD-15 SOURCE TRACE COMPLETE FOR THIS BATCH
