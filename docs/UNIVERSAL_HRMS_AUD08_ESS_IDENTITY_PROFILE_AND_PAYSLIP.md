# Universal HRMS — AUD-08 ESS Identity on Profile, Files, and Payslip

Date: 2026-10-16

Status: **SOURCE TRACE COMPLETE FOR THIS BATCH.** This is one Admin/ESS audit batch. It does not finish the 25-module, 101-submodule, 416-controller catalog. It does not sign a decision or authorize implementation. No company is migration-approved. MySQL was not started.

Classifications are proposals for the future product. They are not approved requirements.

## 1. Coverage reconciliation before this batch

Denominator, unchanged from Steps 21A, 21B, and 21D:

| Item | Count | Status before this batch |
| --- | ---: | --- |
| Modules | 25 | 5 partial: organizationsettings, pims, payroll, leave, attendance. The other 20 are not functionally analyzed at catalog grain |
| Submodules | 101 | 8 partial. 91 inventory only. 2 unknown parent. 0 functionally analyzed |
| Controller records | 416 | 397 link checks pass. 19 fail. Five rows unresolved: 122, 123, 205, 213, 214. Live comparison not done |

AUD-01 through AUD-07 traced named workflows. They did not reclassify those 101 rows. This batch does not reclassify them either.

Already covered, and not repeated:

| Audit | What it closed |
| --- | --- |
| AUD-01 | Hourly leave does not write attendance or payroll |
| AUD-02 | Day leave and `actionApproveatt` |
| AUD-03 | Appadmin server authorization. Non-appadmin actions were left unread |
| AUD-04 | In-repo punch writers. External writer unknown |
| AUD-05 | Atlas and CEVA processing shape |
| AUD-06 | Joiner and leaver month code paths |
| AUD-07 | Approval callers and salary reads for company ids 24, 20, and 10 |

This batch is the ESS gap AUD-03 named only as "not read": whether an ESS session is bound to one employee, on the profile, personal-file, and payslip paths.

ESS controllers not opened in this batch: `AdminController`, `Assessment360degreeController`, both Atlas bills controllers, `AttendanceController`, `BillmanagementController`, `DashboardController`, `DocumentbyempController`, `DocumentController`, `DocumentsController`, `EmployeeprobationersprogressController`, `GfmanagementController`, `HourlyleaveController`, `LeaveController`, `LunchmanagementController`, `MobilebillmanagementController`, `MultiraterassessmentController`, `OtmanagementController`, `PmsController`, `RecruitmentController`, `TicketingController`. Attendance and leave stay with AUD-01 and AUD-02. PF and GF stay in SCOPE-01 until an owner adds them. Payroll actions other than index, payslip, and the employee binding on print payslip were not read.

Runtime status for this batch: source traced. Not runtime verified.

## 2. Actions read

| Action | File | Lines | Employee used for the record |
| --- | --- | --- | --- |
| `actionProfile` | `frontend\modules\ess\controllers\DefaultController.php` | 152–178 | `sesdata('employee_id')` passed to `getEmpInfo`. Requires a session user level. No module-name check on this action |
| `actionIndex` | `frontend\modules\ess\controllers\PersonalfilesController.php` | 23–52 | Same session employee. Requires module name `personalfiles` and a user level. The view `personalfiles\index.php` has no request employee id |
| `actionIndex` and `actionPayslip` | `frontend\modules\ess\controllers\PayrollController.php` | 22–90 and 91–237 | Session employee. Requires module name `payroll` and a user level. Month and year may come from the post. The employee id in the salary lookup is the session id |
| `actionPrintpayslip` | same file | 250–271 | Employee lookup uses the session id. Month and year come from the request |
| Payslip amounts | `frontend\modules\ess\views\payroll\payslip.php` | 21–22 and 245 | `$emp = $empdata['id']`. `empdata` is the controller's session employee. Salary rows are read for that id. This view was not shown to accept another employee's id |
| `actionChangepassword` | `DefaultController.php` | 226–247 | Updates `user` where `id` is `sesdata('user_id')`. The posted old password is compared in SQL as stored text. The new password is written as posted |
| `actionCloudpunch` | `DefaultController.php` | 204–211 | Updates `location_access_data.remark` where `id` is the posted `entryid`. The lines read do not compare that id with the session employee |
| `actionFlagrise` | `DefaultController.php` | 309–320 | Updates `hrm_pms_emp_po.fl` where `id` is the posted `bid`. The lines read do not compare that id with the session employee |

