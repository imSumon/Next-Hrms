# Universal HRMS — Project Control Center

Checkpoint date: 2026-10-12. A documentation correction on 2026-10-16 records later foundation evidence and AUD-07. Development continues where the architecture and the approved rules already exist. An unsigned decision gates the feature that depends on it. The DEC-01 meeting has not occurred. AUTH-ARCH is approved subject to the Section 14 conditions. That approval does not approve any other decision. Rejoin stays the Step 20B rule. The validation message and the employee screens now state that rule. The rejoin-message correction is complete and is not the current next task.

The result reported at the 2026-10-12 correction was 33 passed and 198 assertions. A later focused run reported 50 passed and 500 assertions. This correction did not rerun either suite. Automated tests are not signed-in browser UAT. A working direction is not an owner signature. An audit result does not authorize a future design. A migration design is not migration approval.

This is the continuity record. It does not approve a policy, a load, or a further audit. The audit backlog is `docs/UNIVERSAL_HRMS_STEP21E_REMAINING_FUNCTIONAL_AUDIT_PLAN.md`. AUD-07 records a trace that was already complete. It does not lift a gate.

No control-center file existed in `docs`, `legacy-reference\hrms\docs`, or `next-hrms\docs` before this checkpoint. `UNIVERSAL_HRMS_MIGRATION_DATA_CONTINUITY_ARCHITECTURE.md` is a migration design. It is not this checkpoint.

The parent `docs` folder and `legacy-reference\hrms\docs` were the earlier copies. `next-hrms\docs` now holds the documentation set prepared for the repository, including this file. This status correction does not change the parent `docs` folder.

## 1. Current checkpoint

Requirements analysis is **partially complete** (Step 21D). Functional audit of the catalog is not complete. No company is approved for employee migration.

| Fact | Evidence | State |
| --- | --- | --- |
| Catalog | Step 21A dump parse | 25 modules, 101 submodules, 416 controllers |
| Controller links | Step 21B | 397 pass the three logical checks. 19 fail. Five rows stay unresolved: 122, 123, 205, 213, 214 |
| Live `legacy_master` | Step 21C | Not performed. MySQL was not running and was not started |
| Behavior coverage | Step 21D, then AUD-01 through AUD-06, plus AUD-07 | 21D catalog grain is unchanged: 5 modules and 8 submodules partial, 91 inventory only, 0 catalog rows marked functionally analyzed. AUD-01 through AUD-06 are workflow audits. AUD-07 records approval callers and payroll reads. None of them reclassifies those 101 rows |
| Requirements text | Active file `next-hrms\docs\UNIVERSAL_HRMS_REQUIREMENTS.md` | 173 IDs after the 2026-10-17 leave-balance and encashment rows. The earlier Stage 2 count was 167. The parent `docs` folder and `legacy-reference\hrms\docs` were not updated. D01–D18 were not renumbered |
| Target companies | Steps 20N and 20O | Three `Active` rows. Not a migration approval |
| Employees and episodes | Step 20O | Tables exist. Counts were 0 |
| Attendance, leave, overtime, payroll tables | Step 21 matrix section 6 | Specified. Not created by the migrations run through Step 20 |
| Employee module tests | Reported after the create-form note | 33 passed, 198 assertions, from `php artisan test` in `next-hrms` against `proedge_new_hrms_test`. That figure stays the earlier full-suite report. This correction did not rerun that command |
| Later focused tests | Reported after the organization-master review | 50 passed, 500 assertions. Covers the session, directory, profile, display-name history, and department and designation masters in that focused set. This correction did not rerun it. It is not signed-in browser UAT |

The parent `docs` folder and `legacy-reference\hrms\docs` still carry the Stage 2 count: 111 CORE, 33 CONFIGURABLE, 4 CLIENT EXTENSION, 17 BUSINESS DECISION, 2 DEFERRED, total 167. The active requirements file now adds LEAVE-CORE-005, LEAVE-CORE-006, LEAVE-CORE-007, LEAVE-CORE-008, LEAVE-CONF-003, and MIG-CORE-011: 116 CORE, 34 CONFIGURABLE, 4 CLIENT EXTENSION, 17 BUSINESS DECISION, 2 DEFERRED, total 173. LEAVE-CORE-008 is proposed and is not an approved pay rule. Those folders were not synchronized.

Company rows verified in Step 20O, local PostgreSQL `proedge_new_hrms`:

| source_key | Name | id |
| --- | --- | --- |
| 24 | Pro-edge Associates Limited | `01M4KG2364VXPYVZCM7HZTGPB1` |
| 20 | Viyellatex Group | `01M4KG2384JEKYAE5SXC927QDF` |
| 10 | Atlas Copco Bangladesh Ltd. | `01M4KG238KHQSQTQVFAA0YAYVG` |

`company_code` and `source_system` are null. These rows do not authorize an employee load.

## 2. Decisions

Already in force, from Step 20R section 2: ULID identity, company-scoped employee code, no merge, no silent recode, tenant catalog ids 24, 20, and 10, the reconciliation contract, architecture AP-01 through AP-05, and raw-punch immutability with a new day-result version.

Also in force, from Step 20B section 5 and LIFE-CORE-008: rejoining keeps the same employee identity and employee code, inserts a new episode, and leaves the previous episode in place.

AUTH-ARCH is approved in Step 20R section 6B, subject to all Section 14 conditions. Approver: Anisur Rahman, Owner — Universal HRMS Project. Approval date: 2026-10-17. That approval does not itself satisfy SEC-CORE-003 or approve the role list, and does not authorize implementation.

SEC-CORE-004 is approved in Step 20R section 6C as the initial four-category role list and permission split, by the same approver and date, subject to the conditions in that section. The two flags stay until a separate tested mapping. The unresolved items in that section stay open. SEC-CORE-003 is not approved. This decision does not authorize implementation.

