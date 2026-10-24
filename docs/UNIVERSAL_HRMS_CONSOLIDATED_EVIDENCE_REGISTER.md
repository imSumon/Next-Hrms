# Universal HRMS — Consolidated Evidence Register

Stage 2. Thirty findings. This register does not approve a policy. Status words are confirmed, partially confirmed, or unresolved. None are marked approved.

Evidence types: source-code finding, previously observed database result, design artifact.

## Register

| ID | Audit | Observed behavior | Evidence | Affects | Class | Priority | Status |
| --- | --- | --- | --- | --- | --- | --- | --- |
| F-01 | AUD-01 | Hourly leave writes `hourly_leave_emp_app` and a balance. It does not write attendance or payroll | Source | LEAVE-CORE-004 | LEGACY ONLY | P1 | Confirmed. Not a first-release shall |
| F-02 | AUD-01 | Hourly balance uses an 8-hour day, a 30-minute remainder, female-only ML, and July–June proration | Source | Leave year, not payroll month | LEGACY ONLY | P1 | Confirmed. Not approved |
| F-03 | AUD-02 | Day leave deducts a balance only when status becomes 3 | Source | LEAVE-CONF-001, BD-D06 | LEGACY ONLY | P0 | Confirmed |
| F-04 | AUD-02 | Day-leave approve does not compare the session employee with `boss_id` or `gboss_id` | Source | APP-CORE-001, SEC-CORE-001, BD-D17 | LEGACY ONLY | P0 | Confirmed gap |
| F-05 | AUD-02 | Day-leave delete removes the row and does not restore a deducted balance | Source | Leave integrity | LEGACY ONLY | P1 | Confirmed |
| F-06 | AUD-02 | Atlas first approval can jump to status 3. Other names in that branch stop at 1, which does not deduct | Source | BD-D17 | LEGACY ONLY | P1 | Confirmed alias branch |
| F-07 | AUD-02 | Atlas and CEVA grand-boss attendance approval can set the day to Present and replace punches with shift times | Source | BD-D16, PUNCH-APPROVED | LEGACY ONLY | P0 | Confirmed. Raw punch table was not updated |
| F-08 | AUD-02 | `proedge` and `vtl` approval in that action changes application status only | Source, agrees with Proedge August | BD-D16 | LEGACY ONLY | P0 | Confirmed for that action |
| F-09 | AUD-02 | A VTL displayed-punch change was not produced by the `vtl` branch | Prior month plus source | BD-D16 | UNKNOWN | P1 | Unresolved writer |
| F-10 | AUD-03 | Admin login is a custom session. Yii RBAC is not the check | Source | SEC-CORE-001 | LEGACY ONLY | P0 | Confirmed |
| F-11 | AUD-03 | Eight appadmin controllers have no `user_level` test. `UserController::actionUpdate` saves with the level test commented out. `SiteController::actionBackend` sets a session from a username without a password comparison | Source | SEC-CORE-001 | LEGACY ONLY | P0 | Confirmed |
| F-12 | AUD-03 | `moduleAccess()` checks a module grant and is commented out inside appadmin. Grid buttons read controller grants | Source | SEC-CORE-001 | LEGACY ONLY | P0 | Confirmed. Non-appadmin call sites were sampled, not exhaustively read |
| F-13 | AUD-03 | `supadmin` may open any company id. Other levels need a `user_companies` row | Source | Tenant boundary | LEGACY ONLY | P0 | Confirmed. Role list unsigned |
| F-14 | AUD-04 | File upload writes `date` and `emp_code` on `all_att_log`. Location transfer also writes `update_datetime` and `balid` = `location_access_data.id` | Source | PUNCH-CORE | LEGACY ONLY | P1 | Confirmed. Not the closed-month writer |
| F-15 | AUD-04 | Closed-month rows had `area` filled and `update_datetime` null. Neither in-repo writer produces that pair | Prior month plus source | Punch archive | UNKNOWN | P1 | Unresolved external writer. No vendor named |
| F-16 | AUD-04 | `area` is never assigned in this source | Source | Archive note | UNKNOWN | P1 | Unresolved |
| F-17 | AUD-04 | `balid` means the location-access id only on the transfer insert. Other rows stay uninterpreted | Source plus prior samples | Archive note | UNKNOWN | P1 | Partially confirmed |
| F-18 | AUD-04 | The shared log query has no company predicate. Attribution is the company card map. Catalog ids 24, 20, and 10 are not stored on the punch | Source plus workbook | D7-APPROVED | CORE boundary | P0 | Confirmed in source. Card-number overlap was not counted |
| F-19 | AUD-04 | `actionDelete` and `actionArchivelogs` delete raw rows. The target rule says a correction does not edit the raw punch | Source versus PUNCH-APPROVED | PUNCH-WITHDRAW | LEGACY ONLY versus approved target | P0 | Confirmed contradiction. Withdrawal stays unsigned. The target rule stays in force |
| F-20 | AUD-05 | Atlas, CEVA, and Proedge process actions share one shape: card map, MIN/MAX punch, `photo` weekend, employee shift, status-3 leave, status-1 approval substitution, month lock | Source | One engine | CORE shape | P1 | Confirmed in source. CEVA production is not confirmed |
| F-21 | AUD-05 | Atlas action uses 31-minute late and early buffers. Proedge and CEVA actions use 16-minute late grace and compare early-out with shift end | Source | BD-D01, BD-D05 | CONFIGURABLE | P0 | Confirmed constants. Not approved defaults. September grace was not measured |
| F-22 | AUD-05 and third-company audit | No local CEVA database. Atlas September matched employee shift, Friday–Saturday in `photo`, no September lock, no overtime pay | Previously observed database result | BD-D03, BD-D08 | LEGACY ONLY | P1 | Atlas month confirmed as previously reported. CEVA month unresolved |
| F-23 | Closed months and traceability | VTL missing-out was stored as Absent. Proedge and Atlas used Present plus missing-punch text. Shift winner differed | Previously observed database result | BD-D02, BD-D03 | CONFIGURABLE | P0 | Confirmed for those months. Not a universal outcome |
| F-24 | Workbook and traceability | Calculated, payable, and paid overtime disagree across the three months | Previously observed database result | BD-D08 through BD-D12 | BUSINESS DECISION | P0 | Confirmed split. Formulas unsigned |
| F-25 | AUD-06 | In-month `joining_date` starts the attendance loop. In-month `discontinue_date` stops it only when `disconrinue` is 1 | Source | D3, D6, BD-D07 | LEGACY ONLY | P0 | Confirmed. No operated partial month |
| F-26 | AUD-06 | Salary generate prorates `partial=1` heads from joining date through month end. Divisor is 30 when `has_provident_fund` is greater than 0, otherwise the calendar length. `disconrinue=1` is omitted and is not prorated by leaving date | Source | PAY-CONF-001, BD-D07 | LEGACY ONLY | P0 | Confirmed formula. Not an approved default |
| F-27 | AUD-06 | `ML` day reduction runs only for database names `icwlw` and `valw` | Source | Client payroll | LEGACY ONLY | P2 | Confirmed branch |
| F-28 | AUD-06 | Payroll month is the requested calendar month. `leave_financial_year` is a leave year. Hourly July–June is a third period | Source | FY-CONF-001 | CONFIGURABLE | P1 | Confirmed separation. Company months unresolved |
| F-29 | Steps 20N, 20O, 20R | Three company rows exist. Employees and episodes were 0. No company is migration-approved | Design artifact and prior local review | RECON-APPROVED | CORE constraint | P0 | Confirmed. Not re-queried in Stage 2 |
| F-30 | Step 21C | Live `legacy_master` comparison was not performed | Design artifact | Catalog confidence | UNKNOWN | P1 | Unresolved. MySQL was not started |
| F-31 | Leave-balance source, 2026-10-17 | Proedge and Viyellatex dumps, local company 5, store leave year start 7 and end 6, labels `2015-2016` through `2026-2027`. Every balance row in those dumps uses a July–June label. Atlas, local company 26, stores start 1 and end 12, labels `2021` through `2026`. All 355 Atlas earned-leave rows use a calendar-year label | Previously observed database result. Dumps, not a live query | LEAVE-CONF-003, FY-CONF-001 | CONFIGURABLE | P1 | Confirmed stored setting and stored labels. The operational rule is unsigned |
| F-32 | Leave-balance source, 2026-10-17 | No accepted opening formula, reconciliation method, or named balance confirmer is recorded for Proedge, Viyellatex, or Atlas. Proedge `prev_bal` and `encashment` are zero on 484 earned-leave rows. Viyellatex `prev_bal` is non-zero on 5 of 2,411 and `encashment` is zero. Atlas `prev_bal` is non-zero on 240 of 355 | Previously observed database result plus owner questions left unanswered | LEAVE-CORE-007, LEAVE-OPENING | UNKNOWN | P0 | Unresolved current practice. Unsigned intended policy |
| F-33 | Leave-balance source, 2026-10-17 | Atlas has 184 earned-leave rows with non-zero `encashment`, and 15 other-code balance rows labeled `2020-2021` (12) or `2022-2023` (3) | Previously observed database result | LEAVE-OPENING | UNKNOWN | P1 | Unresolved meaning and validation. Not reconstructed |
| F-34 | Leave-balance source, 2026-10-17 | The stored balance rows identify no writer and carry no confirmation. Copying them into an approved balance would promote an unresolved figure | Previously observed database result | MIG-CORE-011 | Migration risk | P0 | Confirmed risk. Historical use of a generator, form, or upload remains unknown |
| F-35 | Encashment to payroll, 2026-10-17 | `actionUploadencashment` forces status 3, replaces an `ENC` application with the same employee, year, and start date, and overwrites `leave_emp_annual_leave.encashment` with the file’s day count. It does not write `employee_salary`. No transaction, month lock, or history | Source. `HrmleaveempappController.php` 2663–2741 | LEAVE-CORE-006, LEAVE-CORE-008 | LEGACY ONLY | P0 | Confirmed path. Use is unknown |
| F-36 | Encashment to payroll, 2026-10-17 | `earn_leave_encashment` is a separate payroll table. Its upload replaces by employee code and year and can store a net payable. It does not write the annual-leave ledger or `employee_salary`. The ECL and QTEC encashment reports are not the company-id 24, 20, or 10 report buttons | Source. `EarnleaveencashmentController.php` 309–371. `payrollreports\index.php` 501–505 | PAY-CORE-007 | LEGACY ONLY | P1 | Confirmed separation. Staff use unknown |
| F-37 | Encashment to payroll, 2026-10-17 | Proedge and Viyellatex dumps: no `ENC` application token, no `earn_leave_encashment` rows, and annual `encashment` is zero. Atlas: annual `encashment` is non-zero on 184 rows, one application row contains the token `ENC`, and `earn_leave_encashment` has no rows. The studied generate view does not read either store | Previously observed database result plus source | LEAVE-OPENING, F-33 | UNKNOWN | P0 | Observed discrepancy. Not a payment result |
| F-38 | Atlas payroll data, 2026-10-17 | Atlas dump `proedgea_atlas.sql`, local company 26. `additional_module4` has 209 rows, all company 26, with no null total, month, or year. Periods are January 2017 (25), February 2020 (2), and January 2022–2026 (31, 36, 38, 38, 39). Salary head 398 is `Earned Leave`, code `EL`, Earnings, Moduler, `which_module` Additional Module 4. `employee_salary` has 5,613 rows for head 398 and company 26, across 123 month-year pairs from 2016 through September 2026; 5,337 amounts are zero and 276 are not. September 2026 has 59 salary rows and no module row. Joining `employee_salary.employee_id` to `employees.emp_id_no`, then to the module row on code, month, and year, matches all 209 module rows (208 non-zero salary amounts and 1 zero). 5,389 salary rows have no module row for that key, including 68 non-zero amounts. 15 salary rows did not resolve to a parsed employee row. Neither table stores an annual-leave encashment id or an ENC application id | Dump inspection, not a live query. `additional_module4` lines 347–364. `employee_salary` lines 2793–2803. `salary_head` tuple `(398,26,'Earned Leave','EL',...)` in the insert ending at line 6438 | F-37, LEAVE-CORE-008 | UNKNOWN | P0 | Confirmed stored rows and a generator key. Not an encashment link, a payment result, or a finalized month |
| F-39 | Separation and rejoining, 2026-10-17 | The discontinue screen always shows the separation fields. The general employee update shows the same fields when the company field list includes them. Both save the same `employees` row. `actionDupdate` saves `disconrinue`, `discontinue_date`, `discontinue_type`, and `discontinue_description` with `save(false)`. The type list is Retrenchment, Retirement, Resignation, Suspend, Terminate, Hold, Discharge, Long Absent, Death, Removal, Promotion, and Dismissal. The action does not change `active` or `joining_date`, does not compare the date with today, and does not write attendance, leave, salary, or the user row. Setting the flag back to No overwrites that row. No second employment period is inserted. `actionDelete` deletes the employee row only. `AppraisalController::actionUpload` can overwrite the same three separation fields and writes `AppraisalHistory`; the type write runs when the bank-account column is non-empty. The employee CSV create sets the flag to 0, and its separation columns are commented out. Later reads: attendance “all” requires the flag 0, while a selected employee with `active` 1 is still loaded and then truncated only when the flag is 1 and the date falls in the processed month. Salary generation requires the flag 0 and does not read the date. The shared leave generator and the Atlas-named generator require the flag 0. `actionLeavebalanceupdateyear` also includes a leaving date on or after 2025-07-01. ESS login for `proedge`, `vtl`, and `atlas` requires the flag 0 and local `emp_unit` 5, 5, and 26 | Source. `EmployeesController.php` 95–108, 285–356, 370–424, 434–441, 881–884. `_dform.php` 50–55. `_form.php` 158. `AppraisalController.php` 1101–1144, 1831–1878, 2332. `AttendancedetailController.php` `actionProcess` 293–370, `actionProcessproedge` 5334–5416, `actionProcessatlas` 5945–6027. `generatesalarynoview.php` 86–96. `HrmleaveempappController.php` 833, 1194, 2816. `ProedgeController.php` 37–40, `VtlController.php` 37–40, `AtlasController.php` 37–40 | F-25, F-26, LIFE-CORE-007, LIFE-CORE-008, D3, D6, BD-D07 | LEGACY ONLY | P0 | Confirmed path. Type labels are not separate workflows. Staff practice, final pay, forfeiture, and rehire eligibility remain unresolved |
| F-40 | Organization assignment, 2026-10-17 | The employee form and the employee CSV write the current department, designation, branch, boss, grand boss, job location, unit, floor, section, management level, and shift onto the same `employees` row. `emp_unit` is set to the session local company id. Both paths use `save(false)` and keep no prior value. The appraisal CSV updates those columns and writes `AppraisalHistory`; the actor is the appraisal row’s `created_by`. `emp_approver` stores approver strings and its upload replaces the row for that employee code. `employees_for_organogram` has its own CRUD. `cost_center_allocation` stores an employee, cost center, department, and percentage, with no date. Leave submission copies `employees.boss` and `g_father` onto the application. The studied approve action does not compare the session user with those ids. The studied salary generation selects by local company, active, and the discontinue flag, and does not read department or branch | Source. `_form.php` 89–169, 201–204, 268–315. `EmployeesController.php` 198–237, 285–356, 663–712, 726–741, 825, 878, 919–933, 952. `AppraisalController.php` 1143–1153, 1164–1194, 1366–1390, 1497–1527, 2332. `EmpapproverController.php` 311–356. `CostCenterAllocation.php` 7–16. `EmployeesForOrganogram.php` 9–16. `LeaveController.php` 628. `HrmleaveempappController.php` 638–639. `generatesalarynoview.php` 86–96 | EMP-CORE-004, LIFE-CORE-002, LIFE-CORE-003, LIFE-CORE-004, ORG-CORE-008, BD-D03, BD-D17 | LEGACY ONLY | P0 | Confirmed overwrite and three reporting stores. Effective-dated assignment is already specified. The operational store and the effective date remain unresolved |
| F-41 | Employee import, 2026-10-17 | Manual create, manual update, employee CSV, and recruitment conversion each insert or overwrite an `employees` row with `save(false)`. The employee model has no required code and no unique rule. Atlas and Proedge dump schemas have a primary key on `id` and no unique key on `emp_id_no`. The employee CSV always inserts a new row, creates a missing department, designation, branch, or management level before that insert, and then redirects to the list. It reports no rejected row. The photo CSV looks up `emp_id_no` with no company filter and calls `save(false)` on the result. The appraisal CSV looks up the numeric id, writes history rows, then saves the employee once. A missing id is used before that save. None of these paths opens a transaction. The punch-card CSV deletes only the same code and card pair, then inserts with `save(false)`. It does not check that the code exists or that the card is unused. `actionDelete` deletes the employee row and does not delete the punch-card map | Source plus previously recorded dump census. `Employees.php` 117–132. `EmployeesController.php` 198–237, 285–356, 434–441, 663–956, 967–1007, 1032–1107. `AppraisalController.php` 1143–1156, 2332. `EmpcodetopunchcardController.php` 309–348. Atlas `employees` create ends with `PRIMARY KEY (id)` only. Proedge dump the same. Step 20G: Proedge and Atlas duplicate-code groups 0. Viyellatex seven codes on two rows each | CODE-APPROVED, NOMERGE-APPROVED, NFR-CORE-006, PUNCH-CORE-009, MIG-CORE-003 | LEGACY ONLY | P0 | Confirmed gaps. The Viyellatex duplicate codes are stored rows. This audit does not identify which path wrote them. No import policy was approved |
| F-42 | Cross-company access, 2026-10-17 | The session name `companydbname` selects the database connection. `moduleAccess()` allows levels `supadmin`, `admin`, and `admin_user` when the session company has the module. It does not read the row’s company and it does not name the action. Employee, leave-application, appraisal, department, and employee-salary `findModel` methods load by primary key on that connection and do not apply `emp_unit` or `company_id`. The employee list search does apply the session local company. The department and employee-salary forms post `company_id` as the session local company. The employee form posts `emp_unit` the same way. These three dumps each contain one local `companies` row. Their employee rows are that local company, plus one Proedge row with `emp_unit` 0. The Atlas dump’s `departments` table has 381 rows and more than one local `company_id`, including 34 for company 26. Its `employee_salary` table likewise stores other local company ids. No request was sent. On 2026-10-17 the screen test was blocked: no authorized non-production Atlas session was available. The only open browser tab was the new application at `localhost:5174` on an employee search, not the legacy department screen. The Laragon data directory remains not compared. MySQL was not started | Source plus dump composition. `ApuadminComponent.php` 73–112. `EmployeesController.php` 562–571. `_form.php` 89. `HrmleaveempappController.php` 2399–2408. `AppraisalController.php` 1070–1079. `DepartmentsController.php` 293–302. Department form hidden `company_id`. `EmployeesalaryController.php` 329–338. Salary form hidden `company_id`. Step 20G company table. Atlas department insert count | ORG-CORE-002, SEC-CORE-001, SEC-CORE-002, F-12, F-41, AUD-10, AUD-12 | LEGACY ONLY | P0 | Confirmed missing row check. Within the Atlas dump, a department or salary id can name another local company. Not an executed access. SEC-CORE-003 stays unsigned |
| F-43 | Punch identity map, 2026-10-17 | `emp_code_to_punch_card` stores `id`, `emp_code`, and `punch_card_no`. The Atlas dump’s table has a primary key on `id` and no unique key. Create and update call `save()` after `moduleAccess()`. The CSV calls `save(false)` and replaces only the same code and card pair. Delete removes the row by id. No path stores a previous value, an actor, an effective date, or a reason. Day processing looks up `emp_code` equal to `employees.emp_id_no` and takes `one()`. A grouped lookup keeps the last row for that code. A missing row is then read for `punch_card_no`. The lookup does not test `disconrinue` and does not distinguish two employee rows that share a code. The map has no company column. The session database is the boundary | Source plus recorded map counts. `EmpCodeToPunchCard.php` 8–12. Atlas create ends `PRIMARY KEY (id)`. `EmpcodetopunchcardController.php` 97–121, 309–348. `AttendancedetailController.php` 391–393 and 737–741. `actionProcessproedge` 5443. `actionProcessatlas` 6054. Workbook section 9. Step 20G section 11. Viyellatex dump: card `126384` is on codes `1234567`, `1234567`, and `10231`. Card `132058` is on codes `03129399`, `132058`, and `132058` | PUNCH-CORE-009, DI-CORE-005, MIG-CORE-003, F-41, AUD-04 | LEGACY ONLY | P0 | Confirmed map behavior and the two stored Viyellatex card collisions. Not an attendance-day error. Proedge’s full duplicate-card count remains unpublished. No mapping policy was approved |

