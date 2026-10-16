# Universal HRMS — ESS Read-Identity Coverage Matrix

Date: 2026-10-16

Status: **COVERAGE MATRIX FOR THIS BATCH.** Rows are proposals. They are not approved requirements and not signed gates. Source inspection is not runtime verification. No request was sent. No company is migration-approved.

This file replaces the one-controller audit sequence. It does not replace AUD-08 through AUD-20. The identity risk register remains the finding index.

## 1. Counting method

The Step 21 catalog counts 25 modules, 101 submodules, and 416 controller records. It does not name actions. An ESS read-action total cannot be taken from that catalog.

This batch counted `function action` declarations in `frontend\modules\ess\controllers`, one file per controller, backup folders excluded:

| Set | Controllers | Declarations |
| --- | --- | --- |
| ESS controller files | 24 | 295 |
| Deferred and excluded: `GfmanagementController` 5, `PmsController` 68, `RecruitmentController` 3, `PfmanagementController` 9 | 4 | 85 |
| Declarations left after that exclusion | 20 | 210 |

The 210 figure is every remaining declaration. It is not a count of read actions. This matrix did not open each of the 210 and did not split them into read and write.

Two other counts are used below, and they are not added together:

- Prior identity groups in section 3. Each group is a reused AUD finding. The actions inside a group were not re-opened and were not re-counted one by one.
- Twenty read actions opened in this batch, section 4. A declaration is one action even when it renders more than one view.

## 2. Controller classification

| Controller | Declarations | Class | Identity evidence |
| --- | --- | --- | --- |
| `PersonalfilesController` | 1 | Already traced for the self read | AUD-08. Session employee |
| `DocumentController`, `DocumentbyempController`, `DocumentsController` | 5 + 11 + 1 | Already traced for the named reads | AUD-09. Lists can filter. Id reads do not |
| `DashboardController` | 28 | Already traced for index and the named detail reads | AUD-11. Not every action in the file was a read audit |
| `OtmanagementController` | 10 | Print read traced. Writes were a separate finding | AUD-11 |
| `PayrollController` | 11 | Certificate and tax reads traced. Salary card and tax 108 opened here. Two PF actions stay deferred | AUD-10 and section 4 |
| `AdminController` | 8 | Profile, payslip, salary card, and certificate traced. Service book opened here | AUD-12 and section 4 |
| `HourlyleaveController` | 13 | Index read traced. Approval is a write | AUD-13 |
| `TicketingController` | 2 | Partial. The ticket model file was absent | AUD-14 |
| `LunchmanagementController` | 6 | List traced. Insert and delete are writes | AUD-15 |
| `MobilebillmanagementController` | 6 | List and print traced. Two approval screens opened here. `actionBillreject` is a write and is outside the matrix | AUD-16 and section 4 |
| `BillmanagementController` | 8 | List traced. Approve-history view body was not read | AUD-17 |
| `AtlasbillsController` | 23 | Index, delete, and three detail views traced. Approval lists and four detail views opened here. Other accounts and second-approver views were not line-read | AUD-18 and section 4 |
| `EmployeeprobationersprogressController` | 8 | Index list and the review screen traced. Delete and insert are writes | AUD-19 |
| `Assessment360degreeController` | 17 | List and view reads traced. Five submit actions were not | AUD-20 |
| `MultiraterassessmentController` | 10 | Read actions opened here. Three submit actions were not | Section 4 |
| `AttendanceController`, `LeaveController` | 13 + 18 | Workflow traces exist. This matrix did not re-derive an identity row for each action | AUD-01 and AUD-02 |
| `DefaultController` | 11 | Not yet traced | No AUD-08 through AUD-20 identity row |
| `GfmanagementController`, `PmsController`, `RecruitmentController`, `PfmanagementController` | 85 | Deferred | SCOPE-01. Not opened |

`searchorgo` and the document file cabinet remain the named unread slices from earlier audits. They were not re-opened.

## 3. Reused read groups

These rows reuse AUD-08 through AUD-20. The source was not re-read. A self read means the session employee owns the row. A rater, supervisor, or company-admin read is kept when the source compares that role, and marked for redesign when the later screen drops the comparison.