The first-release scope boundary is approved in Step 20R section 6D, by the same approver and date. It is the shared platform plus catalog modules `organizationsettings`, `pims`, `payroll`, `leave`, and `attendance`, with only Payroll Basic, Overtime, and OT Management inside payroll. SCOPE-01 stays deferred. The catalog of 25 modules, 101 submodules, and 416 controllers stays on the full roadmap. Partial analysis is not implementation readiness. This approval does not authorize an implementation milestone and does not change any business-rule, security, UAT, migration, or release gate. The evidence register is the later UAT result record. UAT-01, UAT-03, UAT-04, UAT-05, UAT-06, and UAT-07 are **PASS**. UAT-02 is **defined flag checks PASS**. That result does not score the informational role-list sentence and does not approve the role matrix. All seven scenarios passed their defined, approved checks. This is not full release approval. The evidence register records the current UAT fixture state after UAT-04, UAT-05, and UAT-06: Employee A’s temporary display name, one additional Open episode with joining date `2026-01-05`, and department `UAT-DEPT`’s temporary name. Employee B and the empty designation list were unchanged. Those values are test inputs, not approved rules. The recorded session is `http://127.0.0.1:8091` on `proedge_new_hrms_uat`. The restore-verification database `proedge_new_hrms_restore_verify_20261017` remains separate and was not dropped. Tester, date, actual results, and evidence references are the 2026-10-17 Cursor agent session entries in the evidence register. These results do not authorize the next implementation milestone.

Unsigned: D1, D2, D3, D4 group dispositions, D6, the meaning of `dis`, episode overlap, date order, inactive-status pay, whether an inactive episode may store a last working date, the columns of an employee assignment, D01–D18, and FY-BOUNDARY. BD-D15 stays deferred. Blank approver and date fields in Step 20R are unsigned. Step 20R-1 capture fields are blank. Assignment columns are not defined. `location_nodes` and `organization_nodes` are not that definition.

A draft outside the register names D1 `obtain_verified_name`, D3 `verify_individually`, and D4 `case_by_case`, with the text `Anisur Rahman` and the date `2026-10-12`, conditions not recorded, status UNSIGNED, migration authorization NONE. That draft is not a signature. It was not copied into the register.

D6 has a later session proposal in `docs/UNIVERSAL_HRMS_DEC01_SESSION_PREPARATION.md`: HR verifies the joining date and the employment status before a legacy row creates an episode, and a record that fails that check is held. The approver and the decision date are not recorded. Step 20R still shows Option A as the earlier recommendation, and its decision field stays blank.

`docs/UNIVERSAL_HRMS_ORGANIZATION_MASTERS_PERMISSION_DECISION.md` records Option A as the Stage 5D working direction: `can_manage_organization_masters` is a separate membership flag, default false, and it does not reuse `can_manage_employees`. That file is not an external approval and has no approver signature. Step 20R was not edited.

## 3. Task register

### Completed