## Counts

| Status | Findings |
| --- | --- |
| Confirmed | F-01 through F-08, F-10 through F-14, F-18 through F-21, F-23 through F-29 |
| Partially confirmed | F-17, F-22 |
| Unresolved | F-09, F-15, F-16, F-30, and the CEVA half of F-22 |

Twenty-four findings are confirmed (F-01–F-08, F-10–F-14, F-18–F-21, F-23–F-29). Two are partial (F-17, F-22). Four are unresolved (F-09, F-15, F-16, F-30). The CEVA half of F-22 is the open part of that partial finding, not a thirty-first row.

The 2026-10-17 leave-balance addendum adds F-31 and F-34 as confirmed and F-32 and F-33 as unresolved. The Stage 2 counts above stay the Stage 2 census. F-28’s statement that company leave-year months were unresolved is limited by F-31 for these three dumps. F-31 does not sign the operational leave year. F-38 adds stored Atlas module and salary-head counts. It does not sign encashment pay. F-39 adds the legacy separation write path. It does not sign a separation type, a final payment, or a rehire rule. F-40 adds the legacy assignment overwrite. It does not define assignment columns or sign a reporting line. F-41 adds the employee import gaps. It does not identify the writer of the stored Viyellatex duplicate codes. F-42 adds the missing company check on primary-key reads. It does not sign SEC-CORE-003. The 2026-10-17 screen test is blocked. It does not record an executed access. F-43 adds the punch-card map lookup. It does not approve a shared card or attribute a day result to one.

