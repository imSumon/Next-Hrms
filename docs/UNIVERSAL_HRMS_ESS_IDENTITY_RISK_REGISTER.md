# Universal HRMS — ESS Identity Risk Register

Date: 2026-10-16

Status: **CONSOLIDATION FOR AUD-08 THROUGH AUD-21, WITH THE AUTHORIZATION REQUIREMENTS NOTE.** These rows are proposals. They are not approved requirements, not exploited vulnerabilities, and not signed gates. The requirements note is `next-hrms\docs\UNIVERSAL_HRMS_AUTHORIZATION_REQUIREMENTS.md`. It does not replace this register or the audit files. Source inspection is not runtime verification. No company is migration-approved.

No earlier risk register was found in `next-hrms\docs`. This file is the consolidation. It does not replace AUD-08 through AUD-20.

The catalog denominator is unchanged: 25 modules, 101 submodules, and 416 controller records. This file does not reclassify those rows. The checkpoint's count of 91 inventory-only submodules was not recounted.

## 1. Patterns

| Pattern | Evidence | Confidence | Proposal | Follow-up |
| --- | --- | --- | --- | --- |
| P1. A list filters to the session employee, and the action that uses an id does not | AUD-09 `findModel`; AUD-11 dashboard detail; AUD-12 profile, payslip, salary card; AUD-13 approval; AUD-15 both deletes; AUD-16 print and delete; AUD-17 delete and both approval actions; AUD-18 three bill detail views; AUD-19 `findModel`, `actionDelete`, and `actionPpapplication`; AUD-20 four rater views load `pms_360_emp_period_entry` by `pepe` | Confirmed in the cited lines. Not executed | **REDESIGN** before any of these actions is copied | Blocks implementation of that action. Does not block the employee foundation. Does not require a new owner decision to keep auditing |
| P2. A posted employee number is stored, while the screen only prefills the session number | AUD-15 lunch insert and update; AUD-16 mobile-bill insert; AUD-11 overtime create and edit; AUD-09 document create, where the controller does not set `employee_id`; AUD-19 probation insert, which also updates `employees` for the posted id | Confirmed for the controller lines. The forms were not all opened | **REDESIGN** the missing binding | Same as P1 |
| P3. The view hides a delete or approval link by status. The action does not test that status | AUD-15 `actionDelete`; AUD-16 `actionDeletea`; AUD-17 `actionDelete` and the approval saves. AUD-18 `actionBilldel` is the opposite: it tests status `New` before delete | Confirmed as a source mismatch where named. No screenshot | **REDESIGN** the action that lacks the test. **KEEP** the Atlas delete test | UI confirmation is blocked. The source gap is already recorded |
| P4. Company or database scope is a session connection, a hardcoded alias, or a request name | AUD-11 `searchorgo` and the seven hardcoded index connections; overtime writes on `vtlw`; AUD-13 `$_REQUEST['db']`; AUD-10 PIMS `findOne` without `emp_unit`; AUD-17 `sesdata('dbname')` beside `user.db` | Confirmed where the lines were read. Alias-to-company maps for 24, 20, and 10 remain unresolved | **REDESIGN** a request-selected connection. **CONFIGURABLE** where the source only switches a view by alias | Do not invent the company map. Implementation of those aliases stays blocked until the map is evidence, not a guess |
| P5. Sensitive values are rendered from the loaded row | AUD-08 password written from the post and compared as stored text; AUD-12 grid prints `username.password`; AUD-12 and dashboard salary views print pay fields for the requested id | Confirmed in those files. Not displayed in this session | **REDESIGN** the password column and the clear-text password write. **KEEP** a session-bound payslip read | A future screenshot must not capture the password column or salary figures |
| P6. The controller names a model or view that is not in the ESS tree | AUD-14 `TicketFromEss` and `views\ticketing` | Confirmed absent. The backup class uses another namespace and was not treated as the live model | **DEFERRED** as an evidence gap, not as a decision to remove ticketing | Resolve only by finding the class the controller imports |
| P7. Writes found in these batches do not insert an audit row | AUD-09 document save, AUD-10 PIMS `save(false)`, AUD-11 overtime, AUD-13 leave, AUD-15 lunch, AUD-16 mobile bill, AUD-17 bills | Confirmed as absence in the actions read. Another listener was not searched | **IMPROVE** only after the ownership gap is closed. Do not add history that records an unauthorized write as valid | Does not by itself block architecture |