| Task | Report status | What completion means |
| --- | --- | --- |
| 20A foundation | STEP 20A COMPLETE | Companies, branches, location nodes, organization nodes |
| 20B lifecycle design | APPROVED FOR IMPLEMENTATION | Design review only |
| 20C lifecycle tables | IMPLEMENTATION COMPLETE | Employees and episodes migrated and probed |
| 20D lifecycle review | REVIEW PASSED | Catalog matched. No employee insert |
| 20E mapping design | DESIGN COMPLETE | Reconciliation not executed |
| 20G identity audit | IDENTITY AUDIT COMPLETE | Offline dump census |
| 20J dry-run design | DRY-RUN DESIGN COMPLETE | Not executed |
| 20K approval package | APPROVAL PACKAGE PREPARED | Hashes recorded. Decisions not signed |
| 20N company seed | COMPANY SEED COMPLETE | Three rows, then ULID correction |
| 20O seed review | INTEGRITY REVIEW PASSED | Read-only reconfirm. Employees and episodes 0 |
| 20Q management paper | MANAGEMENT REPORT COMPLETE | Does not select decisions |
| 20R register | DECISION REGISTER PREPARED | Migration and attendance fields remain blank. AUTH-ARCH is approved subject to all Section 14 conditions. Section 6D approves the first-release scope boundary only |
| 20R-1 agenda | AGENDA PREPARED | Capture fields blank |
| 21 traceability | TRACEABILITY MATRIX PREPARED | Specified versus approved kept separate |
| 21 coverage report | COVERAGE REPORT PREPARED | Analysis partial |
| 21A inventory | INVENTORY DOCUMENTED FROM THE MASTER DUMP | Not a behavior audit |
| 21B catalog integrity | CATALOG INTEGRITY BASELINE DOCUMENTED | Dump classification |
| 21D gap assessment | REQUIREMENTS ANALYSIS PARTIALLY COMPLETE | Coverage grain |
| AUD-01 hourly leave | SOURCE AUDIT COMPLETE | Does not write attendance or payroll. Not added to the first-release `shall` list. Company row counts unknown |
| AUD-02 leave and attendance approval | SOURCE AUDIT COMPLETE | Day-leave balance deducts only at status 3. Atlas and CEVA grand-boss approval can rewrite a day. `proedge` and `vtl` in that action change application status only. BD-D16 and BD-D17 stay unsigned |
| AUD-03 appadmin authorization | SOURCE AUDIT COMPLETE | Session level checks on some admin actions. Eight controllers have no level test. `moduleAccess()` is module-scoped and is commented out inside appadmin. Action-level server checks are not present. Role names stay unsigned |
| AUD-04 punch writer | SOURCE AUDIT COMPLETE | Two in-repo writers. Neither writes `area` or `company`. Closed-month rows with `area` text are not from those writers. External writer UNKNOWN. No vendor named |
| AUD-05 Atlas and CEVA processing | SOURCE COMPARISON COMPLETE | Same engine shape. Atlas action uses 31-minute late and early buffers. Proedge and CEVA actions use 16-minute late grace. No CEVA operated month. No core change |
| AUD-06 joiner and leaver month | SOURCE AUDIT COMPLETE | Attendance can start on `joining_date` and, when `disconrinue` is 1, stop on an in-month `discontinue_date`. Salary prorates some heads from the joining date and omits `disconrinue=1`. No operated partial month. BD-D07 stays unsigned |
| Employee separation and rejoining | SOURCE AUDIT COMPLETE | Evidence register F-39. One PIMS form writes the separation fields on the same employee row. Type labels are not separate workflows. Clearing the flag overwrites that row. D3, D6, and BD-D07 stay unsigned. No final-pay, forfeiture, or rehire rule was chosen |
| Employee organization assignments | SOURCE AUDIT COMPLETE | Evidence register F-40. Department, designation, branch, boss, and location overwrite the current employee row. Approver strings and the organogram table are separate stores. EMP-CORE-004 and the reporting-chain specification already require effective dates. Assignment columns stay undefined as a decision. BD-D03 and BD-D17 stay unsigned |
| Employee import and data integrity | SOURCE AUDIT COMPLETE | Evidence register F-41. Create, CSV, recruitment conversion, and appraisal upload use `save(false)`. The employee code has no unique key in the Atlas and Proedge schemas. NFR-CORE-006, CODE-APPROVED, and NOMERGE-APPROVED already apply. The seven stored Viyellatex duplicate-code groups stay unresolved exceptions |
| Viyellatex duplicate-code worksheet | PREPARED. NOT A DECISION | Step 20G section 11. Fourteen rows, seven codes. Every disposition is unresolved. No row was selected, merged, or recoded |
| Cross-company authorization | SOURCE AUDIT COMPLETE. SCREEN TEST BLOCKED | Evidence register F-42. The session database is the boundary between Proedge, Viyellatex, and Atlas. Inside that database, primary-key employee, leave, appraisal, department, and salary lookups do not apply the local company. The Atlas dump contains other local companies on departments and salary. The 2026-10-17 screen test did not run: no authorized non-production Atlas session was available. SEC-CORE-003 stays unsigned. AUTH-ARCH and SEC-CORE-004 are unchanged |
| Punch identity mapping | SOURCE AUDIT COMPLETE | Evidence register F-43. The card map is looked up by employee code and keeps one row when several exist. Viyellatex cards `126384` and `132058` are each stored three times. Atlas has no duplicate card in its 65-row map. Proedge’s full duplicate count is unpublished. PUNCH-CORE-009 stays the controlling requirement. No shared-card overlap was approved |
| Employee foundation owner UAT | PARTIAL | Evidence register execution record, tester Cursor agent session, execution date 2026-10-17. UAT-01, UAT-03, UAT-04, UAT-05, UAT-06, and UAT-07 are **PASS**. UAT-02 is **defined flag checks PASS**. The informational role-list sentence was not a pass or fail condition, and the role matrix is not approved. All seven scenarios passed their defined, approved checks. This is not full release approval. The evidence register records the current UAT fixture state after those writes. The temporary names and rejoin date are not approved rules. Recorded session: `http://127.0.0.1:8091` on `proedge_new_hrms_uat`. Restore-verification database `proedge_new_hrms_restore_verify_20261017` remains separate. SEC-CORE-004 is not an implemented permission set. Attendance, leave, payroll, the role matrix, migration, and release stay outside it. This row does not authorize an implementation milestone |
| First-release scope boundary | APPROVED. NOT AN IMPLEMENTATION MILESTONE | Step 20R section 6D. Approver: Anisur Rahman, Owner — Universal HRMS Project. Approval date: 2026-10-17. Shared platform plus `organizationsettings`, `pims`, `payroll`, `leave`, and `attendance`. Inside payroll, only Payroll Basic, Overtime, and OT Management. SCOPE-01 stays deferred. The full catalog stays on the roadmap. No business-rule, security, UAT, migration, or release gate is changed |
| L2 Leave-Position Recording Engine | G7 AUTHORIZED 2026-10-18. IMPLEMENTATION VERIFIED | Step 20R section 6I. Approver: Anisur Rahman, Owner. Approval date: 2026-10-18. Records proposed or excepted positions and generic movements only. Empty company-scoped leave-type list. Writer grants are `leave_position_writers` rows, not membership flags. Confirmation fields stay unused. Schema applied to `proedge_new_hrms_test` and `proedge_new_hrms` only. Focused `php artisan test` on 2026-10-18: LeavePositionRecordingTest 4 passed, 56 assertions; with AuthenticationAndMembershipTest and OrganizationMasterJsonTest, 23 passed, 281 assertions. Does not sign LEAVE-OPENING or authorize approved-balance use, booking, payroll, attendance, migration, or release |
| L2 Leave-Position Recording Operator UI | G7-UI AUTHORIZED 2026-10-18. IMPLEMENTATION VERIFIED | Step 20R section 6J. Approver: Anisur Rahman / Product Owner/CTO. Approval date: 2026-10-18. Company-scoped `/session` screens for the already-built L2 recording engine: leave-type list/create/rename/status (name and status only); proposed/excepted leave-position list/create/update; movement list/create, including reversals that add a new row. Reuses login, active-company context, the `leave_position_writers` grant, JSON APIs, confirmation refusal, `usable_as_approved_balance=false`, and audit. Follows the existing `/session` RealSession pattern. No new schema, flags, or permission model. Writer grants remain CLI-only. Focused verification on 2026-10-18, without a signed-in browser pass: `npm run build` in `frontend` passed (`tsc --noEmit` and the Vite build). `php artisan test --compact` on LeavePositionOperatorUiContractTest, LeavePositionRecordingTest, and AuthenticationAndMembershipTest passed 25 tests and 284 assertions against `proedge_new_hrms_test`, with transaction rollback. Limitations: the employee picker loads at most 50 directory pages (750 employees) and says so when that limit is reached; a movement shows the acting user id because the movement JSON has no display name; position detail takes the company name from the leave-type list because the position JSON has no company name. Does not overwrite section 6I. Does not sign LEAVE-OPENING or authorize approved-balance use, booking, payroll, attendance, migration, or release |
| L2 Immutable Punch Recording Engine | AUTHORIZED 2026-10-18. NOT IMPLEMENTED | Step 20R section 6K. Approver: Anisur Rahman / Product Owner/CTO. Approval date: 2026-10-18. Management decision: Authorize the bounded Immutable Punch Recording Engine (L2) milestone only. Preserve all unresolved dependencies and exclusions. The signed fields are repeated in section 6 of this file, in the governance worksheet, and in gates section 5. This row does not overwrite the G7 or G7-UI rows. It does not implement the engine |
| Stage 2 consolidation | CONSOLIDATION COMPLETE | Evidence register, traceability reconciliation, and this checkpoint. No policy was signed. No company was migration-approved |
| Stage 3 meeting package | PACKAGE PREPARED | Worksheet only. D1, D3, and D4 stay unsigned. No approval date was written |
| DEC-01 session preparation | SESSION PAPER PREPARED | Agenda for the existing worksheet. The meeting has not occurred. No approval field was filled |
| Incremental delivery plan | PLAN PREPARED | Decision gates. It named the validation-message correction. No policy was signed |
| Rejoin message and screen guidance | DONE AFTER STAGE 4C | The message and the detail and add-episode pages state the approved new-episode rejoin. The Stage 4C report still quotes the earlier message |
| Create-form episode note | DONE AFTER STAGE 4C | The form says the code and name create the identity, and that the three episode fields are the optional first episode. Validation was not changed |
| Documentation copy into `next-hrms\docs` | COPIED | 66 parent documents were copied. Step 20E was already identical and was not overwritten. The parent `docs` folder was not modified |
| Stage 4B authentication and membership | COMPLETE FOR THIS STAGE | Session login, `users`, and `company_memberships`. Writes need `can_manage_employees`. That flag is not a universal role. No company was migration-approved |
| Stage 4C employee lifecycle | COMPLETE FOR THIS STAGE | No new migration. Identity, code, and episode constraints stay. Setting a non-Open episode back to Open is refused. The Stage 4C report remains the record of that step, including its original message text. Payroll calculation and organization assignment are not started |
| Stage 5D department and designation masters | COMPLETE FOR THIS STAGE | Separate company-scoped tables. `can_manage_organization_masters` defaults to false and does not grant employee changes. Name and status changes are audited. No employee assignment and no legacy load. The permission file is working direction, not an external signature. No company was migration-approved |
| Display-name editing and history | COMPLETE FOR THIS SLICE | The profile can change `display_name` inside the active company. History shows the previous name, the new name, the actor, and the time. Episode editing was not added. No separate stage-report file was created for this slice |
| Active-company directory label and logout | COMPLETE FOR THIS SLICE | The directory shows the server-selected company name. Logout is a POST that ends the session. Client `company_id` does not select the company. No separate stage-report file was created for this slice |
| AUD-07 approval callers and payroll reads | SOURCE TRACE COMPLETE | Records where status `s` is submitted, the Proedge and Viyellatex status-`1` gap, and the salary-register reads for company ids 24, 20, and 10. Does not rewrite AUD-02. Does not authorize attendance or payroll design. BD-D16 and BD-D17 stay unsigned |
| Leave-balance opening requirements | REQUIREMENTS RECORDED. NOT A DECISION | Evidence register F-31 through F-38. LEAVE-CORE-005 through LEAVE-CORE-008, LEAVE-CONF-003, and MIG-CORE-011. LEAVE-CORE-008 is proposed. LEAVE-OPENING stays unsigned. F-38 counts Atlas head 398 and `additional_module4` and does not link them to the encashment ledger. No opening formula or encashment pay rule was chosen. No company was migration-approved |
| AUD-08 ESS identity on profile, files, and payslip | SOURCE TRACE COMPLETE FOR THIS BATCH | Session employee id binds profile, personal files, and the studied payslip reads. Password change and two posted-id updates are not that binding. Catalog grain is unchanged. Does not authorize implementation |
| AUD-09 ESS document identity | SOURCE TRACE COMPLETE FOR THIS BATCH | Document lists can filter by the session employee. View, update, delete, and upload by id do not. Not runtime verified. Does not authorize implementation |
| AUD-10 ESS payroll identity and one PIMS update | SOURCE TRACE COMPLETE FOR THIS BATCH | Named ESS certificate and tax reads use the session employee. PF actions were not opened. PIMS `actionUpdate` loads an employee by id on the session company connection and saves the posted model. Not runtime verified. Does not authorize implementation |
| AUD-11 ESS dashboard and overtime identity | SOURCE TRACE COMPLETE FOR THIS BATCH | Dashboard lists can be session-scoped. Detail pages load the requested employee id. ESS overtime writes use posted ids on the `vtlw` connection. Not runtime verified. Does not authorize implementation |
| AUD-12 ESS admin identity and payroll reads | SOURCE TRACE COMPLETE FOR THIS BATCH | `clientAdmin` list is company-filtered. Profile, payslip, and salary card load the requested employee id and read salary. Not runtime verified. Does not authorize implementation |
| AUD-13 ESS hourly leave identity | SOURCE TRACE COMPLETE FOR THIS BATCH | Create and delete use the session employee. Approval updates a request by id and then changes the balance. Not runtime verified. Does not authorize implementation |
| AUD-14 ESS ticketing identity | SOURCE TRACE COMPLETE FOR THIS BATCH | Ticket create does not set the employee in the controller. `actionDeleteleave` deletes a leave row only when its employee matches the session. Not runtime verified. Does not authorize implementation |
| AUD-15 ESS lunch identity | SOURCE TRACE COMPLETE FOR THIS BATCH | Lunch screens list the session employee number. Insert, update, and both deletes use the posted or requested id and do not compare it. Not runtime verified. Does not authorize implementation |
| AUD-16 ESS mobile-bill identity | SOURCE TRACE COMPLETE FOR THIS BATCH | The mobile-bill list uses the session employee number. Insert, print, and delete use the posted or requested id and do not compare it. Not runtime verified. Does not authorize implementation |
| Legacy portal visual evidence | BLOCKED PENDING AUTHORIZED LEGACY PORTAL ACCESS | VIS-01 through VIS-12 remain planned. The evidence folder has no PNG. The new application is not the legacy portal. Does not authorize implementation |
| AUD-17 ESS bill management identity | SOURCE TRACE COMPLETE FOR THIS BATCH | Create stores the session employee number. Delete and both approval actions update by requested id. The list screens filter; those writes do not. Not runtime verified. Does not authorize implementation |
| AUD-08 through AUD-17 identity consolidation | CONSOLIDATION COMPLETE FOR THIS BATCH | `next-hrms\docs\UNIVERSAL_HRMS_ESS_IDENTITY_RISK_REGISTER.md`. Patterns are proposals. No gate was signed. Catalog grain is unchanged |
| AUD-18 ESS Atlas bills reads and delete | SOURCE TRACE COMPLETE FOR THIS BATCH | Lists and delete use the session employee. Delete also requires status `New`. Detail views load a bill by id. Not runtime verified. Does not authorize implementation |
| AUD-19 ESS probation progress identity | SOURCE TRACE COMPLETE FOR THIS BATCH | The ESS list filters by the session employee number. Insert, review, view, and delete use a posted or requested id. Delete has no status test. Not runtime verified. Does not authorize implementation |
| AUD-20 ESS assessment 360 identity reads | SOURCE TRACE COMPLETE FOR THIS BATCH | The employee's own list and view use the session employee. Rater lists do too. The four rater detail views load an entry by the requested id and do not repeat the role test. Submit actions were not traced. Not runtime verified. Does not authorize implementation |
| ESS read-identity coverage matrix | COVERAGE MATRIX COMPLETE FOR THIS BATCH | `next-hrms\docs\UNIVERSAL_HRMS_ESS_READ_IDENTITY_COVERAGE_MATRIX.md`. Twenty read actions were opened. Prior audits were reused and not re-counted. The catalog has no action total. Not runtime verified. Does not authorize implementation |
| AUD-21 DefaultController and Atlas read gaps | SOURCE TRACE COMPLETE FOR THIS BATCH | Eleven `DefaultController` actions classified. Eight previously unread Atlas views load by id or by paid status. Approve history filters the first approver. The file cabinet has no employee column. `searchorgo` treats `id` and `emp_id_no` as different filters. `responder_id` is written more than one way. Not runtime verified. Does not authorize implementation |
| Authorization requirements consolidation | PROPOSED REQUIREMENTS NOTE | `next-hrms\docs\UNIVERSAL_HRMS_AUTHORIZATION_REQUIREMENTS.md`. Evidence is AUD-08 through AUD-21. Nine proposed requirements. Existing decision ids stay unsigned. Not runtime verification. Does not authorize implementation |
| Authorization owner review | PREPARED FOR THE OWNER. NOT A DECISION | `next-hrms\docs\UNIVERSAL_HRMS_AUTHORIZATION_OWNER_REVIEW.md`. Groups the nine requirements into four baseline controls. Leaves SEC-CORE-003, SEC-CORE-004, DOC-07, DOC-08, PRB-04, and the access brief unsigned. Does not decide BD-D16, BD-D17, or D1–D6. Does not authorize implementation |
| Authorization decision agenda | PREPARED FOR THE OWNER. NOT A DECISION | `next-hrms\docs\UNIVERSAL_HRMS_AUTHORIZATION_DECISION_AGENDA.md`. Five questions that block an authorization architecture: the initial role model, a platform administrator, company membership, company administration, and the company boundary with a reserved narrower limit. Recommendations are proposals. Does not sign SEC-CORE-003, SEC-CORE-004, or the access brief. Does not decide BD-D16, BD-D17, or D1–D6. Does not authorize implementation |
| Authorization decision readiness checklist | OWNER INTENT RECORDED. NOT A FORMAL SIGNATURE | `next-hrms\docs\UNIVERSAL_HRMS_AUTHORIZATION_DECISION_READINESS_CHECKLIST.md`. Questions 1, 3, 4, and 5 marked ACCEPT. Question 2 marked MODIFY. Stage 5D is reconciled as two layers and is not withdrawn. Does not sign SEC-CORE-003, SEC-CORE-004, or the access brief. Does not change the implementation gates. Does not authorize implementation |
| Authorization decision-record delta | OWNER INTENT RECORDED FOR CONFIRMATION. NOT STEP 20R | `next-hrms\docs\UNIVERSAL_HRMS_AUTHORIZATION_DECISION_RECORD_DELTA.md`. Records the five-point direction, the details still requiring confirmation, and the procedures still unwritten. Step 20R was not edited. Gate rows were not edited. Does not authorize implementation |
| Initial role and permission matrix proposal | PROPOSAL FOR OWNER REVIEW. NOT A DECISION | `next-hrms\docs\UNIVERSAL_HRMS_INITIAL_ROLE_PERMISSION_MATRIX_PROPOSAL.md`. Recommends accepting four role categories in principle, and an explicit employee-directory permission separate from own-profile access. The two flags stay. The mapping is not approved. The procedure checklist separates owner decisions from implementation safeguards. Does not sign SEC-CORE-003 or SEC-CORE-004. Does not change Stage 5D, Step 20R, or the implementation gates. Does not authorize implementation |
| Authorization pre-sitting decision package | PROPOSED OWNER INTENT AND STEP 20R DRAFT. NOT A SIGNATURE | `next-hrms\docs\UNIVERSAL_HRMS_AUTHORIZATION_PRE_SITTING_DECISION_PACKAGE.md` section 11. Eight draft entries. Approver, role, and date are `PENDING OWNER`. The register was not filled. No entry is ready for formal signing without further owner input. Does not authorize implementation |