| Category | Addendum |
| --- | --- |
| Confirmed evidence | F-31. Stored leave-year settings and the year labels on the balance rows agree. F-38. Atlas head 398 and `additional_module4` row counts, periods, and the employee-month key |
| Unresolved current practice | F-32 and F-33. Staff practice is not established |
| Unsigned intended policy | BD-D06, BD-D07, BD-D17, FY-BOUNDARY, and LEAVE-OPENING. The dumps do not answer them |
| Migration risk | F-34. An unresolved balance must not become a confirmed production balance by being copied. F-35 and F-36: an encashment day count and a separate net-payable row are not an `employee_salary` amount |

## Further investigation

| ID | Disposition |
| --- | --- |
| F-09, F-15, F-16, F-17 | Can remain open. The workbook already keeps `area`, `balid`, and a null company column off the business columns. Another source pass would not name the external writer |
| F-18 overlap count | Required before migration of punches. Not required to keep the card-map rule |
| F-22 CEVA month | Not necessary for the one-engine design. Required before anyone states a CEVA production policy |
| F-30 | Required before treating the 25 / 101 / 416 figures as live. Not required to design the product. Do not start MySQL for it |
| F-32, F-33 | Required before a leave balance is confirmed or loaded as approved. Not answered by another source pass. The owner supplies the figure, the period, the reconciliation, and the confirmer |
| F-35, F-36, F-37 | The studied salary path does not post encashment. Another source pass is not required to establish that. Who pays, and from which store, remains an owner question |
| F-38 | The Atlas dump count is done. Another pass over this dump is not required. Whether head 398 is encashment pay, and what a non-zero head-398 amount with no module row means, remains an Atlas owner question |
| F-39 | The separation form and its downstream reads are traced. Another source pass is not required to establish that one form covers the type labels. Which label is used, whether clearing the flag is a rehire, and whether a final payment or leave settlement is expected, remain owner questions |
| F-40 | The assignment write paths are traced. Another pass over the employee form is not required. Which store is the operational reporting line, and from which date a department, branch, or manager change should apply, remain owner questions |
| F-41 | The import paths and the recorded duplicate-code census are enough. Another pass over the employee CSV is not required. Step 20G section 11 lists both rows in each of the seven Viyellatex groups. All seven dispositions remain unresolved under the approved no-merge rule |
| F-42 | The primary-key lookups and the Atlas dump composition are enough. Another controller sample is not required. The 2026-10-17 screen test is blocked: no authorized non-production Atlas session was available, and MySQL was not started. A later read-only confirmation still requires that session. SEC-CORE-003 stays unsigned |
| F-43 | The map lookup and the two stored Viyellatex card collisions are recorded. Another pass over attendance processing is not required. Whether those shared cards are an allowed overlap remains the unsigned PUNCH-CORE-009 question. Proedge’s full card census is still unpublished |
| F-12 remainder | Required before authorization acceptance tests. The CORE rule that the server checks the action does not wait on a full call-site list |

