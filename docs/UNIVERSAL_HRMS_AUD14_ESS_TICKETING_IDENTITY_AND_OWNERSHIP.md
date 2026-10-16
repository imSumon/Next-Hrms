# Universal HRMS — AUD-14 ESS Ticketing Identity and Ownership

Date: 2026-10-16

Status: **SOURCE TRACE COMPLETE FOR THIS BATCH.** No request was sent. Source inspection is not runtime verification. Classifications are proposals, not approved requirements. No decision was signed. No company is migration-approved.

Controller: `frontend\modules\ess\controllers\TicketingController.php`. It contains two actions, `actionIndex` and `actionDeleteleave`. AUD-01 through AUD-13 were not repeated. Provident fund was not opened. The catalog denominator stays 25 modules, 101 submodules, and 416 controller records.

## 1. `actionIndex`

Lines 19–60.

The action requires the session module list to contain `leave`, then a user level. It does not test a module name `ticketing`. It creates `app\modules\ess\models\TicketFromEss`, sets `captcha` to a random number, and on post calls `load` and `save(false)`. The controller does not assign `emp_id`, `emp_id_no`, or `company_old_id` before that save. After save it renders `view` with `getEmpInfo(sesdata('employee_id'))` and the new model id. The session employee is passed to the view. It is not written by these lines.

`frontend\modules\ess\models\TicketFromEss.php` is not in the ESS models folder. `frontend\modules\ess\views\ticketing` is not present. `TicketFromEssSearch` is imported at line 7 and is not used.

A class named `TicketFromEss` exists under `backend\modules\00backup00\ticketing\models\TicketFromEss.php`. Its namespace is `app\modules\ticketing\models`, and its table is `ticket_from_ess`, with `emp_id` among its attributes. That is not the class this controller imports. It was not used as the behavior of `actionIndex`.

No ticket list, ticket view-by-id, or ticket update action is in this controller. No audit insert was found.

## 2. `actionDeleteleave`

Lines 64–159.

| Step | Lines | What the source shows |
| --- | --- | --- |
| Gate | 66–73 | Module list must contain `leave`. A user level is required. Otherwise the action redirects |
| Target | 77–80 | `session user.db`. `SELECT * FROM hrm_leave_emp_app where id=` plus `$_REQUEST['lid']` |
| Ownership | 90–92 | `DELETE` runs only when `emp_id` on that row equals `sesdata('employee_id')`. A different employee redirects to `ess/leave` |
| Status | 90–92 | No status, date, or approver condition in the delete |
| Related rows and files | 92 | The delete is one table. No attachment path and no second table are in the action |
| Mail | 98–144 | After delete, it reads the session employee and `employees` where `id` equals the row's `boss_id` and `field_name='Email ID'`. The message uses `$llv['leave_name']`. `$llv` is not assigned in this action. The blind-copy address differs for database name `proedge` |
| Ticket table | — | `ticket_from_ess` is not named |

The action does not delete a ticket. It deletes a day-leave application row. Whether every status may be deleted is **UNRESOLVED**. The ownership comparison is present. No audit insert was found.

## 3. Findings

| ID | What the source shows | What it does not establish | Proposal | Inference, labeled as such |
| --- | --- | --- | --- | --- |
| TKT-01 | The controller saves the posted ticket model with `save(false)` and does not set the session employee on that model | The ESS model and form. Both files are absent, so a missing model event could still assign `emp_id` | **UNRESOLVED** for the stored ticket employee. The controller lines themselves do not bind it | A posted employee id could be stored. That was not executed, and the form was not found |
| TKT-02 | Delete removes `hrm_leave_emp_app` by request id only after `emp_id` matches the session employee | A status rule. None is coded | **KEEP** the ownership comparison. **UNRESOLVED** for which statuses may be deleted | None |
| TKT-03 | Both actions use the module string `leave`. The delete then redirects to `ess/leave` | A separate ticketing grant | **UNRESOLVED** as a module boundary. Do not treat `leave` as proof of a ticketing permission | None |

Nothing in this batch is **REMOVE**, **REDESIGN**, **ENHANCE**, or **CONFIGURE**. The ticket write cannot be classified further while its model is missing. The leave delete already has the ownership comparison. No exploit was demonstrated.

## 4. Runtime

No ticket was created. No leave row was deleted. No mail was sent. The findings are source-traced.

## 5. Next batch

ESS `LunchmanagementController`, limited to whether `actionIndex`, `actionDelete`, and `actionDeletea` stay on the session employee. Do not open provident fund. Do not repeat AUD-01 through AUD-14.

## 6. Statement

No application code, schema, test, configuration, database, or Git state was changed. The legacy tree was not modified. The active control center gained one completed row. No gate was signed.

AUD-14 SOURCE TRACE COMPLETE FOR THIS BATCH