| Group | Role the source shows | List filter | Detail check | Scope | Evidence | Prior ref | Disposition | More inspection |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Profile, personal files, payslip | Self | Session employee | Session employee | Session connection | Verified in AUD-08 | ESS-ID-01 | **KEEP** | No |
| Document lists | Self | Session employee | Not this row | Session connection | Verified in AUD-09 | DOC-01, DOC-02 | **KEEP** | File cabinet still unread |
| Document view and download by id | Self on the list only | Session employee on the list | Requested id | Session connection | Verified in AUD-09 | DOC-03, DOC-05 | **REDESIGN** | No |
| Dashboard index | Self | Session employee | Not this row | Session company on `searchboss` | Verified in AUD-11 | DASH-01 | **KEEP** | `searchorgo` purpose still unread |
| Dashboard detail | The requested employee | Not a substitute for the detail | `getEmpInfo` of the requested id | Session connection, and a request database on the `db` actions | Verified in AUD-11 | DASH-03, DASH-04 | **REDESIGN** | No |
| Overtime print | Supervisor line | Session employee number | Session employee number | Named in AUD-11 | Verified in AUD-11 | OT-01 | **KEEP** | No |
| ESS certificates, tax slip, income-tax deduction | Self | Session employee | Session employee | Session connection. Alias chooses a view | Verified in AUD-10 | PAY-01 | **KEEP** | No |
| Admin employee list | Company admin | `emp_unit` is the session company | Not this row | Session company | Verified in AUD-12 | ADM-02 | **KEEP** the filter | The password column is a separate display finding |
| Admin profile, payslip, salary card, certificate | Company admin | The list is filtered | Requested id, no `emp_unit` test | Session connection | Verified in AUD-12 | ADM-03 | **REDESIGN** | No |
| Hourly-leave index | Self | Session employee | Session employee | Session connection | Verified in AUD-13 | HLV-01 | **KEEP** | No |
| Lunch list | Self | Session employee number | Not this row | Session connection | Verified in AUD-15 | LCH-01 | **KEEP** | No |
| Mobile-bill list | Self | Session employee number | Not this row | Session connection | Verified in AUD-16 | MBL-01 | **KEEP** | No |
| Mobile-bill print | Self on the header only | Session employee on the header | Requested `lid` | Session connection | Verified in AUD-16 | MBL-04 | **REDESIGN** | No |
| Employee bill list | Self | Session `emp_id_no` | Not this row | Session connection | Verified in AUD-17 | BILL-01 | **KEEP** | Approve-history view body unread |
| Atlas own-bill lists | Self | Session `eid` | Not this row | Session `user.db` | Verified in AUD-18 | ATL-01 | **KEEP** | No |
| Atlas own-bill detail views | Self intended by the following branch | Not this row | Load by id. The next test assigns a status | Session `user.db` | Verified in AUD-18 | ATL-03 | **REDESIGN** | No |
| Probation index | Self | Session `emp_id_no` | Not this row | Session `user.db` | Verified in AUD-19 | PRB-01 | **KEEP** | No |
| Probation review screen | Appraiser on the notification list | Appraiser fields on the list | Load by id | Session `user.db` | Verified in AUD-19 | PRB-04 | **REDESIGN** | No |
| 360 own list and `actionPms360viewbo` | Self | Session employee | Session employee | Session `user.db` | Verified in AUD-20 | A360-01 | **KEEP** | No |
| 360 rater lists | Named rater | Role column equals the session employee | Not this row | Session `user.db` | Verified in AUD-20 | A360-02 | **KEEP** | No |
| 360 rater detail views | Named rater on the list only | Role column on the list | `pepe` only | Session `user.db` | Verified in AUD-20 | A360-03 | **REDESIGN** | No |
| Ticket index | Not established | Controller does not set the employee | Model file absent | Not established | Unresolved in AUD-14 | TKT-01 | **UNRESOLVED** | Only if the missing model is found |

PF actions stay **DEFERRED**. They are not in this table.

## 4. Read actions opened in this batch