## Employee foundation owner UAT

Session scope: the existing employee foundation only. Company isolation, the two existing membership flags, directory and profile, display-name history, episodes and rejoin, department and designation masters, and the excluded-capability checks. SEC-CORE-004 categories are not implemented permissions. Attendance, leave, payroll, role-matrix implementation, migration, and production release are outside this session.

The owner instruction of 2026-10-17 says all seven checklist areas were marked checked. A check is not a result. That checklist mark supplied no tester, execution date, actual result, or result evidence, so the checklist itself is not a passed UAT. The execution record below is the result source.

Automated tests from the same date are a separate record. They are not this UAT. The suite against `proedge_new_hrms_test` reported 69 passed and 1 failed, 681 assertions. The failure was a dropped database connection in `test_valid_login_regenerates_the_session_and_invalid_login_is_generic`. A separate rerun of that test passed, 12 assertions. No browser session was observed for this register entry.

| Area | Scenario | Tester | Date | Expected | Actual | Evidence | Result | Follow-up |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Company isolation | The server-selected company is the boundary. Another company’s employee is not returned. A client company id does not move the record. | Cursor agent session | 2026-10-17 | Active company only | See UAT-01. Directory was company A. The company B employee was not found. The foreign company id was refused. | UAT-01 | PASS | Rejected company id was not treated as a cross-company read |
| Two membership flags | Employee writes require `can_manage_employees`. Master writes require `can_manage_organization_masters`. One flag does not grant the other. Membership is not the SEC-CORE-004 role list. | Cursor agent session | 2026-10-17 | Flags stay separate | See UAT-02. The defined employee-create and department-change checks matched the expected refusals. | UAT-02 | defined flag checks PASS | The role-list sentence was seen and was not scored |
| Directory and profile | Directory and profile stay inside the active company. | Cursor agent session | 2026-10-17 | Active-company rows only | See UAT-03. Company A showed Employee A. After the switch, that profile was not found. | UAT-03 | PASS | None |
| Display-name history | A change keeps the previous name, the new name, the actor, and the time. | Cursor agent session | 2026-10-17 | History retains those four facts | See UAT-04. The temporary name was recorded. History kept the previous name, new name, actor, and time. The code change was refused. | UAT-04 | PASS | The temporary name is not a display-name rule |
| Episodes and rejoin | Rejoin adds an episode and leaves the previous episode. A non-Open episode is not set back to Open. | Cursor agent session | 2026-10-17 | LIFE-CORE-008 | See UAT-05. The previous episode remained. A new Open episode was added. Reopen and a second Open episode were refused. | UAT-05 | PASS | The test date was not judged. Overlap, date order, and inactive pay stay unsigned |
| Department and designation masters | Masters are company-scoped. A name or status change does not assign an employee. | Cursor agent session | 2026-10-17 | No assignment write | See UAT-06. The renamed department stayed on Company A. The designation list was empty. The profile still said no assignment is stored. | UAT-06 | PASS | The temporary department name is not a naming rule |
| Excluded capabilities | Branch writes, employee assignment, the role matrix, attendance, leave, payroll, and migration are not part of the session. | Cursor agent session | 2026-10-17 | Those actions are absent from this UAT | See UAT-07. Those screens were absent. The profile said no assignment is stored. | UAT-07 | PASS | None |

