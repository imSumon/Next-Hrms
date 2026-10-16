# Universal HRMS — AUD-10 ESS Payroll Identity and One Admin PIMS Update

Date: 2026-10-16

Status: **SOURCE TRACE COMPLETE FOR THIS BATCH.** No request was sent. Source inspection is not runtime verification. Classifications are proposals, not approved requirements. No decision was signed. No company is migration-approved.

AUD-08 named the unread ESS payroll actions. Provident-fund actions from that list were not opened, because SCOPE-01 still defers PF/GF. AUD-03 examined appadmin controllers and did not examine `backend\modules\pims\controllers\EmployeesController.php`. AUD-01 through AUD-09 were not repeated. The catalog denominator stays 25 modules, 101 submodules, and 416 controller records.

## 1. Part A — ESS payroll actions

Controller: `frontend\modules\ess\controllers\PayrollController.php`. Each action below requires the session module list to contain `payroll` and requires a session user level. The employee passed to the view is `sesdata('employee_id')`. A posted employee id was not read in these actions. Month or year, where accepted, does not select the employee.

| Action | Lines | What the source shows | What it does not show |
| --- | --- | --- | --- |
| `actionNoc` | 456–486 | Renders `ottonoc` when the session database name is `otto`, and `astranoc` when it is `astra`. The view receives the session employee and the current month and year | A view for any other database name. `$pagei` is set only in those two branches. No calling screen was opened |
| `actionSalcer` | 497–531 | Renders `ottosalcer` for `otto`, `proedgesalcer` for `proedge`, and `astrasalcer` otherwise. Same session employee | Whether those views query another employee. The views were not opened |
| `actionTaxslip` | 546–590 | Posted `month` and `year` are accepted. If absent, the current month and year are used. The view `taxslip` receives the session employee. In that view, line 22 sets `$emp` from `$empdata['id']` | A full tax calculation. The rest of the view was not audited |
| `actionItdeduction` | 600–633 | Not on the AUD-08 name list. It sits between the tax action and the PF actions and uses the same session employee. Posted `year` only | The view body |

Not opened, by the SCOPE-01 exclusion: `actionPfdeduction` (line 643) and `actionPfstatement` (line 684). Their employee binding is **UNRESOLVED**. Still unread, and not part of this batch: `actionSalarycard` and `actionTax108`.

No audit-table write was found in the actions above.

## 2. Part B — Admin PIMS employee update

Selected action: `EmployeesController::actionUpdate` in `backend\modules\pims\controllers\EmployeesController.php`, lines 285–366.

Why this one: AUD-03's inspected-file list is the appadmin controllers, `SiteController`, and `ApuadminComponent`. It does not include this PIMS controller. This is the primary employee update, not a second update such as `actionDupdate` or `actionUpdateotherinfo`.

| Step | Lines | Source fact |
| --- | --- | --- |
| Module check | 287 and 565 | `moduleAccess()` runs. AUD-03 already found that this check is a module grant, not an action grant and not an employee-ownership test |
| Database | 288–289 and 566–567 | `EmployeesSearch::setDb` uses `sesdata('companydbname')`. `EmployeesSearch` extends `Employees`, and `setDb` writes `Employees::$_db` through `self::` |
| Target row | 292 and 570 | `Employees::findOne($id)` on that connection. The id is the route id. The lines do not compare `emp_unit` or company id |
| Write | 308–325 and 349–356 | The posted model is saved with `save(false)`. Ajax also writes `photo` from `$_POST['photo']`, and for database name `cma` writes `weekly_holiday`. The non-ajax branch writes `photo` and does not contain the `cma` holiday lines |
| History | — | No audit insert was found in this action |

`save(false)` skips model validation. The model rules were not read.

## 3. Findings

| ID | What the source shows | Proposal | Inference, labeled as such | Runtime |
| --- | --- | --- | --- | --- |
| PAY-01 | NOC, salary certificate, tax slip, and income-tax deduction pages are given the session employee. Posted month or year does not replace that id | **KEEP** that employee binding | A view could still query another id. The tax view's `$emp` is the session employee's id. The certificate views were not opened, so that inference stays open for them | Not run |
| PAY-02 | Certificate view names follow `otto`, `astra`, and `proedge` | **CONFIGURE** the layout. Do not copy the alias test as the product rule | None beyond the branch list | Not run |
| PAY-03 | PF deduction and PF statement were not read | **UNRESOLVED** | None | Not run |
| PIMS-01 | Update loads any id on the session company connection and saves the posted employee, including pay fields that exist on the model | **REDESIGN** the missing row-level company check and the skipped validation | Inference: an id for another unit in the same database could be saved. The source does not prove a second unit is present in that database | Not run |
| PIMS-02 | `photo` is written from the post. For `cma` only, `weekly_holiday` is written on the ajax path. Architecture problem 4 already says weekend data has been stored in `photo` for some companies | **CONFIGURE** weekend storage. **REDESIGN** using `photo` as a weekday list | The action does not say that this `photo` post is a weekday list. That meaning stays the earlier architecture finding, not a new proof | Not run |

Nothing in this batch is **REMOVE** or **ENHANCE**. The ESS reads already bind the employee. The PIMS write does not have a narrower check to improve. It lacks a company-unit comparison.

## 4. What remains unverified

- Whether `getEmpInfo` can return a different person than `sesdata('employee_id')`. AUD-08 left that resolver unread. This batch did not open it.
- The bodies of `ottonoc`, `astranoc`, `ottosalcer`, `proedgesalcer`, and `astrasalcer`.
- `actionSalarycard`, `actionTax108`, and the two PF actions.
- Whether `moduleAccess()` rejects a user with no payroll or PIMS module at runtime. The call is present. The component was not re-read.
- No exploit was demonstrated.

## 5. Next batch

ESS controllers still unopened after AUD-08 and AUD-09, starting with `DashboardController` and `OtmanagementController` for the same session-employee question. Do not open PF. Do not expand PIMS beyond this one update unless a later batch is named. Do not repeat AUD-01 through AUD-10.

## 6. Statement

No application code, schema, test, configuration, database, or Git state was changed. The legacy tree was not modified. The active control center gained one completed row. No gate was signed.

AUD-10 SOURCE TRACE COMPLETE FOR THIS BATCH