Connection for these queries is session `user.db` unless a row says otherwise. Module and user-level checks are present on these actions except where a row says otherwise.

| Action | View | Table | List or role filter | Detail check | Scope | Evidence | Disposition | More inspection |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| `MultiraterassessmentController::actionIndex` | `index.php` | `mra_projects`, `mra_emp_project_entry`, `mra_organogram` | Own rows use the session `emp_id_no`. Rater rows use `responder_id` equal to that number and `responder_name` other than `Appraisee` | The links pass the project id | Session `user.db` | Verified for `index.php` | **KEEP** that branch | The same action's `indexcustomer.php` binds `responder_id` to the numeric session id. See the next row |
| `actionIndex` when the level is `customer` | `indexcustomer.php` lines 57–60 | `mra_organogram` | `responder_name` is `Customer`. `responder_id` is `sesdata('employee_id')`, not `emp_id_no` | Project id | Session `user.db` | Partial. The bind is verified. The stored type of `responder_id` is not | **UNRESOLVED** | Do not treat the numeric id and `emp_id_no` as the same value |
| `actionMraappraiseeviewbo` | `mraappraiseeviewbo.php` | `mra_emp_project_entry`, rating tables | Session `emp_id_no` | Same employee number and the requested project | Session `user.db` | Verified | **KEEP** | Self read |
| `actionMraresponderbolist` | `mraresponderbolist.php` line 15 | `mra_organogram` | `responder_id` is the session `emp_id_no`, and the name is not `Appraisee` | Links pass the organogram id | Session `user.db` | Verified | **KEEP** | Rater list |
| `actionMraresponderviewbo` | `mraresponderviewbo.php` lines 21–24 | `mra_organogram`, then the subject's entry and ratings | The list compares the responder | `where id` is `pepe`. The session number is loaded and is not in that `where` | Session `user.db` | Verified. Not executed | **REDESIGN** | Same pattern as AUD-20 rater views |
| `actionMracustomerbolist` | `mracustomerbolist.php` lines 12–15 | `mra_organogram` | `responder_name` is `Customer`. `responder_id` is the numeric session id | Project id | Session `user.db` | Partial | **UNRESOLVED** | Same identifier question as `indexcustomer.php` |
| `actionMracustomerviewbo` | `mracustomerviewbo.php` lines 22–25 | `mra_organogram` by `pepe`, then the subject's ratings | The list compares the responder | No comparison of `responder_id` to the session employee | Session `user.db` | Verified. Not executed | **REDESIGN** | Rater detail |
| `actionMraviewparameters` | `mraviewparameters.php` line 36 | `pms_360_behavioral_objectives` | None. No employee query | Requested year | The action requires module `assessment360degree`, not `multiraterassessment` | Verified query. Role not established | **UNRESOLVED** | Not an employee read |
| `AdminController::actionServicebook` | `servicebook.php` | `getEmpInfo` of `$_REQUEST['id']`, then EPF service-book tables and leave rows for that `emp_id_no` or id | `clientAdmin` only | The requested id. No `emp_unit` test | `sesdata('dbname')` for the service-book models. `getEmpInfo` uses its own session connection | Verified | **REDESIGN** | Company-admin read. The level gate is present. The employee-to-company test is not |
| `actionServicebookprint` | `servicebookprint` | Same `getEmpInfo($_REQUEST['id'])` | `clientAdmin` only | Requested id | Same as the screen | Verified for the action. The print view body was not line-read | **REDESIGN** | Same id path |
| `PayrollController::actionSalarycard` | `salarycard.php` lines 42–43 and 167 | `employee_salary` and, farther down, `pf_audited_balance`, `pf_interest`, and `pf_opening_balance` | Session employee from `getEmpInfo(sesdata('employee_id'))` | `$emp` is that employee's id | Session `user.db`. `salary_head` also filters `company_id` to `emp_unit`. Alias changes which head ids are skipped | Verified | **KEEP** the employee filter | The PF tables are on this card for the same employee. `PfmanagementController` was not opened and stays deferred |
| `PayrollController::actionTax108` | `tax108.php` lines 75 and 209 | `tax_return_info` | List uses the session `emp_id_no` | The form hides that same number | Session `user.db` | Verified for the read | **KEEP** the list | The post inserts the posted `emp_id_no`. That post is a write and is outside this matrix |
| `MobilebillmanagementController::actionBillapplication` | `billapplication.php` line 4 | `mobile_bill` | Module `mobilebillmanagement` and a user level | `where id` is the requested `lid`. No approver comparison | Session `user.db` | Verified for the GET | **REDESIGN** | The post updates that `lid`. The post is a write and is outside this matrix |
| `actionBillapplicationsec` | `billapplicationsec.php` line 4 | `mobile_bill` | Same module and level | `where id` is the requested `lid` | Session `user.db` | Verified for the GET | **REDESIGN** | The post is a write |
| `AtlasbillsController::actionAtlasbillapprove` | `atlasbillapprove.php` lines 68, 105, 153, 191, 239, 286 | `atlas_fuel_bill`, `atlas_expenses_bill`, `atlas_local_travel_bill` | `boss` or `g_father` equals the session employee, plus a status | Not this list | Bills use `user.db`. `Employees::setDb` is `Yii::$app->atlas` | Verified | **KEEP** the manager filter | The hardcoded `atlas` connection is a company-scope question, not a missing employee test |
| `actionAtlasbillapprovedindex` | `atlasbillapprovedindex.php` lines 29–31 | The same three bill tables | `boss` or `g_father` equals the session employee, and status is `Approved`, `sApproved`, or `Paid` | Not this list | Session `user.db` | Verified | **KEEP** | Manager history |
| `actionAtlasbillacc` | `atlasbillacc.php` lines 10–12 | The same three tables | Status `Approved` or `sApproved` only. The session employee is loaded and unused | Links pass the bill id | Session `user.db`. Employee lookup uses `Yii::$app->atlas` | Verified | **REDESIGN** | No accounts-role test was found beyond the module and a user level |
| `actionAtlasfuelbillapproveview`, `actionAtlasexpensesbillapproveview`, `actionAtlaslocaltravelapproveview` | Matching views, line 10 in each | The matching bill table, then `employees` for `bill['eid']` | The queue compares boss or grand-boss | `where id` is the requested id. Session employee is assigned and is not in that `where` | Session `user.db` | Verified. Not executed | **REDESIGN** | AUD-18 already recorded the fuel status write. These rows are the reads |
| `actionAtlasfuelbillaccview` | `atlasfuelbillaccview.php` lines 8–10 | `atlas_fuel_bill` by id | The accounts list has no employee filter | Requested id | Session `user.db` | Verified | **REDESIGN** | The other accounts views and the second-approver views were not line-read |