### Recording template

UAT-01 through UAT-07 were executed on 2026-10-17 and are recorded below. UAT-02 is limited to its defined flag checks. The temporary values in UAT-04, UAT-05, and UAT-06 are not approved business rules. This template does not by itself fill a result. Do not create employees, memberships, or companies for the session. If a precondition is missing, record BLOCKED.

Shared prerequisites: a non-production Next HRMS session, two companies that already exist, and one employee that already exists in each. The signed-in company is the server-selected membership. A client-supplied company id is not the company. SEC-CORE-004 categories are not permissions to test. Attendance, leave, payroll, the role matrix, migration, and release stay outside the session.

Copy one block per area. Leave Actual, Evidence, and Result blank until the owner writes them.

| Field | Record |
| --- | --- |
| Tester | |
| Execution date | |
| Environment and company context | |
| Preconditions and test data | |
| Steps performed | |
| Expected result | |
| Actual result | |
| Evidence reference | |
| Result | NOT VERIFIED |
| Defect or follow-up | |

**1. Company isolation.** Sign in with a membership of company A only. Open `/employees` and confirm the heading is company A and a known company B employee is absent. Open `/employees/{company-B-employee-id}` directly. Expected: not found. On `/company`, submit a company id that is not an active membership. Expected: “That company is not an active membership.” Evidence: the company name on the directory and the response to the foreign id. Do not treat a forged id that is rejected as a passed cross-company read.

**2. Two membership flags.** Use two existing memberships of the same company. One has `can_manage_employees` and not `can_manage_organization_masters`. The other has the master flag and not the employee flag. With the employee flag off, open `/employees/create`. Expected: forbidden, “Employee changes are not authorized.” With the master flag off, submit a department or designation change to `/api/departments` or `/api/designations`. Expected: 403, “Organization master changes are not authorized.” The opposite flag does not reverse either result. Evidence: both responses. There is no department or designation HTML screen. The master write is the JSON route.

**3. Directory and profile.** While company A is selected, open `/employees` and then `/employees/{company-A-employee-id}`. Expected: the directory and the profile show company A and that employee’s code and display name. Repeat the profile URL after switching to company B. Expected: not found. Evidence: both pages, including the company name.