## 2. Finding index

| Ref | Source | Action and table | Behavior the source shows | Confidence | Proposal | Blocks |
| --- | --- | --- | --- | --- | --- | --- |
| ESS-ID-01 | AUD-08 | Profile, personal files, payslip reads | Session employee id | Confirmed | **KEEP** | No |
| ESS-ID-02 | AUD-08 | Payslip view name | Alias and level choose the template | Partial | **CONFIGURABLE** | Implementation of that layout |
| ESS-ID-03 | AUD-08 | Password update on `user` | Compares and stores the posted password text for the session user | Confirmed | **REDESIGN** | Implementation of password change |
| ESS-ID-04 | AUD-08 | Cloud-punch remark | Posted id, no employee comparison in the lines read | Partial | **REDESIGN** | That write |
| ESS-ID-05 | AUD-08 | PMS flag | Posted id | Confirmed as a write pattern | **DEFERRED** with SCOPE-01 | Do not audit PMS content in the identity queue |
| DOC-01, DOC-02 | AUD-09 | Two document lists | Session employee filter | Confirmed | **KEEP** | No |
| DOC-03, DOC-04, DOC-05 | AUD-09 | View, update, delete, upload, and `DocumentController` | Id or posted employee, original file name | Confirmed in the actions | **REDESIGN** | Those actions |
| DOC-06, DOC-07 | AUD-09 | Admin list filter; company ids 24, 20, 10 | Purpose and company map not established | Unresolved | **UNRESOLVED** | Do not treat either as a rule |
| PAY-01, PAY-02 | AUD-10 | NOC, salary certificate, tax, income-tax deduction | Session employee. Alias chooses the certificate view | Confirmed for the controller | **KEEP** the employee. **CONFIGURABLE** the view | Layout only |
| PAY-03 | AUD-10 | PF actions | Not opened | Unresolved | **DEFERRED** with SCOPE-01 | Do not open PF |
| PIMS-01 | AUD-10 | `EmployeesController::actionUpdate` | `findOne($id)` on the session company connection, `save(false)` | Confirmed | **REDESIGN** | Admin employee write |
| DASH-01 | AUD-11 | Dashboard index header and `searchboss` company filter | Session employee and session company | Confirmed | **KEEP** | No |
| DASH-02 | AUD-11 | `searchboss` | `LIKE` on boss and grand-boss. Exact tests are commented out | Confirmed | **IMPROVE** | That list |
| DASH-03, DASH-04 | AUD-11 | `searchorgo`, detail pages, `getEmpInfo` | Request id. `getEmpInfo` does not force the session employee | Confirmed | **REDESIGN** | Those reads |
| OT-01 | AUD-11 | Overtime print and both rejects | Session employee number compared | Confirmed | **KEEP** | No |
| OT-02, OT-03 | AUD-11 | Overtime create, edit, approve | Posted ids on `vtlw`. No day-result or payroll write in the controller | Confirmed for the tables named. Payroll effect unresolved | **REDESIGN** the writes. **UNRESOLVED** for payroll | Do not describe overtime approval as a payroll write |
| ADM-01, ADM-02 | AUD-12 | `clientAdmin` list | Company filter on `emp_unit`. Password attribute printed | Confirmed | **KEEP** the filter. **REDESIGN** the password column | That screen |
| ADM-03, ADM-04 | AUD-12 | Profile, payslip, salary card, certificate | Request id. Templates only for `mrb` and `ceva` | Confirmed | **REDESIGN** the reads. **CONFIGURABLE** the two templates | Those reads |
| HLV-01, HLV-02 | AUD-13 | Hourly-leave create, index, delete | Session employee. Delete has no status test | Confirmed | **KEEP** the binding. **UNRESOLVED** for a status rule | No new rule |
| HLV-03 to HLV-06 | AUD-13 | Approval, request `db`, id `103`, unit helpers, annual plan | Id or request connection | Confirmed | **REDESIGN** | Those actions |
| TKT-01 | AUD-14 | `actionIndex` ticket save | Controller does not set the employee. Model file absent | Unresolved | **UNRESOLVED** | Ticketing implementation |
| TKT-02 | AUD-14 | `actionDeleteleave` | Deletes `hrm_leave_emp_app` only when `emp_id` matches. Does not delete a ticket | Confirmed | **KEEP** the comparison | No |
| LCH-01 | AUD-15 | Lunch views | Session employee number | Confirmed | **KEEP** | No |
| LCH-02, LCH-03 | AUD-15 | Lunch insert, update, both deletes | Posted or requested id. Session value loaded and unused on delete | Confirmed | **REDESIGN** | Those writes |
| MBL-01 | AUD-16 | Mobile-bill list | Session employee number | Confirmed | **KEEP** | No |
| MBL-02, MBL-03, MBL-04 | AUD-16 | Insert, delete, print | Posted or requested id. Mail send is not commented out | Confirmed | **REDESIGN** | Those actions. Approval actions were not opened |
| BILL-01 | AUD-17 | `actionEmpbills`, index list | Session `emp_id_no` on `bill_employee_bills` | Confirmed | **KEEP** | No |
| BILL-02, BILL-03 | AUD-17 | `actionDelete`, `actionApprovelist`, `actionApprovelistext` | Delete and status save by requested id. Views filter; actions do not | Confirmed | **REDESIGN** | Those actions |
| BILL-04 | AUD-17 | Upload and the two database names | Original file name. Connection equality not shown | Unresolved | **UNRESOLVED** | Do not equate the two names |
| ATL-01 | AUD-18 | Atlas bill index lists | `eid` is the session employee on `atlas_fuel_bill`, `atlas_expenses_bill`, and `atlas_local_travel_bill` | Confirmed | **KEEP** | No |
| ATL-02 | AUD-18 | `actionBilldel` | Selects the row only when `eid` matches, then deletes only when status is `New` | Confirmed | **KEEP** | No |
| ATL-03 | AUD-18 | Three detail views | Load by `id`. The following `if` assigns `Approved` with `=`. The `eid` comparison is the `elseif` and uses `$emp` | Confirmed in the source text. Not executed | **REDESIGN** | Those views |
| ATL-04 | AUD-18 | `actionAtlasfuelbillapproveview` | Sets status from `$_REQUEST['id']` with no approver comparison in the lines read. Other approve and accounts bodies were not line-read | Partial | **REDESIGN** for the fuel branch that was read. **UNRESOLVED** for the unread bodies | Do not treat this as a finished approval audit |
| PRB-01 | AUD-19 | Probation index list | Session `emp_id_no` on `employee_probationers_progress`. Open rows exclude status `Closed` | Confirmed | **KEEP** | No |
| PRB-02 | AUD-19 | `actionIndex` insert and `employees` update | Posted `emp_id` and posted `status`. Also writes education fields on `employees` for that id | Confirmed | **REDESIGN** | That write |
| PRB-03 | AUD-19 | `actionDelete`, `actionView`, `actionUpdate`, `actionIndexx` | `findOne($id)` on `sesdata('dbname')`. No module test and no status test on delete | Confirmed | **REDESIGN** | Those actions |
| PRB-04 | AUD-19 | `actionPpapplication` | Updates status and comments by posted `lid`. View labels are `New`, `Appraiser Responded`, `Management Decision`, and `Closed`. The action does not test the previous status or the appraiser. Notification lists do filter the appraiser fields | Confirmed for the writes. The allowed sequence is not established | **REDESIGN** the missing checks. **UNRESOLVED** as a lifecycle rule | Do not adopt the four labels as a signed probation policy |
| PRB-05 | AUD-19 | Status `Closed`, `actionDelete`, connection names | No literal `Closed` assignment was found in the files that name the table. No delete link was found in the probation views or the two notification views that were read. Index uses `user.db`. CRUD uses `sesdata('dbname')` | Partial | **UNRESOLVED** | The admin PIMS copy of this controller was not opened |
| A360-01 | AUD-20 | Own 360 list and `actionPms360viewbo` | `pms_360_emp_period_entry` and the rating tables use the session employee | Confirmed | **KEEP** | No |
| A360-02 | AUD-20 | Four rater lists | `superviser`, `colleague`, `peers`, or `customer` equals the session employee on `pms_360_emp_period_entry` | Confirmed | **KEEP** | No |
| A360-03 | AUD-20 | Four rater detail views | Entry loaded by `pepe`. Subject employee and ratings follow that entry. Session employee is not in the `where` | Confirmed in the source. Not executed | **REDESIGN** | Those four views |
| A360-04 | AUD-20 | `actionPms360viewparameters` and the period id | Objectives by requested year. No employee query. `pepe` is not compared with the requested period | Partial | **UNRESOLVED** | Do not treat this as a PMS design |
| MRA-01 | Read matrix | Multirater own view and responder list | Session `emp_id_no` on `mra_emp_project_entry` and `mra_organogram.responder_id` | Confirmed | **KEEP** | No |
| MRA-02 | Read matrix | `actionMraresponderviewbo`, `actionMracustomerviewbo` | `mra_organogram` by `pepe`. Session responder is not in the `where` | Confirmed. Not executed | **REDESIGN** | Those two views |
| MRA-03 | Read matrix | Customer lists and `actionMraviewparameters` | Customer lists bind the numeric session id. Parameters reads `pms_360_behavioral_objectives` by year | Partial | **UNRESOLVED** | Do not equate the numeric id with `emp_id_no` |
| SVC-01 | Read matrix | `actionServicebook`, `actionServicebookprint` | `clientAdmin`, then `getEmpInfo` of the requested id. No `emp_unit` test | Confirmed | **REDESIGN** | Those two reads |
| PAY-04 | Read matrix | `actionSalarycard`, `actionTax108` list | Session employee on `employee_salary` and `tax_return_info`. The card also reads PF tables for that same id | Confirmed for the employee filter | **KEEP** | `PfmanagementController` stays deferred |
| MBL-05 | Read matrix | Mobile-bill approval screens | GET loads `mobile_bill` by `lid`. No approver comparison | Confirmed | **REDESIGN** | The posts are writes and stay outside the read matrix |
| ATL-05 | Read matrix | Atlas approve queue and approved history | `boss` or `g_father` equals the session employee | Confirmed | **KEEP** | The approve view also sets the employee model connection to `atlas` |
| ATL-06 | Read matrix | Atlas accounts list and four bill views | Accounts list has no employee filter. Approve and fuel-accounts views load by id | Confirmed for the views named in the matrix | **REDESIGN** | AUD-21 read the remaining accounts and second-approver views. They load by id or by status `Paid` and do not add a role test |
| DEF-01 | AUD-21 | `DefaultController` eleven actions | Profile, help, others, notifications, and the index branches that load an employee use the session employee. Logout destroys the session. Cloud-punch list uses `emp_id_no`. Cloud-punch remark, password text, and `actionFlagrise` use a posted id | Confirmed for the actions. `indexnreedemo.php` was not re-read | **KEEP** the session reads. **REDESIGN** the three posted-id writes | No module string in this controller |
| ATL-07 | AUD-21 | Atlas paid list and seven unread detail views | `atlasbillaccpaid` filters status `Paid` only. The seven views select the bill by id | Confirmed | **REDESIGN** | Same pattern as ATL-06. The `ac` write branches were not line-read |
| BILL-05 | AUD-21 | `actionApprovehistory` | `internal_first_approver` equals the session employee id | Confirmed | **KEEP** | A second-approver history query was not in that view |
| DOC-08 | AUD-21 | `HrmFilecabSearch` | No employee column on `hrm_filecab`. Search does not add one. The document list above it does force the session employee | Confirmed | **UNRESOLVED** as a sharing rule | Do not invent an owner column |
| DASH-05 | AUD-21 | `EmployeesSearch::searchorgo` | `id` and `emp_id_no` are separate filters. Session `emp_unit` is commented out | Confirmed | **REDESIGN** the missing company filter. The two columns are verified as different | The directory's audience is not established |
| MRA-04 | AUD-21 | `mra_organogram.responder_id` | Admin form stores `emp_id_no`. Customer import stores `mra_customers.id`. ESS customer screens bind `employees.id` | Confirmed as three different uses. A stored row was not read | **UNRESOLVED** as one column type | Do not collapse the three uses |