`actionBillreject` updates `mobile_bill` by `$_REQUEST['id']` and does not render a bill. It is a write and is not a matrix row.

## 5. Counts for the twenty actions opened here

| Disposition | Actions | Which |
| --- | --- | --- |
| **KEEP** | 6 | Appraisee view, responder list, salary card, tax 108 list, Atlas approve queue, Atlas approved-history queue |
| **REDESIGN** | 11 | Two multirater detail views, both service-book actions, both mobile-bill approval screens, Atlas accounts list, three Atlas approve views, fuel accounts view |
| **UNRESOLVED** | 2 | Customer list, parameters screen |
| Mixed on one action | 1 | `actionIndex`: employee branch **KEEP**, customer branch **UNRESOLVED** |

Evidence status of those twenty: 17 verified, 2 partial, and 1 parameters action whose query is verified while its employee role is unresolved. None of the twenty is **DEFERRED**.

These twenty are not a percentage of the 210 declarations.

## 6. Repeated identity patterns

Supported by more than one audit:

1. A list names the session employee, or names that employee as boss, grand-boss, or rater. The later view loads the requested id and does not repeat the test. Seen in documents, dashboard detail, admin payroll reads, mobile print, Atlas detail, probation review, 360 rater views, and, in this batch, multirater detail, mobile-bill approval screens, and Atlas approve views.
2. A company-admin or accounts screen is gated by a user level or a module string, and the row query does not test `emp_unit` or an accounts assignment. Seen in AUD-12 and in the service book and Atlas accounts list.
3. Company scope is the session connection, except where a view also calls `Yii::$app->atlas`. That second connection was observed. It was not mapped to company id 10.

