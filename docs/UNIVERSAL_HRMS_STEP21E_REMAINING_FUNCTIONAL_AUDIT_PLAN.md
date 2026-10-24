# Universal HRMS — Step 21E Remaining Functional Audit Plan

Planning record. AUD-01 through AUD-06 were completed after this plan was written. Their outcomes are recorded on those rows.

Coverage statuses are the Step 21D classifications. Inventory completion is not a functional audit. A finished requirements analysis is not migration approval.

## 1. Coverage used for the backlog

| Status | Count | Treatment in this plan |
| --- | ---: | --- |
| PARTIALLY ANALYZED modules | 5 | Close the named gap. Do not rerun the finished portion |
| PARTIALLY ANALYZED submodules | 8 | Same. Ids 4, 5, 16, 30, 52, 53, 54, 70 |
| INVENTORY ONLY submodules | 91 | Grouped. Not 91 separate audits |
| UNKNOWN parent | 2 | Submodules 36 and 71. Catalog comparison, not a new behavior theory |
| FUNCTIONALLY ANALYZED | 0 | Nothing to mark finished |
| NOT APPLICABLE | 0 | A catalog row is a legacy entry. Scope exclusion is a separate decision |

The five partial modules are organizationsettings, pims, payroll, leave, and attendance.

## 2. Work already completed

Do not repeat these unless a row below names the missing piece.

| Work | Evidence | Still open inside it |
| --- | --- | --- |
| Employee source schema | Step 20F | Live MySQL copy not compared |
| Employee identity census | Step 20G | D1–D6 unsigned. `dis` unexplained |
| Mapping and dry-run design | Steps 20E and 20J | Not executed |
| Company seed and review | Steps 20N and 20O | Not an employee approval |
| Proedge August 2026, VTL September 2026, Atlas September 2026 | Closed-month audits | CEVA has no operated month. Those months were not partial-month cases |
| Payroll consumption of those months | Same audits and the workbook | Formulas unsigned |
| Catalog inventory and link classification | Steps 21A and 21B | Live comparison. Five controller rows unresolved |
| Requirements text for the in-scope list | Active requirements file, 173 IDs: 116 CORE, 34 CONFIGURABLE, 4 CLIENT EXTENSION, 17 BUSINESS DECISION, 2 DEFERRED. The earlier inventory was 166. FY-CONF-001 made 167. LEAVE-CORE-005, LEAVE-CORE-006, LEAVE-CORE-007, LEAVE-CORE-008, MIG-CORE-011, and LEAVE-CONF-003 make 173. LEAVE-CORE-008 stays proposed | Policy values unsigned |
| Decision register and agenda | Steps 20R and 20R-1 | Capture fields blank |

Documentation updates, not new investigations:

- Read Steps 20N and 20O instead of Step 20L for the company rows.
- Read Steps 20A–20O instead of requirements section 46 for whether implementation has started.
- The requirements file is in the parent `docs` folder and has been copied into `next-hrms\docs`. The earlier sentence that the parent folder had no copy is stale.

## 3. Recommended order

Business decisions are not audits. They sit beside the audit list because several audits cannot close a requirement until an owner signs.

| Order | ID | Kind | Why this order |
| ---: | --- | --- | --- |
| 1 | DEC-01 | Owner decisions already listed | Unsigned values block requirements approval even if no new module is read |
| 2 | AUD-01 | Hourly leave against day leave | Inside the written leave scope. The day path is only partial |
| 3 | AUD-02 | Approval chain for leave and corrections | Samples exist. The step list is unsigned |
| 4 | AUD-03 | Server authorization | The security `shall` is ahead of the `appadmin` screens |
| 5 | DB-01 | Live catalog comparison | Blocked until MySQL is already running. Closes catalog confidence, not a policy |
| 6 | AUD-04 | Punch writer, `area`, `balid` | Integration scope. The product `shall` already refuses to name a vendor from a guess |
| 7 | AUD-05 | Conditional Atlas and CEVA month | Only if a result can add a policy or an extension |
| 8 | AUD-06 | Joiner or leaver month | Payable-day rule. The studied months were full months |
| 9 | SCOPE-01 | Inventory-only modules | Deferred until an owner adds them to the first release |

### DEC-01 — Sign or revise the open register