**UNRESOLVED** is an evidence state. It is not a product decision.

## 3. Coverage

| Count | Figure | Limit |
| --- | --- | --- |
| Inventory denominator | 25 modules, 101 submodules, 416 controller records | Not re-counted in this batch |
| Inventory-only submodules | 91, from the existing checkpoint | Not re-derived |
| ESS read-identity matrix | `next-hrms\docs\UNIVERSAL_HRMS_ESS_READ_IDENTITY_COVERAGE_MATRIX.md` | Reuses AUD-08 through AUD-20. Opens 20 read actions. Does not reclassify the 416 controller records |
| Declaration count, not a read-action total | 24 ESS controller files, 295 `function action` declarations. Deferred controllers account for 85. 210 declarations remain | The catalog has no action names. The 210 figure was not split into reads and writes |
| Actions opened for this matrix | 20, before AUD-21 | 6 **KEEP**, 11 **REDESIGN**, 2 **UNRESOLVED**, 1 mixed. 17 of the 20 have a verified query. AUD-21 does not add those 20 to a new total |
| AUD-21 classifications | 11 `DefaultController` declarations, plus 8 Atlas views that the matrix had not line-read | Not added to the 295 declaration count. The 210 in-scope declarations were not each reviewed |
| Still not classified action by action | `AttendanceController` and `LeaveController`, and the body of `indexnreedemo.php` | Workflow audits and earlier notification traces stay in place |
| Functional business-rule analysis | Attendance, leave, and payroll rules remain the AUD-01, AUD-02, AUD-05, AUD-06, and AUD-07 traces | AUD-08 through AUD-20 did not replace those traces and did not finish a business-rule catalog |
| Visual evidence captured | 0 | VIS-01 through VIS-12 are pending |
| Visual evidence blocked | All 12 planned rows | No authorized legacy session |