### Partial

| Task | Report status | Limit |
| --- | --- | --- |
| 20F employee schema | PARTIALLY VERIFIED | Dump schema. MySQL was not running |
| 20H and 20I decisions | BUSINESS DECISIONS REQUIRED / STILL REQUIRED | Options proposed. Not approved load rules |
| 20M seed design | BUSINESS DECISION REQUIRED at that time | Status was later set to `Active` in 20N. The design file was not rewritten |
| Closed-month behavior | Existing Proedge, VTL, and Atlas audits | Three months. Not the whole catalog |
| Requirements section 46 | CORE and configuration model ready. Policy values decision required | Its lines “architecture not started” and “development not started” predate Steps 20A–20O |
| Stage 4A employee management | PARTIAL AT THAT STEP | Directory and episodes exist. Stage 4B now gates them with login and membership. The 4A report remains the record of that step |

### Blocked

| Task | Block |
| --- | --- |
| Employee migration and dry-run execution | D1, D2, D3, D4 dispositions, D6, and a signed exception register. Reconciliation not executed. No company is migration-approved |
| Attendance, leave, overtime, and payroll behavior | BD-D02, BD-D03, BD-D07, BD-D09, BD-D11, BD-D12, BD-D13, BD-D14, BD-D16, BD-D17, and BD-D18 unsigned. BD-D15 deferred. AUD-07 does not lift this block |
| Approved leave balance and a confirmed leave-balance load | LEAVE-OPENING unsigned. A proposed position may be specified. A legacy amount is not a confirmed balance |
| Policy freeze | D01–D18 unsigned. FY-BOUNDARY unsigned |
| Live catalog comparison | Step 21C. Local MySQL was not running. Do not start it from this checkpoint |
| Universal role and permission matrix | The initial role list is approved as SEC-CORE-004. The matrix is not implemented, and this approval does not authorize the build. `can_manage_employees` remains the employee-module grant. `can_manage_organization_masters` remains the master-write flag until a separate tested mapping |