**4. Display-name history and code.** As the employee-flag membership, open `/employees/{id}/edit`, change the display name, and save. Expected: the profile shows the new name, and Audit history shows When, Actor, Previous, and New. The employee code on the profile is unchanged. A submitted `employee_code` is rejected with “The employee code cannot be changed. Separation does not release a code for reuse.” Evidence: the history row and the unchanged code.

**5. Episodes and rejoin.** On an employee whose latest episode is not Open, open `/employees/{id}/episodes/create` and save a new episode. Expected: the previous episode remains, with its status and last working date, and the new episode is added. Editing the closed episode back to Open is refused with “This inactive or separated episode cannot be set back to Open.” A second Open episode is refused. Overlap, date order, and inactive pay are not rules to judge. Evidence: both episodes on the profile and the refusal text.

**6. Department and designation masters.** As the master-flag membership, read `/api/departments` and `/api/designations` for the active company, then create or rename one existing master through the matching POST or PUT. Expected: the row stays on that company. Open the employee profile. Expected: “No assignment is stored for this employee.” Changing the master does not change the employee. Evidence: the master response and the profile sentence.

**7. Excluded capabilities.** From the signed-in employee pages, confirm there is no branch write, no assignment form, and no navigation into attendance, leave, payroll, a role matrix, or migration. Expected: the profile states that no assignment is stored, and `routes/web.php` exposes departments and designations only as those JSON routes. Evidence: the profile sentence and the absence of those screens. Do not build a screen in order to show that it is absent.

### Execution record — prepared 2026-10-17

This record was prepared for manual execution. On 2026-10-17 the authorized sessions executed UAT-01, the defined checks in UAT-02, UAT-03, UAT-04, UAT-05, UAT-06, and UAT-07. The later session changed the Employee A display name, added one Open episode for Employee A, and renamed the Company A department. Those values are test inputs, not approved naming or date rules.

The running session checked on 2026-10-17, without a sign-in, is `http://127.0.0.1:8091`. Its process database is `proedge_new_hrms_uat` on `127.0.0.1:5432`, and debug is off. `/up` returned 200. `proedge_new_hrms` and `proedge_new_hrms_test` remain separate. The restore-verification database `proedge_new_hrms_restore_verify_20261017` remains present and is not the application database. It was not dropped.

Sign in at `http://127.0.0.1:8091/login` with the existing local fixture password. Do not change that password and do not copy it into this register. The accounts are `uat-a-only@example.test` (UAT Company A only; employee changes yes; master changes no), `uat-both@example.test` (UAT Company A and UAT Company B; employee changes yes; master changes no on both), and `uat-masters@example.test` (UAT Company A only; employee changes no; master changes yes).

Existing records, read from `proedge_new_hrms_uat` for this preparation:

| Record | Value |
| --- | --- |
| UAT Company A | `01M55ARSBX4G7J9YV0HCXQJB7E`, Active |
| UAT Company B | `01M55ARSC81JRC3K8BH0B9V8EX`, Active |
| Employee A | `UAT-A-001`, UAT Company A Employee, `01M55ARSCBTRBXX9CT8YPQJCXQ`, episode `01M55ARSCK04AN8Z73Y10MV9R0`, separated, joining 2024-01-15, last working date 2024-12-31 |
| Employee B | `UAT-B-001`, UAT Company B Employee, `01M55ARSCGR2H60GEES15MDC43`, episode `01M55ARSCP3W19KV1V0VXBG9RH`, Open, joining 2025-01-06, no last working date |
| Department | `UAT-DEPT`, UAT Department, Active, `01M55ARSCT86JTDHX8BB04ZMW6`, company A |
| Designations | No rows |

SEC-CORE-004 categories are not the permissions under test. The two membership flags are. Scenarios 4, 5, and 6 change fixture rows when they are executed. This preparation does not perform those changes.

Current fixture state on `proedge_new_hrms_uat` after UAT-04, UAT-05, and UAT-06, taken from those execution records. These values are test inputs, not approved naming or date rules. They were not re-read from the database for this reconciliation. Employee B and the designation count were not changed by those scenarios.

| Record | Current recorded value |
| --- | --- |
| Employee A display name | `UAT temporary display name 20261017` |
| Employee A code | `UAT-A-001` (unchanged) |
| Employee A episodes | Original separated episode `01M55ARSCK04AN8Z73Y10MV9R0` remains joining 2024-01-15, last working date 2024-12-31, updated 2026-10-17 16:22:54. One additional Open episode with joining date `2026-01-05` and no last working date |
| Department `UAT-DEPT` | Name `UAT temporary department name 20261017`, Active, still company A, id `01M55ARSCT86JTDHX8BB04ZMW6` |
| Employee B | Unchanged: `UAT-B-001`, UAT Company B Employee, Open episode `01M55ARSCP3W19KV1V0VXBG9RH`, joining 2025-01-06, no last working date |
| Designations | No rows |

#### UAT-01 — Company isolation

Purpose: the server-selected company is the boundary. Another company’s employee is not returned. A client company id does not move the record.

Preconditions and account: `uat-a-only@example.test`, membership of UAT Company A only. Employee A is in company A. Employee B is in company B. Do not add a company B membership to this account.

Steps:

1. Sign in and open `http://127.0.0.1:8091/employees`.
2. Confirm the directory heading names UAT Company A and that `UAT-B-001` is absent.
3. Open `http://127.0.0.1:8091/employees/01M55ARSCGR2H60GEES15MDC43` directly.
4. Open `http://127.0.0.1:8091/company` and submit company id `01M55ARSC81JRC3K8BH0B9V8EX`, which is not an active membership for this account.

Expected result: the directory is company A only. The company B employee is not found. The foreign company id is refused with “That company is not an active membership.” A rejected forged id is not a passed cross-company read.

Actual result: Signed in as `uat-a-only@example.test`. `/employees` showed UAT Company A and `UAT-A-001`, and did not show `UAT-B-001`. The Employee B address returned a page titled Not Found with heading 404. `/company` listed only UAT Company A. Submitting company id `01M55ARSC81JRC3K8BH0B9V8EX` returned to `/company` and included “That company is not an active membership.” The directory remained UAT Company A.

Tester: Cursor agent session

Execution date: 2026-10-17