AUD-21 classified the 11 `DefaultController` declarations and the Atlas views the matrix had not line-read. Approve history, the file cabinet, `searchorgo`, and `responder_id` are recorded in the matrix section 10. `GfmanagementController`, `PmsController`, `RecruitmentController`, and `PfmanagementController` stay deferred. Attendance and leave keep their workflow audits and were not re-labeled action by action. The 210 remaining declarations were not each reviewed.

## 4. What is not an owner decision

Continuing the source audit does not need a new signature. SEC-CORE-003, SEC-CORE-004, D1–D6, and the attendance and payroll gates stay unsigned. Those gates block implementation. They do not block the next read-only trace.

## 5. Next task

The owner sitting paper is `next-hrms\docs\UNIVERSAL_HRMS_AUTHORIZATION_OWNER_REVIEW.md`. It is not a decision. SEC-CORE-003 and SEC-CORE-004 may stay unsigned. BD-D16, BD-D17, and D1–D6 are not decided there. Do not open `GfmanagementController`, `PmsController`, `PfmanagementController`, or `RecruitmentController`. Do not classify the remaining 210 declarations one by one. Do not repeat AUD-01 through AUD-21.

The 295 declaration count is not a count of audited reads. Of the 210 non-deferred declarations, not every declaration was classified. Implementation of attendance, payroll, roles, narrower scope, and migration stays unauthorized.

ESS IDENTITY RISK REGISTER COMPLETE FOR THIS BATCH