### Unverified

| Item | Why |
| --- | --- |
| Live `legacy_master` versus the dump | Step 21C opened no connection |
| Dump versus the Laragon data directory | Not compared |
| Step 1A–1E reports | Not found. The 99-submodule count and the “all 416 links valid” claim are superseded by Steps 21A and 21B |

## 4. Status lines that are not the current checkpoint

| Older statement | Newer evidence |
| --- | --- |
| Step 20A says Step 20B was not started | 20B, 20C, and 20D were completed afterward |
| Step 20L: TARGET MAPPING NOT VERIFIED, companies count 0 | 20N and 20O seeded and reviewed the three company rows. D1–D6 were still unsigned in 20L and remain unsigned |
| Step 20M: company status unselected | 20N stored `Active` after the user confirmed it |
| Step 21D: 0 catalog rows functionally analyzed | AUD-01 through AUD-06 audited named workflows. They did not reclassify the 101 submodule rows |
| Requirements section 46: architecture and development not started | Foundation and lifecycle tables exist. Policy values are still unsigned. Stage 2 left those two lines in place and marked them as the original wording |
| Step 21D opening: control center absent | This file is that control center |
| Control center after Stage 4C, and the Stage 4C report, say the validation message treats rejoin as unapproved | The message now says an inactive or separated episode cannot be set back to Open, and that rejoining keeps the same employee and employee code, creates a new episode, and leaves that episode unchanged. The detail page and the add-episode page state the same path and still say a second Open episode is refused. The Stage 4A and Stage 4C reports were not rewritten |
| Incremental delivery plan section 5 names the rejoin-message correction as the next development task | That correction is the completed row above. It is not the current next task. The plan file was not rewritten by this correction |
| This control center, at the 2026-10-12 correction, reports 33 passed and 198 assertions as the latest result | That figure remains the earlier full-suite report. The later focused report is 50 passed and 500 assertions. Neither run was repeated here |
| This control center and gates section 5 said all seven employee-foundation UAT scenarios stay NOT VERIFIED. Step 20R section 6D point 5 originally printed that sentence as the status at approval | Point 5 now states that the sentence recorded the 2026-10-17 approval-time status. Later results stay in the evidence register: UAT-01, UAT-03, UAT-04, UAT-05, UAT-06, and UAT-07 are PASS for their approved checks. UAT-02 is defined flag checks PASS. The temporary names and rejoin date are not approved rules. The role-list sentence was not scored. The role matrix is not approved. Section 6D’s approval status was not changed |