- Workflow: management decisions.
- Modules: employee, attendance, leave, overtime, payroll.
- Known: Step 20R lists each item. Proposals are not approvals. No merge and no silent recode stay approved.
- Missing: approver, date, and the chosen value for D1, D2, D3, D4 dispositions, D6, `dis`, overlap, date order, inactive pay, and D01–D18. BD-D15 stays deferred.
- Why it matters: the in-scope `shall` text cannot be frozen while these values are blank.
- Input: Step 20R and Step 20R-1. No new source reading is required to hold the meeting.
- Deliverable: completed capture fields, then a register update that copies only signed values.
- Priority: P0. Depends on the owners, not on MySQL.
- Blocks: requirements approval, development of those rules, testing, and migration.
- Exit: each item is approved, revised, or explicitly deferred, with a name and a date.

### AUD-01 — Hourly leave and day leave

- Workflow: leave.
- Modules: `hourlyleave` (id 30; submodules 96, 97, 98) and `leave` submodule 30.
- Known: day-status effects in Proedge August 2026, VTL September 2026, and Atlas September 2026. Hourly leave was not read.
- Missing: whether an hourly request writes a day result, a balance, or neither, and which companies use it.
- Why it matters: the leave requirement may be incomplete or may need a configurable duration.
- Where to read: `backend\modules\hourlyleave` and the day-leave writer already cited in the closed-month audits. Submodule 97 has zero catalog controllers. Its stored name has a leading space.
- Deliverable: a short addendum stating whether hourly leave changes LEAVE-CORE or stays outside the first release.
- Priority: P1. Depends on the day-leave findings. Does not require MySQL to start.
- Blocks: requirements approval for leave.
- Exit: the addendum either maps hourly leave onto an existing requirement or records it as out of the first release, with the files that were read.
- Outcome: **SOURCE AUDIT COMPLETE** in `docs/UNIVERSAL_HRMS_AUD01_HOURLY_LEAVE_FUNCTIONAL_AUDIT.md`. The hourly insert does not write attendance or payroll. It is not added to the first-release `shall` list. Company row counts remain unknown.

### AUD-02 — Approval chain

- Workflow: leave requests and attendance corrections.
- Modules: leave submodule 30, attendance submodule 53, payroll only where a correction reaches pay.
- Known: VTL samples rewrote displayed punches. A Proedge August approval did not change the stored day. Boss and grand-boss fields exist. BD-D16 and BD-D17 are unsigned.
- Missing: the ordered steps actually enforced, and one Proedge case where a correction did change the day, if such a month exists.
- Why it matters: approval length and editable fields are company policy, and the universal workflow cannot be tested without them.
- Where to read: the approval controllers already named in the architecture assessment. Do not rerun the three closed months.
- Deliverable: a step list marked as observed legacy behavior, plus the fields each step can change. The list stays unsigned until DEC-01.
- Priority: P1.
- Blocks: requirements approval for correction and approval, and later test design.
- Exit: observed steps and fields are written, and BD-D16 and BD-D17 are either evidenced for the decision meeting or marked still unknown.
- Outcome: **SOURCE AUDIT COMPLETE** in `docs/UNIVERSAL_HRMS_AUD02_LEAVE_AND_ATTENDANCE_APPROVAL_AUDIT.md`. Day-leave approval does not compare the session employee with `boss_id` or `gboss_id`. Balance changes only when status becomes 3. Attendance approval rewrites `attendance_detail` on the `atlas` branch and the `ceva` grand-boss branch. The `proedge` and `vtl` branch updates application status only. BD-D16 and BD-D17 remain unsigned. The VTL displayed-punch writer was not found in that action.

### AUD-03 — Server authorization

- Workflow: roles and module access.
- Modules: filesystem `appadmin`, which is not a catalog module. Catalog submodules 43 Access Log and 44 Company Users are inventory only.
- Known: requirements already say the server must check the action. Legacy `moduleAccess()` and hidden buttons are described.
- Missing: which roles exist, whether the action is rechecked, and how company scope is stored.
- Why it matters: security is in the first-release list. The screen audit is not.
- Where to read: `backend\modules\appadmin` controllers for users, modules, and company modules. Tables `company_modules`, `user_companies_modules`, and `user_companies_module_controllers` when a read-only session is already available.
- Deliverable: a role-and-scope note that either supports SEC-CORE or lists the gaps.
- Priority: P1. The file reading can start before MySQL. The table counts wait for DB-01.
- Blocks: test design for authorization. Does not block the decision meeting.
- Exit: every in-scope security `shall` is tied to a legacy behavior or marked as a new control with no legacy equivalent.
- Outcome: **SOURCE AUDIT COMPLETE** in `docs/UNIVERSAL_HRMS_AUD03_APPADMIN_SERVER_AUTHORIZATION_AUDIT.md`. Admin login is a custom session, not Yii RBAC. Fifteen appadmin controllers test `supadmin` and sometimes `admin`. Eight controllers do not. `moduleAccess()` checks a module grant and is commented out inside appadmin. Controller-grant rows drive grid buttons, not the action. The server-must-authorize-the-action requirement has no legacy action-level equivalent. Role names and tenant scope stay unsigned. Non-appadmin call sites were not exhaustively read.