No row here is an executed access.

## 7. Remaining non-deferred gaps

AUD-21 closed the gaps that were open in this section. The detail is in section 10.

| Gap | Result |
| --- | --- |
| `DefaultController` | All 11 declarations are classified in section 10. They are not all reads |
| Atlas accounts and second-approver views | The paid list and seven detail views load by status or by id. They do not repeat boss, grand-boss, or an accounts user |
| Bill approve-history | `approvehistory.php` lines 103 and 137 keep `internal_first_approver` equal to the session employee id |
| File cabinet | `hrm_filecab` has no employee attribute. `HrmFilecabSearch::search` does not add one |
| `searchorgo` | `employees.id` and `emp_id_no` are separate filters. The company line is commented out |
| `responder_id` | The admin dropdown writes `emp_id_no`. The customer import writes `mra_customers.id`. The ESS customer screens bind `employees.id` |
| `actionTax108` post and `actionBillreject` | Still writes. They stay outside the read rows |

`AttendanceController` and `LeaveController` are still not classified action by action. Their workflow audits remain AUD-01 and AUD-02. `indexnreedemo.php` was not re-read. The 210 in-scope declarations were not each reviewed.

Blocked by an owner decision, not by this matrix: SEC-CORE-003, SEC-CORE-004, D1–D6, and the attendance and payroll gates. SCOPE-01 still defers provident fund, gratuity, recruitment, and PMS content. Visual capture remains blocked.

## 8. Recommended next batch

Consolidate the repeated list-then-id findings into one authorization-requirements note. Do not open another controller, and do not classify the remaining 210 declarations one by one, unless a later task names a file that does not follow that pattern. Do not open `GfmanagementController`, `PmsController`, `PfmanagementController`, or `RecruitmentController`.

## 9. Statement

No application code, schema, test, configuration, database, or Git state was changed. The legacy tree was not modified. No gate was signed.

## 10. AUD-21 gap closure

Date: 2026-10-16. The declaration totals in section 1 were not recounted.

### DefaultController

No action in this file tests a module string. Each action except the two logouts requires a user level. `indexnreedemo.php` was not re-read.

| Action | Behavior | What the source shows | Evidence | Disposition |
| --- | --- | --- | --- | --- |
| `actionIndex` | Read | Passes `getEmpInfo` of the session employee, except the `Trustee` branch, which renders `trustee.php` with no employee argument. `External` redirects to `actionLogoutext`. `user.dbname` `proedge` or `nreedemo` renders `indexnreedemo`. Username `atlascopcoacc` renders `accindex.php`, a menu whose first link is `atlasbills/atlasbillacc` | Verified for the action and for the first lines of `trustee.php` and `accindex.php` | **KEEP** the session employee on the branches that load it. The accounts menu is a username test. `actionAtlasbillacc` does not repeat that username |
| `actionLogout` | Other | `session->destroy()`, then redirect to `/` plus `sesdata('dbname')` | Verified | No employee read |
| `actionLogoutext` | Other | Destroys the session. `customer` goes to `/dbname/customer`. Any other level goes to `/dbname/login` | Verified | No employee read |
| `actionProfile` | Read | `getEmpInfo(sesdata('employee_id'))` and `getCom` of the session company | Verified | **KEEP** |
| `actionCloudpunch` | Mixed | The view lists `location_access_data` on `Yii::$app->db` where `company_name` is `user.dbname` and `employee_id` equals the session `emp_id_no`. A post updates `remark` where `id` is posted `entryid`, on `Yii::$app->db`, with no employee test | Verified | **KEEP** the list. **REDESIGN** the remark update. The list column is the employee number, not `employees.id` |
| `actionChangepassword` | Mixed | Reads and updates `user` where `id` is `sesdata('user_id')`. The posted old password is compared as stored text, and the posted new password is stored as text | Verified | **KEEP** the session user id. **REDESIGN** the stored-text password. Same pattern as ESS-ID-03, on this action |
| `actionFlagrise` | Write | Updates `hrm_pms_emp_po.fl` where `id` is `$_REQUEST['bid']`. No employee test. Both branches then redirect to `/ess` | Verified | **REDESIGN** the missing row check. `PmsController` was not opened |
| `actionIndexext` | Read | The action loads the session employee and does not pass it. `indexext.php` line 6 reads `sesdata('employee_id')` | Verified for the action and that line | **KEEP** the session id. The rest of the external menu was not line-read |
| `actionNotifications` | Read | Passes the session employee to `notifications.php` or `notifications_apps.php` | Verified for the action. Notification queries in those views stay with the earlier audits that opened them | **KEEP** the action argument |
| `actionHelp` | Read | Passes the session employee. The opened view shows help images and no employee query | Verified | **KEEP** |
| `actionOthers` | Read | Passes the session employee. The opened view is a label and no employee query | Verified | **KEEP** |