## 5. Safety

Do not start MySQL from this record. Do not treat the company rows as a load approval. Do not merge identities or silently recode. Do not repeat the three closed-month audits, AUD-02, or AUD-07 unless a named contradiction appears. Do not treat AUD-07 as an attendance or payroll specification.

## 6. Next document

`docs/UNIVERSAL_HRMS_INCREMENTAL_DELIVERY_AND_DECISION_GATES.md` records the delivery principle and the feature gates. Section 5 records the implemented and verified L2 Leave-Position Recording Operator UI. The next implementation checkpoint is the L2 Immutable Punch Recording Engine, authorized in Step 20R section 6K and restated in gates section 5. It is not implemented. `docs/UNIVERSAL_HRMS_DEC01_SESSION_PREPARATION.md` remains the unsigned session paper. Step 20R remains the register. Section 6D of that register is the approved first-release scope boundary. Section 6I is the named L2 leave-position recording engine. Section 6J is the named L2 Operator UI. Section 6K is the named L2 Immutable Punch Recording Engine. `docs/UNIVERSAL_HRMS_FIRST_RELEASE_DESIGN_BOUNDARY.md` is the design boundary. `docs/UNIVERSAL_HRMS_FIRST_RELEASE_FUNCTIONAL_DESIGN.md` is the functional design.

