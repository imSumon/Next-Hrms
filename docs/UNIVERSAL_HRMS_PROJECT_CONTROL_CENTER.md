# Universal HRMS — Project Control Center

Checkpoint date: 2026-10-12. Development continues where the architecture and the approved rules already exist. An unsigned decision gates the feature that depends on it. The DEC-01 meeting has not occurred, and no new decision was approved. Rejoin stays the Step 20B rule. The validation message and the employee screens now state that rule. The latest reported test result is 33 passed and 198 assertions. This status correction did not rerun the suite.

This is the continuity record. It does not approve a policy, a load, or a new audit. The audit backlog is `docs/UNIVERSAL_HRMS_STEP21E_REMAINING_FUNCTIONAL_AUDIT_PLAN.md`.

No control-center file existed in `docs`, `legacy-reference\hrms\docs`, or `next-hrms\docs` before this checkpoint. `UNIVERSAL_HRMS_MIGRATION_DATA_CONTINUITY_ARCHITECTURE.md` is a migration design. It is not this checkpoint.

The parent `docs` folder and `legacy-reference\hrms\docs` were the earlier copies. `next-hrms\docs` now holds the documentation set prepared for the repository, including this file. This status correction does not change the parent `docs` folder.

## 1. Current checkpoint

Requirements analysis is **partially complete** (Step 21D). Functional audit of the catalog is not complete. No company is approved for employee migration.

| Fact | Evidence | State |
| --- | --- | --- |
| Catalog | Step 21A dump parse | 25 modules, 101 submodules, 416 controllers |
| Controller links | Step 21B | 397 pass the three logical checks. 19 fail. Five rows stay unresolved: 122, 123, 205, 213, 214 |
| Live `legacy_master` | Step 21C | Not performed. MySQL was not running and was not started |
| Behavior coverage | Step 21D, then AUD-01 through AUD-06 | 21D catalog grain is unchanged: 5 modules and 8 submodules partial, 91 inventory only, 0 catalog rows marked functionally analyzed. Six workflow audits were completed afterward and are not a reclassification of those 101 rows |
| Requirements text | `docs/UNIVERSAL_HRMS_REQUIREMENTS.md`, matching copy under `legacy-reference\hrms\docs` | 167 IDs after Stage 2 added FY-CONF-001. Previous count was 166. D01–D18 were not renumbered |
| Target companies | Steps 20N and 20O | Three `Active` rows. Not a migration approval |
| Employees and episodes | Step 20O | Tables exist. Counts were 0 |
| Attendance, leave, overtime, payroll tables | Step 21 matrix section 6 | Specified. Not created by the migrations run through Step 20 |
| Employee module tests | Reported after the create-form note | 33 passed, 198 assertions, from `php artisan test` in `next-hrms` against `proedge_new_hrms_test`. This checkpoint did not rerun that command |

The requirements file is in both `docs` and `legacy-reference\hrms\docs`. Stage 2 added FY-CONF-001. Counts are 111 CORE, 33 CONFIGURABLE, 4 CLIENT EXTENSION, 17 BUSINESS DECISION, 2 DEFERRED, total 167.

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

Unsigned: D1, D2, D3, D4 group dispositions, D6, the meaning of `dis`, episode overlap, date order, inactive-status pay, whether an inactive episode may store a last working date, the columns of an employee assignment, D01–D18, and FY-BOUNDARY. BD-D15 stays deferred. Blank approver and date fields in Step 20R are unsigned. Step 20R-1 capture fields are blank. Assignment columns are not defined. `location_nodes` and `organization_nodes` are not that definition.

A draft outside the register names D1 `obtain_verified_name`, D3 `verify_individually`, and D4 `case_by_case`, with the text `Anisur Rahman` and the date `2026-10-12`, conditions not recorded, status UNSIGNED, migration authorization NONE. That draft is not a signature. It was not copied into the register.

D6 has a later session proposal in `docs/UNIVERSAL_HRMS_DEC01_SESSION_PREPARATION.md`: HR verifies the joining date and the employment status before a legacy row creates an episode, and a record that fails that check is held. The approver and the decision date are not recorded. Step 20R still shows Option A as the earlier recommendation, and its decision field stays blank.

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
| 20R register | DECISION REGISTER PREPARED | Open fields blank |
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
| Stage 2 consolidation | CONSOLIDATION COMPLETE | Evidence register, traceability reconciliation, and this checkpoint. No policy was signed. No company was migration-approved |
| Stage 3 meeting package | PACKAGE PREPARED | Worksheet only. D1, D3, and D4 stay unsigned. No approval date was written |
| DEC-01 session preparation | SESSION PAPER PREPARED | Agenda for the existing worksheet. The meeting has not occurred. No approval field was filled |
| Incremental delivery plan | PLAN PREPARED | Decision gates. It named the validation-message correction. No policy was signed |
| Rejoin message and screen guidance | DONE AFTER STAGE 4C | The message and the detail and add-episode pages state the approved new-episode rejoin. The Stage 4C report still quotes the earlier message |
| Create-form episode note | DONE AFTER STAGE 4C | The form says the code and name create the identity, and that the three episode fields are the optional first episode. Validation was not changed |
| Documentation copy into `next-hrms\docs` | COPIED | 66 parent documents were copied. Step 20E was already identical and was not overwritten. The parent `docs` folder was not modified |
| Stage 4B authentication and membership | COMPLETE FOR THIS STAGE | Session login, `users`, and `company_memberships`. Writes need `can_manage_employees`. That flag is not a universal role. No company was migration-approved |
| Stage 4C employee lifecycle | COMPLETE FOR THIS STAGE | No new migration. Identity, code, and episode constraints stay. Setting a non-Open episode back to Open is refused. The Stage 4C report remains the record of that step, including its original message text. Payroll calculation and organization assignment are not started |

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
| Attendance, leave, overtime, and payroll behavior | BD-D02, BD-D03, BD-D07, BD-D09, BD-D11, BD-D12, BD-D13, BD-D14, BD-D16, BD-D17, and BD-D18 unsigned. BD-D15 deferred |
| Policy freeze | D01–D18 unsigned. FY-BOUNDARY unsigned |
| Live catalog comparison | Step 21C. Local MySQL was not running. Do not start it from this checkpoint |
| Universal role and permission matrix | SEC-CORE-004 is not implemented. `can_manage_employees` is only the employee-module grant |

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

## 5. Safety

Do not start MySQL from this record. Do not treat the company rows as a load approval. Do not merge identities or silently recode. Do not repeat the three closed-month audits unless a named gap in the Step 21E plan requires it.

## 6. Next document

`docs/UNIVERSAL_HRMS_INCREMENTAL_DELIVERY_AND_DECISION_GATES.md` records the delivery principle and the feature gates. `docs/UNIVERSAL_HRMS_DEC01_SESSION_PREPARATION.md` remains the unsigned session paper. Step 20R remains the register.

The validation-message correction, the rejoin guidance, and the create-form note are in the application. The latest reported result is 33 passed and 198 assertions. This status correction did not rerun `php artisan test`. Return links on the create and edit screens, and search text that contains an underscore or a percent sign, were identified later and are not implemented. Unsigned decisions stay feature gates. Assignment, payroll, attendance, leave, and employee migration stay unbuilt. SCOPE-01 stays deferred. DB-01 still waits until MySQL is already running. No company is migration-approved. The DEC-01 meeting remains the path for writing an authorized outcome into a blank Step 20R field.