### DB-01 — Live catalog comparison

- Workflow: catalog integrity.
- Ids: controllers 122, 123, 136, 276, 287, 300, 325, 351, 352, 365, 205, 213, 214, 267, 268, 270, 339, 433, 472. Submodules 36 and 71. Modules 7, 10, 11, 14, 15, 17, 26.
- Known: Step 21C. The data directory exists. The server was not running. No live row was read.
- Missing: MATCH, DIFFERENCE, LIVE-ONLY, or DUMP-ONLY for each id.
- Why it matters: the 25 / 101 / 416 baseline is a dump parse until the local database is compared.
- Method: read-only queries after MySQL is already running. This plan does not start MySQL.
- Deliverable: a completed Step 21C result, or a later addendum with the same id list.
- Priority: P1 for catalog confidence. It does not choose a policy.
- Blocks: treating the catalog as live-verified. Does not block DEC-01.
- Exit: each requested id is classified, and Step 21A or 21B is amended only where the live row differs.

### AUD-04 — Punch writer

- Workflow: raw punch ingestion.
- Modules: attendance submodules 52 and 54, and the shared `all_att_log` table. Catalog module `attendancealllog` has no children.
- Known: the shared log was used in the three months. Company was null on traced rows. `area` and `balid` are uninterpreted. In-repo upload did not write the traced closed-month rows.
- Missing: the writer, and the meaning of `area` and `balid`.
- Why it matters: an unknown writer can be an integration or an extension. It must not become a guessed vendor requirement.
- Where to read: a read-only trace of inserts into `all_att_log` when the server is already up. Source search only for writers that exist in the repo.
- Deliverable: named writer, or a confirmed unknown.
- Priority: P1. Depends on database availability for the trace. Source-only inspection can record that the repo path is not the traced writer, which the closed-month audits already say.
- Blocks: extension scope. Does not block the punch-immutability rule already approved.
- Exit: the writer is named from evidence, or the gap stays unknown without a vendor name attached.
- Outcome: **SOURCE AUDIT COMPLETE** in `docs/UNIVERSAL_HRMS_AUD04_PUNCH_WRITER_AREA_BALID_AUDIT.md`. In-repo writers are the appadmin file upload and `actionTransferlocationdata`. Neither writes `area` or `company`. On the transfer path, `balid` is `location_access_data.id`. `area` stays UNKNOWN. The closed-month rows are not produced by these writers. The external writer stays UNKNOWN. No vendor is named. PUNCH-APPROVED is unchanged. Withdrawal stays unsigned.

### AUD-05 — Atlas and CEVA engines

- Workflow: attendance processing.
- Modules: attendance submodule 53. Catalog actions include `processatlas` and `processceva`.
- Known: Atlas September 2026 was traced for day results. The CEVA engine and a separate Atlas operated-policy month were not.
- Missing: one closed month whose result would add a policy or an extension.
- Why it matters: three companies do not prove every engine.
- Deliverable: either “no new policy” with the month cited, or a new configurable or extension candidate.
- Priority: P2. Depends on a month that is already in a local dump. Do not invent the month.
- Blocks: requirements approval only if a new rule appears.
- Exit: the addendum states the month and whether the in-scope list changes.
- Outcome: **SOURCE COMPARISON COMPLETE** in `docs/UNIVERSAL_HRMS_AUD05_ATLAS_CEVA_ATTENDANCE_COMPARISON.md`. No CEVA operated month exists in the cited local databases. The Atlas and CEVA process actions share the Proedge action’s shape. The Atlas action hardcodes 31-minute late and early buffers. The CEVA action hardcodes the Proedge 16-minute late grace. The in-scope list does not change. Grace values stay unsigned.

### AUD-06 — Joiner and leaver pay

- Workflow: payroll payable days.
- Modules: payroll submodule 5, with attendance day results.
- Known: the studied months were full months. Payable-day basis is unsigned.
- Missing: a partial month.
- Why it matters: BD-D07 and payable days can change when employment does not cover the whole month.
- Deliverable: observed payable-day behavior for that month, still unsigned until DEC-01.
- Priority: P1 for the payable-day rule. P2 if no partial month is identified in the existing dumps.
- Blocks: requirements approval for payroll calculation.
- Exit: the month is cited, or the dumps are shown to contain no partial month and the gap stays open.
- Outcome: **SOURCE AUDIT COMPLETE** in `docs/UNIVERSAL_HRMS_AUD06_JOINER_LEAVER_MONTH_AUDIT.md`. No operated partial month was read. Attendance starts on an in-month `joining_date` and stops on an in-month `discontinue_date` only when `disconrinue` is 1. Salary prorates heads marked `partial` from the joining date and omits `disconrinue=1`. The divisor is 30 when `has_provident_fund` is greater than 0, otherwise the calendar length. Those figures are not approved defaults. BD-D07 stays unsigned.