Evidence reference: Observed at `http://127.0.0.1:8091` during this session. Pages: `/employees`, `/employees/01M55ARSCGR2H60GEES15MDC43`, and the `/company` response.

Status: PASS

#### UAT-02 — Two membership flags

Purpose: employee writes require `can_manage_employees`. Master writes require `can_manage_organization_masters`. One flag does not grant the other. Membership is not the SEC-CORE-004 role list.

Preconditions and accounts: both accounts are members of UAT Company A only. `uat-masters@example.test` has the master flag and not the employee flag. `uat-a-only@example.test` has the employee flag and not the master flag. The department row already exists. There is no department or designation HTML screen.

Steps:

1. Sign in as `uat-masters@example.test` and open `http://127.0.0.1:8091/employees/create`.
2. Sign out. Sign in as `uat-a-only@example.test`.
3. Submit a department change to `PUT http://127.0.0.1:8091/api/departments/01M55ARSCT86JTDHX8BB04ZMW6` for the active company. Send the existing name and status so a refused request does not rename the row.

Expected result: the create screen is forbidden and its heading is “Employee changes are not authorized.” The department request returns 403, “Organization master changes are not authorized.” The opposite flag does not reverse either result.

Actual result: Signed in as `uat-masters@example.test`. `/employees/create` returned HTTP 403 and the heading was “Employee changes are not authorized.” The page also showed “The role list is not approved.” That sentence was not scored. Signed in as `uat-a-only@example.test` and submitted `PUT /api/departments/01M55ARSCT86JTDHX8BB04ZMW6` with the existing name “UAT Department” and status Active. The response was HTTP 403 and the message was “Organization master changes are not authorized.” A later read of `proedge_new_hrms_uat` still showed that department as UAT Department, Active.

Tester: Cursor agent session

Execution date: 2026-10-17

Evidence reference: Observed at `http://127.0.0.1:8091/employees/create` and the department response during this session. The department row was re-read after the refusal.

Status: defined flag checks PASS

Unresolved for this scenario: the unauthorized page also says “The role list is not approved.” Step 20R section 6C has approved the role list and still excludes the role-matrix build. That sentence is not the expected result in the procedure above. Do not score it as a pass or a fail unless a later decision says so. Record the heading.

#### UAT-03 — Directory and profile

Purpose: directory and profile stay inside the active company.

Preconditions and account: `uat-both@example.test`, because the procedure requires the same profile address after a switch to company B. `uat-a-only@example.test` has no company B membership, so it cannot perform that switch. Select UAT Company A first. Employee A belongs to company A.

Steps:

1. Sign in and select UAT Company A.
2. Open `http://127.0.0.1:8091/employees`.
3. Open `http://127.0.0.1:8091/employees/01M55ARSCBTRBXX9CT8YPQJCXQ`.
4. Switch to UAT Company B with the company control, which posts to `/company`.
5. Open the same employee A profile address again.

Expected result: while company A is selected, the directory and profile show UAT Company A, code `UAT-A-001`, and display name “UAT Company A Employee.” After the switch to company B, that profile address is not found.

Actual result: Signed in as `uat-both@example.test` and selected UAT Company A. The directory showed UAT Company A and `UAT-A-001`. The profile heading was “UAT Company A Employee” and the line under it was “UAT Company A · UAT-A-001”. The company control was switched to UAT Company B. The directory then showed UAT Company B and `UAT-B-001`. Opening the Employee A address again returned a page titled Not Found with heading 404.

Tester: Cursor agent session

Execution date: 2026-10-17

Evidence reference: Observed at `http://127.0.0.1:8091` during this session. Pages: `/employees`, `/employees/01M55ARSCBTRBXX9CT8YPQJCXQ` before the switch, and the same address after the switch.

Status: PASS

#### UAT-04 — Display-name history and employee code

Purpose: a display-name change keeps the previous name, the new name, the actor, and the time. The employee code stays unchanged.

Preconditions and account: `uat-a-only@example.test`, company A, employee flag on. Employee A, whose current display name is “UAT Company A Employee” and whose code is `UAT-A-001`. The HTML edit form has a display-name field and no employee-code field. The code check submits `employee_code` on the existing update request.

Steps:

1. Open `http://127.0.0.1:8091/employees/01M55ARSCBTRBXX9CT8YPQJCXQ/edit`.
2. Change the display name, save, and record the value entered.
3. Open the employee A profile and its Audit history.
4. Submit the same update with an `employee_code` value included, through `PUT /employees/01M55ARSCBTRBXX9CT8YPQJCXQ` or `PUT /api/employees/01M55ARSCBTRBXX9CT8YPQJCXQ`.

Expected result: the profile shows the new display name. Audit history shows When, Actor, Previous, and New. The actor is the signed-in user. The profile code remains `UAT-A-001`. The submitted code is rejected with “The employee code cannot be changed. Separation does not release a code for reuse.” The screen also has an Action column. That column is not an additional pass condition beyond the four facts already stated.

Actual result: Signed in as `uat-a-only@example.test`. The temporary display name entered was `UAT temporary display name 20261017`. That text is test input, not a naming rule. The profile showed that name and code `UAT-A-001`. Audit history showed When `2026-10-17 17:48:21`, Actor `UAT Company A Only`, Previous `Display name: UAT Company A Employee`, and New `Display name: UAT temporary display name 20261017`. The Action column showed `Display name updated` and was not scored. `PUT /api/employees/01M55ARSCBTRBXX9CT8YPQJCXQ` with `employee_code` `UAT-A-CHANGED` returned HTTP 422 and “The employee code cannot be changed. Separation does not release a code for reuse.” The code remained `UAT-A-001`.

Tester: Cursor agent session

Execution date: 2026-10-17

Evidence reference: Observed at `http://127.0.0.1:8091/employees/01M55ARSCBTRBXX9CT8YPQJCXQ` and the employee update response during this session.

Status: PASS

The temporary display name is not an approved display-name rule. D1 remains unsigned.

#### UAT-05 — Episodes and rejoin

Purpose: rejoin adds an episode and leaves the previous episode. A non-Open episode is not set back to Open.