A module name in the session is not an action check. AUD-03 already established that pattern for admin. These ESS actions confirm it for this batch. A hidden button was not treated as authorization.

## 3. Findings

| ID | Area | Behavior | Class | Owner input |
| --- | --- | --- | --- | --- |
| ESS-ID-01 | Profile, personal files, payslip read, print payslip employee lookup | The record is loaded from the session employee, not from a posted employee id. Month and year on the payslip may be posted | **KEEP** the rule that an ESS read of that person's file or payslip uses the session employee. **ENHANCE** the payslip so a posted month cannot be mistaken for an employee switch. The enhancement is a proposal | No, for the binding. The payslip layout is a separate question |
| ESS-ID-02 | Payslip view choice | For `vtlnms`, `isml`, `vsl`, and `palstorew`, management level names `NMS` or `FW` select a Bangla view named from the database alias. Other names in that branch use `payslip`. `paldtwo` uses its own view. The default branch uses `payslip` | **CONFIGURE** the layout. Do not copy the alias test as product code. Company ids 24, 20, and 10 were not mapped to these aliases | No new rule. Which layouts a company needs is open |
| ESS-ID-03 | Password change | The session user id is the row updated. The password is compared and stored as posted text | **REDESIGN** the credential check. Do not copy clear-text password comparison. The session binding of the user id can stay | No business policy. This is a security defect |
| ESS-ID-04 | Cloud-punch remark | Any posted `entryid` is updated on the default connection `Yii::$app->db`, after only a user-level check | **REDESIGN**. The write does not show an employee or company check. Whether the screen lists only that employee's rows was not read | No, unless the business still needs employee remarks on location rows |
| ESS-ID-05 | PMS flag | Any posted `bid` updates `hrm_pms_emp_po` on the session connection | **REDESIGN**. The id is not tied to the session employee in the lines read. PMS remains outside SCOPE-01 until an owner adds it | Yes, only if PMS is added to the release. Not required to reject this write pattern |

No finding is classified **REMOVE**. The pages were not shown to be unused.

## 4. Security and data integrity

Profile, personal files, and the studied payslip reads do not accept another employee's id in the controller lines read. That is the applicable ESS rule for those screens.

`actionCloudpunch` and `actionFlagrise` do accept another row's id. That is a cross-record write. It is not proof that ESS as a whole is unbound. It is proof that those two actions are unbound.

`actionChangepassword` does not change another user. It does store the password in a form that must not be copied.

Company scope for the payslip is the session connection `user.db`. This batch did not find a second check that the employee row's company matches that connection beyond `getEmpInfo` on the session id. How `getEmpInfo` resolves the id was not opened. That resolution remains **UNRESOLVED**.

## 5. Company variation

The variation found here is which payslip view is rendered, driven by database alias and, for four aliases, by management-level name. That is a client presentation difference. It is not a different employee-binding rule. Proedge, Viyellatex, and Atlas session names were not special-cased in `actionPayslip` except by falling through to `payslip` when they are not the named aliases. Whether `proedge`, `vtl`, or `atlas` are those session names in production was not re-checked. AUD-02 already uses those names for attendance. This batch does not remap them.

## 6. Conflicts

None with AUD-01 through AUD-07. AUD-03's statement that non-appadmin actions were not exhaustively read stays true. This batch reads seven ESS actions and does not close that statement.

SEC-CORE-007, one employee in one company, is the requirement these reads already resemble. The requirement is not signed into a new build by this audit. BD-D16, BD-D17, and the access-model brief stay unsigned.

## 7. Next audit batch

1. ESS document controllers (`DocumentController`, `DocumentsController`, `DocumentbyempController`): same identity question, because a document is employee data and this batch did not open them.
2. The remaining `PayrollController` actions (`actionTaxslip`, `actionPfdeduction`, `actionPfstatement`, `actionNoc`, `actionSalcer`): confirm they keep the session employee before any of them is described as safe. PF content stays deferred under SCOPE-01. The identity check is the audit question, not a PF design.
3. One admin PIMS employee update: AUD-03 did not read it. The question is whether an admin action can save an employee outside the session company.

Do not repeat AUD-01 through AUD-08 unless one of those files contradicts this batch.

## 8. Statement

No application code, schema, test, configuration, database, or Git state was changed. The legacy tree was not modified. The control center received one completed-row for this batch. No gate was signed.

AUD-08 SOURCE TRACE COMPLETE FOR THIS BATCH