The parent `docs` folder was not updated.

Registration status: **APPROVED.** This is the named implementation milestone for the bounded L2 Immutable Punch Recording Engine only. It does not overwrite G1–G6, section 6I, or section 6J. It is not implemented by this documentation change. PUNCH-APPROVED is the immutability rule named in the dependencies below. It is not this authorization. The leave-position approvals are not this authorization.

| Field | Content |
| --- | --- |
| Named milestone | Immutable Punch Recording Engine |
| Layer | L2 |
| Bounded scope | Record raw punch events for one employee inside the active company, through one engine with two ingestion paths. Path A is an authorized human operator: manual entry of one raw event, and upload of a raw punch file. Path B is device integration: a controlled submission of raw punch events from an attendance device or an integration service. Both paths store the same event. Each accepted event stores event time, ingestion time, punch identity, and a named source. The named source is not the submitting actor. A human who uploads a device export remains the submitting actor; the file’s source stays the named source. A device or integration service that submits directly is a different submitting actor from that human. The original event stays readable. Later processing must not rewrite it. The engine detects a duplicate event. It rejects a punch identity that is active for two employees in the same company at the same time unless a documented overlap is already recorded. The overlap list stays empty. Company comes from an explicit company context, not from an empty company field and not from legacy `area`. Writer authorization is an explicit server check. It is not a new membership boolean and not a role-matrix change. |
| Explicit exclusions | Daily attendance calculation or status. Grace, lateness, shift selection, cutoff, and worked-day calculation. A day-result row. Punch correction, editing, withdrawal, or deletion. Leave booking, approved balances, accrual, and encashment. Payroll integration and payroll calculation. Employee assignment changes. Production migration or release. A new role matrix, a new membership flag, permission screens, and flag replacement. Naming a vendor, a device protocol, a source-name catalog, a duplicate key, or an overlap for cards `126384` and `132058`. Copying a legacy file layout, `area`, or `balid` into the product rule. |
| Required dependencies | Signed G1 and G2, as documentation rules only. They do not authorize this engine. Existing login and server-selected active company. Employee identity inside that company. PUNCH-CORE-001 through PUNCH-CORE-009, PUNCH-CORE-006, PUNCH-CORE-007, DI-CORE-005, and PUNCH-APPROVED, as the shape and the immutability rule only. SEC-CORE-001 for a state-changing action. AUDIT-CORE-001 for the submitting actor and time on an accepted event. NFR-CORE-006 and NFR-CORE-007 for a failed import and a replaceable external feed. |
| Unresolved prerequisites | The human writer is not named. The device or integration credential is not named. Functional design §3.6 names no attendance user and no SEC-CORE-004 category for attendance. `can_manage_employees` and `can_manage_organization_masters` do not grant this engine. The duplicate match fields are not defined. PUNCH-CONF-002, how a detected duplicate is treated, is unsigned. PUNCH-CONF-001, which sources a company accepts, is unsigned. PUNCH-CONF-004, whether one employee may hold multiple active punch identities, is unsigned. The overlap list is empty. No device protocol is identified. EXT-CLIENT-004 stays inactive until a protocol is identified. AUD-04: the external writer is UNKNOWN, `area` is UNKNOWN, and no in-repository device API was found. |
| Gates that remain deferred | BD-D01, BD-D02, BD-D03, BD-D04, BD-D05, BD-D13, BD-D16, PUNCH-WITHDRAW, SEC-CORE-003, FY-BOUNDARY, SCOPE-01, D1–D6, LEAVE-OPENING, and the role-matrix build. ATT-CORE-001 stays outside this milestone because it is day calculation. |
| Management's actual decision | Authorize the bounded Immutable Punch Recording Engine (L2) milestone only. Preserve all unresolved dependencies and exclusions. |
| Approver name/role | Anisur Rahman / Product Owner/CTO |
| Approval date | 2026-10-18 |
| Conditions or exceptions | This entry authorizes only the named L2 engine. It does not change G1–G6, Step 20R §6I, or Step 20R §6J. It does not sign PUNCH-WITHDRAW or any other unsigned attendance policy. The unresolved prerequisites above stay unresolved. |

**Ingestion paths.** Path A and path B are the two ingestion paths accepted with this milestone. They are not built.

| Path | What the documents support | What stays a dependency |
| --- | --- | --- |
| A. Authorized human operator | An ingestion boundary before calculation (PUNCH-CORE-001). A person may enter one event or submit a file of raw events. The signed-in user is the submitting actor. The named source is a separate stored fact (PUNCH-CORE-003). | Who that person is. The file layout. Which sources the company accepts (PUNCH-CONF-001). The legacy bracket-delimited upload is evidence from AUD-04. It is not the product format. |
| B. Device or integration service | An external feed stays replaceable behind that same boundary (NFR-CORE-007). A named protocol may be enabled only after it is identified (EXT-CLIENT-004). The device or service is the submitting actor. The named source remains distinct from that actor. | The protocol, the vendor, the credential, and how that caller is bound to one company. AUD-04 did not find a device client in the repository. `area` is not a source and is not a company. |

Both paths store event time and ingestion time as different facts (PUNCH-CORE-006). Neither path may assign the company from a blank company field (PUNCH-CORE-007).