Preconditions and account: `uat-a-only@example.test`, employee flag on. Employee A’s only episode is separated, id `01M55ARSCK04AN8Z73Y10MV9R0`, last working date 2024-12-31. Do not use employee B for the rejoin. Employee B is already Open. Overlap, date order, and inactive pay stay unsigned and are not results to judge.

Steps:

1. Open `http://127.0.0.1:8091/employees/01M55ARSCBTRBXX9CT8YPQJCXQ/episodes/create`.
2. Save one new episode with status Open, a joining date the tester records, and no last working date.
3. Open the employee A profile and confirm the separated episode is still listed with its status and last working date.
4. Open `http://127.0.0.1:8091/employees/01M55ARSCBTRBXX9CT8YPQJCXQ/episodes/01M55ARSCK04AN8Z73Y10MV9R0/edit` and try to set that episode back to Open.
5. Try to save a second Open episode.

Expected result: the separated episode remains, and the new episode is added. Setting the separated episode back to Open is refused with “This inactive or separated episode cannot be set back to Open.” The current validation message continues: “Rejoining keeps the same employee and employee code, creates a new episode, and leaves this episode unchanged.” A second Open episode is refused. The current refusal text is “This employee already has an Open episode. Automatic separation or rejoining is not available.”

Actual result: Signed in as `uat-a-only@example.test`. The test joining date entered for the new episode was `2026-01-05`, with status Open and no last working date. That date was not judged. The profile still showed employee `01M55ARSCBTRBXX9CT8YPQJCXQ` and code `UAT-A-001`. The separated episode remained `2024-01-15`, last working date `2024-12-31`, status separated, and its updated time stayed `2026-10-17 16:22:54`. The new episode was Open, joining date `2026-01-05`, with no last working date. Setting the separated episode to Open was refused with “This inactive or separated episode cannot be set back to Open. Rejoining keeps the same employee and employee code, creates a new episode, and leaves this episode unchanged.” A second Open attempt used joining date `2026-02-02` and was refused with “This employee already has an Open episode. Automatic separation or rejoining is not available.” That second date was not stored. Overlap, date order, and inactive pay were not scored.

Tester: Cursor agent session

Execution date: 2026-10-17

Evidence reference: Observed at `http://127.0.0.1:8091/employees/01M55ARSCBTRBXX9CT8YPQJCXQ`, the separated-episode edit screen, and the second Open create screen during this session.

Status: PASS

The joining dates are test input. LIFE-OVERLAP, LIFE-ORDER, and LIFE-INACTIVE-PAY remain unsigned.

#### UAT-06 — Department and designation masters

Purpose: masters are company-scoped. A name or status change does not assign an employee.

Preconditions and account: `uat-masters@example.test`, master flag on, UAT Company A. The existing department is `UAT-DEPT`. Designations have no rows. Do not create a designation for this record. The procedure allows create or rename; the existing department is the rename target.

Steps:

1. Read `GET http://127.0.0.1:8091/api/departments` and `GET http://127.0.0.1:8091/api/designations` for the active company.
2. Rename the existing department with `PUT http://127.0.0.1:8091/api/departments/01M55ARSCT86JTDHX8BB04ZMW6`, sending a new name the tester records and status Active. Do not send `id`, `code`, or `company_id`.
3. Open `http://127.0.0.1:8091/employees/01M55ARSCBTRBXX9CT8YPQJCXQ`.

Expected result: the department row remains on UAT Company A. The designation list for that company is empty. The employee profile still says “No assignment is stored for this employee.” The master change does not change the employee.

Actual result: Signed in as `uat-masters@example.test` with UAT Company A selected. The temporary department name submitted was `UAT temporary department name 20261017`, with status Active. That text is test input, not a naming rule. `GET /api/departments` returned the existing row and `company_name` UAT Company A. `GET /api/designations` returned an empty data list. `PUT /api/departments/01M55ARSCT86JTDHX8BB04ZMW6` returned HTTP 200 with the same id, code `UAT-DEPT`, status Active, `company_name` UAT Company A, and the temporary name. No designation was created. Employee A’s profile still said “No assignment is stored for this employee,” and the code remained `UAT-A-001`.

Tester: Cursor agent session

Execution date: 2026-10-17

Evidence reference: Observed at `http://127.0.0.1:8091/api/departments`, `/api/designations`, the department update response, and `/employees/01M55ARSCBTRBXX9CT8YPQJCXQ` during this session.

Status: PASS

The temporary department name is not an approved department-name rule. No designation fixture was created.

#### UAT-07 — Excluded capabilities

Purpose: branch writes, employee assignment, the role matrix, attendance, leave, payroll, and migration are not part of the session.

Preconditions and account: `uat-a-only@example.test`, signed in, with employee A’s profile open. Do not build a screen in order to show that it is absent.

Steps:

1. From the signed-in employee pages, look for a branch write, an assignment form, and navigation into attendance, leave, payroll, a role matrix, or migration.
2. Read the Organization and location section on the employee A profile.
3. Compare that absence with `routes/web.php`: departments and designations appear there only as the JSON routes already named in UAT-06.

Expected result: those actions are absent from this UAT. The profile states that no assignment is stored. The route file does not add HTML screens for the excluded capabilities.

Actual result: While signed in as `uat-a-only@example.test`, the directory and Employee A profile showed Employees, Log out, Add employee, Edit name, Back to directory, Add episode, and Edit episode. No branch write, assignment form, or navigation to attendance, leave, payroll, a role matrix, or migration was present. The profile said “No assignment is stored for this employee.” `routes/web.php` was read and exposes departments and designations only as the JSON routes under `/api/departments` and `/api/designations`. It has no route for the excluded capabilities.

Tester: Cursor agent session

Execution date: 2026-10-17

Evidence reference: Observed at `http://127.0.0.1:8091/employees` and `/employees/01M55ARSCBTRBXX9CT8YPQJCXQ` during this session, compared with `next-hrms\routes\web.php` as it stood on that date.

Status: PASS

UAT-01, UAT-03, UAT-04, UAT-05, UAT-06, and UAT-07 are **PASS** for the approved checks recorded above. UAT-02 is **defined flag checks PASS**. The temporary names and the rejoin date are not approved rules. Overlap, date order, and inactive pay were not scored. All seven scenarios passed their defined, approved checks. This is not full release approval. No feature implementation was started.