### Atlas views that section 4 had not line-read

Module `atlasbills` and a user level are required. None of these reads compares the session employee with `boss`, `g_father`, or `eid`. The connection is `user.db`. Actions that honor `$_REQUEST['ac']` change bill status. Those branches were not line-read. They are writes.

| View | Query | Disposition |
| --- | --- | --- |
| `atlasbillaccpaid.php` lines 34–36 | Three bill tables where status is `Paid` and month/year match. No employee column | **REDESIGN** |
| `atlasfuelbillapproveseview.php` line 10 | `atlas_fuel_bill` where `id` is the request | **REDESIGN** |
| `atlasfuelbillaccviewp.php` line 10 | `atlas_fuel_bill` by id | **REDESIGN** |
| `atlasexpensesbillapproveseview.php` line 10 | `atlas_expenses_bill` by id | **REDESIGN** |
| `atlasexpensesbillaccview.php` line 10 | `atlas_expenses_bill` by id | **REDESIGN** |
| `atlasexpensesbillaccviewp.php` line 10 | `atlas_expenses_bill` by id | **REDESIGN** |
| `atlaslocaltravelaccview.php` line 10 | `atlas_local_travel_bill` by id | **REDESIGN** |
| `atlaslocaltravelaccviewp.php` line 10 | `atlas_local_travel_bill` by id | **REDESIGN** |

This repeats section 6. It is not a new pattern.

### Named relationships

| Question | What the source shows | What remains open | Disposition |
| --- | --- | --- | --- |
| Approve history | `actionApprovehistory` renders the session employee. The view lists `bill_employee_bills` where `internal_first_approver` equals `sesdata('employee_id')` and the status is `First Approved`, `Second Approved`, or `Second Rejected`. Line items use the bill id from that list | A second-approver history query was not found in this view | **KEEP** the first-approver filter |
| File cabinet | `DocumentsController::actionIndex` forces `document.employee_id` to the session employee. `HrmFilecabSearch::search` filters only posted id, date, filename, and caption. `HrmFilecab` properties are `id`, `filename`, `caption`, and `udate` | Whether a cabinet with no employee column is meant to be shared | **UNRESOLVED** as a sharing rule. The absence of an employee filter is verified |
| `searchorgo` | `EmployeesSearch::searchorgo` lines 167–192. `id` is an equality filter when supplied. `emp_id_no` is a `LIKE` filter when supplied. `emp_unit` for the session company is commented out. `boss` and `g_father` apply only when those search fields are set | Who the directory is for. The method does not force the session employee | **REDESIGN** the commented company filter. The id-versus-number distinction is verified |
| `responder_id` | `MraOrganogram` declares a string of at most 255 characters. The admin dropdown value is `emp_id_no`. `MraorganogramController` line 409 assigns `mra_customers.id` for an imported customer. ESS employee lists bind `emp_id_no`. ESS customer lists bind `sesdata('employee_id')` | Which value a given row contains. A live column was not read | **UNRESOLVED** as a single stored type. The three uses are verified |

ESS READ-IDENTITY COVERAGE MATRIX COMPLETE FOR THIS BATCH
