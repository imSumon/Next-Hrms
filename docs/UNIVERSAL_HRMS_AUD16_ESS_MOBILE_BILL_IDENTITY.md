# Universal HRMS — AUD-16 ESS Mobile Bill Identity

Date: 2026-10-16

Status: **SOURCE TRACE COMPLETE FOR THIS BATCH.** No request was sent. Source inspection is not runtime verification. Classifications are proposals, not approved requirements. No decision was signed. No company is migration-approved.

Controller: `frontend\modules\ess\controllers\MobilebillmanagementController.php`. This batch is the read and delete path: `actionIndex`, `actionPrint`, and `actionDeletea`. `actionBillapplication` (line 247), `actionBillapplicationsec` (line 374), and `actionBillreject` (line 500) exist and were not traced. Provident fund was not opened. The catalog denominator stays 25 modules, 101 submodules, and 416 controller records.

## 1. Actions

Each traced action requires the session module list to contain `mobilebillmanagement` and a user level. The connection is `session user.db`.

| Action | Lines | What the source shows |
| --- | --- | --- |
| `actionIndex` | 15–141 | On post, inserts `mobile_bill` from posted `emp_id`, approvers, `details`, `applied_date`, `status`, `current_limit`, and `required_limit`. It then sends mail. The render passes `getEmpInfo(sesdata('employee_id'))`. The insert does not replace `emp_id` with that session value |
| `actionDeletea` | 144–180 | Loads `sesdata('employee_id')` and does not use it. Loads `mobile_bill` by `$_REQUEST['lid']` and deletes that id. No status test |
| `actionPrint` | 184–245 | Loads the session employee for the PDF header and passes `$_REQUEST['lid']` to `views\mobilebillmanagement\print.php`. Line 199 assigns `$emp_id` and the following lines do not read it |

`views\mobilebillmanagement\index.php` line 65 puts the session employee's `emp_id_no` in a hidden `emp_id` field. Line 151 lists `mobile_bill` where `emp_id` equals that number, limited to 10 rows. Lines 187–189 show Print for every listed row and Delete only when `status` is 0. The print view line 69 loads `mobile_bill` by `lid` and does not compare `emp_id` with the session employee. The header still prints the session employee's number and email.

No second table and no attachment path were found in the delete. No audit insert was found. The mail send in `actionIndex` is not inside a comment.

## 2. Findings

| ID | What the source shows | What it does not establish | Proposal | Inference, labeled as such |
| --- | --- | --- | --- | --- |
| MBL-01 | The index view lists and prefills the session employee number. The module name is required | A route filter beyond the action | **KEEP** that view filter and the module test | None |
| MBL-02 | The insert stores the posted employee number, status, and amounts | Whether a browser submits only the hidden session value | **REDESIGN** the missing comparison. The view filter is not repeated in the insert | A different posted number could be stored, and the posted status could skip `0`. Not executed |
| MBL-03 | Delete removes `mobile_bill` by `lid`. The view shows that link only for status 0. The action does not test status or `emp_id` | A caller outside this view | **REDESIGN** the missing row comparison. The view condition is not the action condition | A request with another id could delete a row that the screen does not link. Not executed |
| MBL-04 | Print loads the bill by `lid` and the header from the session employee | Whether those two are the same person. The view does not compare them | **REDESIGN** the missing comparison on the print query | The PDF can show one employee's header and another row's bill. Not executed |

Nothing in this batch is **REMOVE**, **ENHANCE**, or **CONFIGURE**. The approval actions remain **UNRESOLVED** because they were not opened. No exploit was demonstrated.

## 3. Visual evidence

No mobile-bill page was opened. The view's status condition and the action's missing status test are a source mismatch, not a screenshot mismatch. The visual register records this page as source-traced and not captured.

## 4. Runtime

No bill was inserted, printed, deleted, or mailed. The findings are source-traced.

## 5. Next source batch

ESS `BillmanagementController`, limited to its reads and deletes. Do not open the mobile-bill approval actions in that batch unless it is named. Do not open provident fund. Do not repeat AUD-01 through AUD-16.

## 6. Statement

No application code, schema, test, configuration, database, or Git state was changed. The legacy tree was not modified. The active control center gained two completed rows: this source trace, and the visual-evidence register. No gate was signed.

AUD-16 SOURCE TRACE COMPLETE FOR THIS BATCH
