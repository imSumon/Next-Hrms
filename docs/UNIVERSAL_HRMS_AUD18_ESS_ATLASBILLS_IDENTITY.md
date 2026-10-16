# Universal HRMS — AUD-18 ESS Atlas Bills Reads and Delete

Date: 2026-10-16

Status: **SOURCE TRACE COMPLETE FOR THIS BATCH.** No request was sent. No bill was deleted. Source inspection is not runtime verification. Classifications are proposals, not approved requirements. No decision was signed. No company is migration-approved.

Controller: `frontend\modules\ess\controllers\AtlasbillsController.php`. This batch is the list, detail, and delete path. The three create actions, `actionAtlasfuelbillnew`, `actionAtlasexpensesbillnew`, and `actionAtlaslocaltravelnew`, were not audited. Provident fund was not opened. AUD-01 through AUD-17 were not repeated. The catalog denominator stays 25 modules, 101 submodules, and 416 controller records.

## 1. Lists and details

Each action below requires the session module list to contain `atlasbills` and a user level. Queries use `session user.db`. The controller does not pass an employee into the view. The views read `sesdata('employee_id')` themselves.

| Action | Lines | What the source shows |
| --- | --- | --- |
| `actionIndex` | 18–58 | Renders `index` or `index_apps` with the posted or current month and year. `views\atlasbills\index.php` lines 3 and 29–31 list `atlas_fuel_bill`, `atlas_expenses_bill`, and `atlas_local_travel_bill` where `eid` equals the session employee |
| `actionAtlasbillapprovedindex` | 59 | Same module and user-level opening as the index. The view body was not read |
| `actionAtlasbillacc` | 101–135 | Renders the accounts view. No employee is selected in the action |
| `actionAtlasbillaccpaid` | 136 | Same shape as the accounts render. The view body was not read |
| `actionAtlasbillapprove` | 178 | Renders the approval index. The view body was not read |
| `actionAtlasfuelbillview` | 219–252 | Renders `atlasFuelBillView` with no id argument |
| `actionAtlasexpensesbillview` | 568 | Renders the expenses detail. The action body matches the fuel view's render shape |
| `actionAtlaslocaltravelview` | 602 | Renders the travel detail. The action body matches that render shape |

`views\atlasbills\atlasFuelBillView.php` lines 23–32, and the same lines in `atlasExpensesBillView.php` and `atlasLocalTravelView.php`, load the bill with `where id=` plus `$_REQUEST['id']`. The next test is `if($status='Approved' or $status='sApproved' or $status='Paid')`. That uses `=` . The `elseif` compares `$bill['eid']` with `$emp`. The lines read set `$eid` from the session and do not set `$emp`.

The index view lines 131–133 show Delete only when status is `New`. The same link shape appears for expenses at line 186 and travel at line 236.

## 2. Delete

`actionBilldel`, lines 1172–1258.

The request supplies `id` and `wh`. `wh` selects one table: `AtlasFuelBill` selects `atlas_fuel_bill`, `AtlasExpensesBill` selects `atlas_expenses_bill`, and `AtlasLocalTravelBill` selects `atlas_local_travel_bill`. The select requires `eid` equal to `sesdata('employee_id')` and `id` equal to the request id. If no row matches, the action prints `DONT DO THIS` and stops. If the row's status is `New`, it deletes that id. No other status is deleted. No second table and no file path are in the action. No audit insert was found.

The delete statement itself names only `id`. The ownership and status tests run before it.

## 3. Approval, only as far as the fuel branch

`actionAtlasfuelbillapproveview` lines 266–277 load `AtlasFuelBill` by `$_REQUEST['id']` and, when `ac` is `a`, set status to `Approved` and call `save()`. The lines read do not compare an approver with the session employee before that save. Later lines call `Yii::app()` and `Employees::model()`. Those calls are not the Yii2 form used by the rest of this controller. They were not executed.

The same `ac` and `id` opening appears in the other approve and accounts actions. Those bodies were not line-read. They are **UNRESOLVED**.

## 4. Findings

| ID | What the source shows | What it does not establish | Proposal | Inference, labeled as such |
| --- | --- | --- | --- | --- |
| ATL-01 | The index lists three bill tables for the session employee on the session connection | The approved-index and accounts views. Their bodies were not read | **KEEP** the index filter | None |
| ATL-02 | Delete selects by session `eid` and deletes only status `New` | A child row or attachment. None is named | **KEEP** those two tests | None |
| ATL-03 | Three detail views load by id. The status test assigns `Approved`. The employee comparison is the `elseif` and uses `$emp` | A later assignment of `$emp`. None appears before that line | **REDESIGN** the detail test | The assignment makes the first branch the one that runs for a non-empty status string. That was not executed |
| ATL-04 | The fuel approval branch saves a status for the requested id | The other approval and payment actions | **REDESIGN** the branch that was read. **UNRESOLVED** for the others | None |

Nothing in this batch is **REMOVE**. The Atlas module name is not a signed company rule. Whether these tables belong only to company id 10 was not established. That map stays **UNRESOLVED**. No exploit was demonstrated.

## 5. Runtime

No list was opened. No bill was deleted or approved. The findings are source-traced.

## 6. Statement

No application code, schema, test, configuration, database, or Git state was changed. The legacy tree was not modified. The identity register and the active control center gained this batch. No gate was signed.

AUD-18 SOURCE TRACE COMPLETE FOR THIS BATCH