### SCOPE-01 — Outside the written first release

These stay deferred. An owner must add one before it is audited for the first release.

| Group | Catalog ids | Submodules |
| --- | --- | --- |
| Recruitment | 8 | 80, 81, 82 |
| PMS and assessments | 6, 27, 32 | 32, 55, 89, 90, 91, 99, 100, 101, 102, 108, 109, 110, 111 |
| Provident fund and gratuity fund | 12, 13 | 38, 39, 59, 60, 83, 84, 85, 86, 87, 88 |
| Bills, lunch, documents, archive, personal files | 9, 21, 22, 24, 25, 31 | 35, 61, 62, 63, 65, 66, 67, 75, 76, 77, 103, 104, 105 |
| Named or unused extensions | 16, 18, 19, 20, 23, 29 | 47, 48, 49, 58, 68, 93, 94, 95. Module 23 has no submodule and no located code |

Payroll submodules that are not Payroll Basic, Overtime, or OT Management stay in this deferred set until an owner names them. That includes appraisal, tax, loans, lunch inside payroll, QTEC, Atlas Report, Kantar, GPOS, MRB, and the additional modules. Their ids are the inventory-only payroll ids other than 5, 16, and 70.

`wppfmanagement` (id 23) is unknown, not a confirmed workflow. Do not audit it until code or an owner identifies it.

## 4. Completion gates

These gates are separate.

| Gate | Evidence required | What does not satisfy it |
| --- | --- | --- |
| Legacy functional audit complete for the first-release list | AUD-01 through AUD-03 closed, and AUD-04 through AUD-06 either closed or explicitly left unknown. SCOPE-01 still deferred | A catalog of 25 and 101. A directory count |
| Universal requirements analysis complete | The in-scope `shall` text, signed values for the decisions that change those sentences, and acceptance checks for those values | Section 46’s “core ready” line alone. Inventory completion |
| First-release scope ready for approval | The in-scope list plus a signed statement that SCOPE-01 stays out, or a signed addition. Step 20R section 6D, approved 2026-10-17, is that signed statement. Every other row in this table is unchanged | An analyst observation that those modules are later. Section 6D does not mark a partial module functionally analyzed or ready to build, and it does not authorize an implementation milestone |
| Development authorized for one capability | The requirement for that capability is signed, its dependencies in DEC-01 are signed, and the build step names the tables | A proposed rule. Empty target tables |
| A company ready for employee migration | D1, D2, D3, D4 dispositions, and D6 signed, exception register signed, reconciliation result accepted, and a named company approval | The three company rows. A dry-run design |

## 5. Next action

AUD-01 through AUD-06 are complete as source audits. Stage 2 consolidated them. Do not repeat them unless a named contradiction appears.

Next action for unsigned policy: **formal decision capture** using `docs/UNIVERSAL_HRMS_STAGE3_DECISION_MEETING_PACKAGE.md` and the blank fields in Step 20R. SCOPE-01 stays deferred. DB-01 still waits until MySQL is already running. DB-01 is not complete.

`docs/UNIVERSAL_HRMS_INCREMENTAL_DELIVERY_AND_DECISION_GATES.md` is the later sequencing record. It keeps each unsigned decision on the feature that depends on it. Step 20R section 6D is the later signed scope statement. This plan does not fill any other Step 20R field. The DEC-01 meeting has not occurred.

The validation-message correction named in that plan was implemented afterward. The employee detail page and the add-episode page state the approved rejoin path. The create form states that the episode fields are the optional first episode. The latest reported test result is 33 passed and 198 assertions. This plan update did not rerun the suite. The Stage 4A and Stage 4C reports remain the records of those stages.

## 6. Statement

No database was connected. MySQL was not started. No schema, application code, or environment file was changed. Historical audits were not overwritten. Stage 2 did not sign a policy or a migration. Stage 3 prepared the meeting package and did not sign a policy or a migration. DB-01 is not complete. A later status correction in this `next-hrms\docs` copy updated the current requirements count and the requirements-file location. It did not change an audit finding or a decision field.

AUDIT PLAN PREPARED
