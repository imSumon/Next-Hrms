# Universal HRMS — AUD-12 ESS AdminController Identity and Payroll Reads

Date: 2026-10-16

Status: **SOURCE TRACE COMPLETE FOR THIS BATCH.** No request was sent. Source inspection is not runtime verification. Classifications are proposals, not approved requirements. No decision was signed. No company is migration-approved.

The four named actions are in `frontend\modules\ess\controllers\AdminController.php`. AUD-08's ESS `PayrollController` payslip was not reopened. That action uses the session employee. These admin actions do not. Provident fund was not opened. The catalog denominator stays 25 modules, 101 submodules, and 416 controller records.

`getEmpInfo` was read in AUD-11. It returns `SELECT * FROM employees` for the id it is given, on `session user.db`, and does not compare that id with the session employee. This batch uses that result. It does not re-read the helper.

## 1. Actions

None of the four actions tests a module name. Each requires `sesdata('user_level')` to equal `clientAdmin`. Otherwise it redirects to `/`. No `save`, `INSERT`, or `UPDATE` was found in these actions or in the views opened below.

| Action | Lines | Employee | Database | What is returned |
| --- | --- | --- | --- | --- |
| `actionProfile` | 89–101 | `getEmpInfo($_REQUEST['id'])` | Session `user.db` inside `getEmpInfo`. Company label is `getCom(sesdata('company_id'))` | `views\admin\profile.php` prints the returned row, including `basic_salary`, `gross_salary`, `bank_account`, TIN, and passport number. Line 1 loads `emp_fields` and the rest of the 64-line file does not use it |
| `actionPayslip` | 103–125 | `getEmpInfo($_REQUEST['id'])`. Posted `month` and `year` select the period. If absent, the current month and year are used | `views\admin\payslip.php` line 130 uses `session user.db` | Line 22 sets `$emp` from `$empdata['id']` and `$cid` from `$empdata['emp_unit']`. Line 131 reads `salary_head` for that unit. Lines 136 and 167 read `employee_salary` for `$emp`, the head, and the posted period |
| `actionSalarycard` | 157–179 | Same request id and posted month and year | Same session connection at `views\admin\salarycard.php` line 133 | Lines 21–22 and 134–139 match the payslip view: `salary_head` by the row's `emp_unit`, then `employee_salary` for that employee and period |
| `actionSalcer` | 57–87 | The header calls `getEmpInfo` with the session employee. The grid uses `EmployeesSearch::search` | `EmployeesSearch::setDb` to `Yii::$app->$dbname`, where `$dbname` is `session user.dbname` | The grid is the employee list. It does not render a certificate |

`EmployeesSearch::search`, lines 46–83, filters `emp_unit` to `sesdata('company_id')` and `disconrinue` to 0. The controller's own company `where` is commented out at lines 46 and 77. The model filter is the one that runs. `boss` and `g_father` are filter fields, not forced to the session employee.

`views\admin\salcer.php` lines 50–59 show `username.password` in the grid and link each row to `/hrms/ess/admin/salcerdetails?id=` plus that row's id.

## 2. Certificate path linked from `actionSalcer`

`actionSalcerdetails`, lines 126–156, is the action that link calls. It is in the same controller. It was opened because `actionSalcer` does not itself generate the certificate.

It requires `clientAdmin`. It reads posted month and year, then does not pass a branch that uses them for every alias. For session database name `mrb` it renders `salcermrb`. For `ceva` it renders `salcerceva`. Both receive `getEmpInfo($_REQUEST['id'])`. For any other database name, the lines read do not render a view.

Both templates set `$emp` from `$empdata['id']` and query `employee_salary` on `session user.db`. Each ignores the posted month and year and takes the latest `employee_salary` row for that employee (`order by id DESC limit 1`), then reads that month. `salcermrb.php` lines 32–38 also test salary-head ids 506, 507, and 508. No write was found.

## 3. Findings

| ID | What the source shows | What it does not establish | Proposal | Inference, labeled as such |
| --- | --- | --- | --- | --- |
| ADM-01 | A user level other than `clientAdmin` is redirected. There is no module-name test in these actions | Whether another filter rejects the route before the action | **KEEP** the level test that is present. It is not a module grant | None |
| ADM-02 | The salary-certificate list uses `search()`, which limits `emp_unit` to the session company | Whether `username.password` is stored as a hash. The grid prints the attribute | **KEEP** the list's company filter. **REDESIGN** printing the password attribute | The list can show another employee's stored password value to a `clientAdmin` user. That screen was not requested |
| ADM-03 | Profile, payslip, and salary card load `$_REQUEST['id']` and do not compare `emp_unit` with the session company. Payslip and salary card then read `employee_salary` for that id. The payslip form posts back to `payslip?id=` of the already loaded row | A screen that only offers ids from the filtered list. The list filter is not repeated in the detail action | **REDESIGN** the missing company check on these three reads | An id that exists on the session database can be loaded even when it would not appear in `search()`. Not executed |
| ADM-04 | Certificate templates exist for `mrb` and `ceva` only. They use the request id and the latest salary row | A template for company ids 24, 20, or 10. No branch names those ids | **CONFIGURE** the two templates. **UNRESOLVED** for every other alias, because the action does not render one | None |
| ADM-05 | Profile loads `emp_fields` and does not consult it before printing salary, bank, TIN, and passport | Whether that session value was meant to hide fields | **UNRESOLVED** | None |

Nothing in this batch is **REMOVE** or **ENHANCE**. These actions read. They do not update payroll.

The company name on the profile is the session company from `getCom`. The salary figures are the requested employee's row. Those two sources are not the same variable.

## 4. Runtime

No admin page was requested. No payslip was generated. The password column was not displayed in a browser. Every finding above is source-traced.

## 5. Next batch

ESS `HourlyleaveController`, limited to whether leave requests stay on the session employee. Do not open provident fund. Do not expand PIMS. Do not repeat AUD-01 through AUD-12.

## 6. Statement

No application code, schema, test, configuration, database, or Git state was changed. The legacy tree was not modified. The active control center gained one completed row. No gate was signed.

AUD-12 SOURCE TRACE COMPLETE FOR THIS BATCH