**Identity and duplicates.**

- A punch identity maps to an employee inside one company. The identity may differ from the employee code (PUNCH-CORE-008).
- The same punch identity must not be active for two employees in that company at the same time unless a documented overlap is already recorded (PUNCH-CORE-009, DI-CORE-005). No overlap is recorded. Cards `126384` and `132058` are not decided here.
- A duplicate event must be detected (PUNCH-CORE-005). The fields that make two events the same event are not specified. PUNCH-CONF-002 leaves the treatment configurable and unsigned. This milestone does not choose a key, and it does not choose reject, quarantine, or skip. A failed import must not complete as a partial silent write (NFR-CORE-006).
- This milestone does not choose how many active identities one employee may hold (PUNCH-CONF-004).

**Immutability.** PUNCH-APPROVED and PUNCH-CORE-004 keep an accepted event. A later day result, if a later milestone is signed, must be a separate record and must not rewrite the event (ATT-CORE-002 read with functional design §3.6). This milestone does not create that day result. ATT-CORE-001 is calculation and stays out.

PUNCH-WITHDRAW is unsigned. This milestone includes no withdrawal, deletion, or edit. BD-D16’s correction mechanism does not include the raw punch as an editable field. The field list itself stays empty.

**Writer prerequisite.** No writer is named by this approval. Before implementation of a path, the owner must name both of the following, or must name one path and defer the other:

1. The human operator allowed to enter or upload raw punches for one company.
2. The device or integration credential allowed to submit raw punches for one company.

Those two actors are not the same fact as the named source. Naming either one is not a new role, not a membership boolean, and not a mapping of the two existing flags. The leave-position writer list is a different milestone. It is not reused here.

This entry does not sign BD-D01 through BD-D05, BD-D13, BD-D16, PUNCH-WITHDRAW, SEC-CORE-003, FY-BOUNDARY, SCOPE-01, D1–D6, or LEAVE-OPENING. It does not reopen AUD-01 through AUD-06. It does not start MySQL. No company is migration-approved.

`docs/UNIVERSAL_HRMS_AUD07_APPROVAL_CALLERS_AND_PAYROLL_READS.md` is the record of the completed approval-caller and payroll-read trace. It does not rewrite AUD-02 and it does not sign a gate.

The validation-message correction, the rejoin guidance, and the create-form note are in the application. That correction is finished. It is not the current next task. The 2026-10-12 report of 33 passed and 198 assertions remains the earlier full-suite figure. The later focused report is 50 passed and 500 assertions. This correction did not rerun `php artisan test`. Those runs are not signed-in browser UAT. Return links on the create and edit screens were identified later and are not implemented. Employee directory search passes the term through `EmployeeRegistry::escapeLike`, which escapes backslash, `%`, and `_`. That method is the available evidence for those three characters. This correction does not claim that every search path is fully verified. Unsigned decisions stay feature gates. D1–D6, BD-D07, BD-D13, BD-D14, BD-D16, and BD-D17 stay unsigned, as do the other open gates in section 3. Department and designation masters are in the application under the Stage 5D working direction. That direction is not an owner signature. The masters do not assign employees. The L2 leave-position recording engine is built. The L2 Leave-Position Recording Operator UI is implemented and verified. Assignment, payroll, attendance operational use, approved leave balances, and employee migration stay unbuilt. SCOPE-01 stays deferred. DB-01 still waits until MySQL is already running. No company is migration-approved. The DEC-01 meeting remains the path for writing an authorized outcome into a remaining blank Step 20R field.

AUTH-ARCH in `docs/UNIVERSAL_HRMS_STEP20R_MANAGEMENT_DECISION_REGISTER.md` is **APPROVED**, subject to all Section 14 conditions. SEC-CORE-004 in section 6C of that register is **APPROVED** as the initial role list, subject to its conditions. Approver for both: Anisur Rahman, Owner — Universal HRMS Project. Approval date for both: 2026-10-17. The SEC-CORE-004 gate row now records that list and still blocks implementation of the role matrix. Those approvals do not authorize the Immutable Punch Recording Engine. Section 6K does, within the bounded scope repeated above. It is not implemented. AUTH-ARCH and SEC-CORE-004 do not authorize attendance calculation, payroll, roles, narrower scope, or migration. The two flags stay until a separate tested mapping. Stage 5D's original sentence that there is no company-administrator role remains the implementation rule. SEC-CORE-003 stays unsigned. The blocked row for the universal role and permission matrix stays blocked for the build. BD-D16, BD-D17, and D1–D6 stay for their own sittings. The user-to-employee link, self-service writes, the display-name gap, operator identity, password-reset and account-lock policy, the exceptional-access list, duration, and reviewer, and the no-assigner evidence and approver stay open. Section 6D of that register approves the first-release scope boundary only. Section 6I authorizes only the bounded L2 Leave-Position Recording Engine. Section 6J authorizes only the bounded L2 Leave-Position Recording Operator UI. That UI is implemented and verified. Section 6K authorizes only the bounded L2 Immutable Punch Recording Engine. It preserves the unresolved prerequisites and exclusions repeated above. It does not sign PUNCH-WITHDRAW or any other unsigned attendance policy. It is not implemented. The next scoped leave-balance task named in gates section 5 remains owner confirmation of LEAVE-OPENING. That confirmation is not an implementation milestone and is not signed. `docs/UNIVERSAL_HRMS_LEAVE_OPENING_OWNER_DECISION_WORKSHEET.md` is the unsigned owner-review worksheet for those questions. It does not fill Step 20R and does not authorize a build. Provident fund, gratuity, recruitment, and PMS content stay deferred. Visual capture stays blocked until an authorized legacy session exists. Implementation of attendance calculation, payroll, roles, narrower scope, and migration stays unauthorized. Raw punch recording is authorized only inside section 6K and is not implemented.
