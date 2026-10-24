# Universal HRMS — Step 21D Requirements Coverage Gap Assessment

Status: **REQUIREMENTS ANALYSIS PARTIALLY COMPLETE**. It cannot be declared complete.

No company is approved for employee migration. The three seeded company rows do not authorize a data load.

## 1. Sources and where they live

`docs/UNIVERSAL_HRMS_PROJECT_CONTROL_CENTER.md` was absent when Step 21D was written. The active control center and the active requirements file are now in `next-hrms\docs`. This count correction does not rewrite the catalog grain below.

| Document | Location |
| --- | --- |
| Requirements, 173 IDs | Active file `next-hrms\docs\UNIVERSAL_HRMS_REQUIREMENTS.md`. Appendix A. The count when this assessment was written was 166. FY-CONF-001 made 167. LEAVE-CORE-005, LEAVE-CORE-006, LEAVE-CORE-007, LEAVE-CORE-008, MIG-CORE-011, and LEAVE-CONF-003 make 173. LEAVE-CORE-008 stays proposed |
| Step 21 matrix, coverage report, 21A, 21B, 21C | Both `docs` folders, matching copies |
| Step 20E reconciliation design | Also in `next-hrms\docs`. That folder does not hold the Step 21 set |
| Architecture, workbook, closed-month audits, Step 20Q, Step 20R, Step 20R-1 | Both `docs` folders |

This step did not repeat the closed-month audits, the employee dump census, or the catalog parse. Live `legacy_master` was not opened. Step 21C stands: MySQL was not running, and the live comparison is pending.

## 2. Coverage baseline

Current catalog findings, from the offline dump in Steps 21A and 21B:

| Item | Current finding |
| --- | --- |
| `modules` | 25 rows. 18 `Yes`, 7 `No` |
| `sub_modules` | 101 rows |
| `controllers` | 416 rows |
| Logical link checks | 397 pass. 19 fail |
| Live `legacy_master` | Not compared |

The older figure of 99 submodules is not a current finding. The claim that all 416 relationships were valid is not a current finding.

Filesystem counts stay separate: 25 backend directories whose names do not start with `00`, and 346 PHP controllers. That directory list includes `appadmin` and `reports`. The catalog includes `averydata` and `wppfmanagement`.

## 3. How a catalog row was classified

| Status | Meaning in this assessment |
| --- | --- |
| INVENTORY ONLY | The row is in the Step 21A catalog. Behavior, rules, and exceptions were not investigated for that row |
| PARTIALLY ANALYZED | A prior audit examined part of the workflow. The rest of the submodule was not closed |
| FUNCTIONALLY ANALYZED | Source, data, rules, and exceptions for the submodule were closed. None reached this status |
| REQUIREMENTS DEFINED | Used for a target capability whose `shall` text exists. Not used to mark a catalog row complete |
| AWAITING BUSINESS DECISION | A policy value is unsigned. Applied in section 5, on top of partial evidence |
| UNKNOWN / INSUFFICIENT EVIDENCE | The catalog link itself is unresolved, or no code and no audit exist |
| NOT APPLICABLE | Not used. A catalog row is evidence that the legacy entry exists. Absence from the requirements in-scope list is a scope observation, not proof the legacy module is inapplicable |

Companies named below are the companies in the existing audits: Proedge, Viyellatex (VTL), and Atlas. Three-company evidence does not capture every catalog company.

## 4. Module coverage

| id | mname | active | Catalog controllers | Status | What the existing evidence covers |
| ---: | --- | --- | ---: | --- | --- |
| 1 | organizationsettings | No | 26 | PARTIALLY ANALYZED | Company boundary and the three seeded rows. Org screens, banks, users, and organogram were not audited |
| 2 | pims | No | 15 | PARTIALLY ANALYZED | Employee-row census from the three company dumps. PIMS screens, upload, and reports were not audited |
| 3 | payroll | Yes | 104 | PARTIALLY ANALYZED | Salary amounts and paid overtime in three studied months. The other payroll submodules were not audited |
| 4 | leave | Yes | 21 | PARTIALLY ANALYZED | Leave-day effects in those months. The request chain, uploads, and CMA parent were not closed |
| 5 | attendance | Yes | 29 | PARTIALLY ANALYZED | Three closed months for day result, shift source, punches, and approval samples. Not every controller |
| 6 | pms | Yes | 48 | INVENTORY ONLY | No behavior audit |
| 8 | recruitment | Yes | 7 | INVENTORY ONLY | Directory and catalog only. The requirements in-scope list does not name recruitment |
| 9 | documents | Yes | 4 | INVENTORY ONLY | No behavior audit |
| 12 | pfmanagement | Yes | 40 | INVENTORY ONLY | An Atlas September PF amount match did not audit this module |
| 13 | gfmanagement | Yes | 22 | INVENTORY ONLY | No behavior audit |
| 16 | atlasbills | Yes | 4 | INVENTORY ONLY | Named in the workbook as an optional extension, off by default. Bill workflows were not audited |
| 18 | nmsdata | No | 2 | INVENTORY ONLY | Named as a client extension. Calculation was not audited |
| 19 | tonicdata | No | 4 | INVENTORY ONLY | No behavior audit |
| 20 | averydata | No | 2 | INVENTORY ONLY | Catalog row. Code sits under the unused-module folder. Not audited |
| 21 | mobilebillmanagement | Yes | 2 | INVENTORY ONLY | No behavior audit |
| 22 | lunchmanagement | Yes | 5 | INVENTORY ONLY | No behavior audit |
| 23 | wppfmanagement | Yes | 0 | UNKNOWN / INSUFFICIENT EVIDENCE | Active catalog row. No submodule, no catalog controller, and no located code |
| 24 | personalfiles | Yes | 13 | INVENTORY ONLY | No behavior audit |
| 25 | billmanagement | Yes | 7 | INVENTORY ONLY | No behavior audit |
| 27 | assessment360degree | Yes | 11 | INVENTORY ONLY | No behavior audit |
| 28 | attendancealllog | No | 0 | INVENTORY ONLY | The shared punch log was studied under attendance. This module row has no children. Its Yii folder has a default controller that is not a catalog row |
| 29 | proedgedata | No | 8 | INVENTORY ONLY | No behavior audit |
| 30 | hourlyleave | Yes | 8 | INVENTORY ONLY | Named as an open gap against day-leave. Workflow not read |
| 31 | dataarchive | Yes | 3 | INVENTORY ONLY | No behavior audit |
| 32 | multiraterassessment | Yes | 15 | INVENTORY ONLY | No behavior audit |

Module totals: 5 partially analyzed, 19 inventory only, 1 unknown, 0 functionally analyzed.

`appadmin` and `reports` are filesystem modules, not catalog rows. `appadmin` holds the catalog-maintenance screens and was not behavior-audited. Security requirements exist as text. `reports` has one PHP controller. Timecard and payslip behavior is described inside the attendance and payroll audits, not as an audit of that directory.

## 5. Submodule coverage

Eight submodules are partially analyzed. Two have an unresolved parent and no behavior audit. The other 91 are inventory only.

| id | Parent | name | Controllers | Status | Evidence already on file |
| ---: | --- | --- | ---: | --- | --- |
| 4 | pims | PIMS | 7 | PARTIALLY ANALYZED | Employee identity census for Proedge, VTL, and Atlas. Screens not audited. D1–D6 unsigned |
| 5 | payroll | Payroll Basic | 10 | PARTIALLY ANALYZED | Salary rows for the three studied months. Formulas unsigned |
| 16 | payroll | Overtime | 2 | PARTIALLY ANALYZED | Calculated hours and paid amounts were separated in those months. The formula is unsigned |
| 30 | leave | Leave | 12 | PARTIALLY ANALYZED | Day-status effects in those months. The application chain was not closed |
| 52 | attendance | Attendance Manage | 10 | PARTIALLY ANALYZED | Shifts, holidays, and day rows in the three months. Winner rule unsigned |
| 53 | attendance | Attendance Process & Reports | 13 | PARTIALLY ANALYZED | The studied engines and day results. Atlas and CEVA process actions were not month-traced |
| 54 | attendance | Attendance Data Upload & Download | 8 | PARTIALLY ANALYZED | Card map and shared log samples. The external writer is unknown |
| 70 | payroll | OT Management | 4 | PARTIALLY ANALYZED | Same studied-month overtime contrast. Eligibility and rate are unsigned |

| id | Parent | name | Controllers | Status |
| ---: | --- | --- | ---: | --- |
| 36 | module 10, row absent | Notification | 2 | UNKNOWN / INSUFFICIENT EVIDENCE |
| 71 | NULL | CMA | 3 | UNKNOWN / INSUFFICIENT EVIDENCE |

For both unknown rows, Step 21B classified the catalog defect. It did not establish a business rule. Submodule 71’s controller module and grants point at leave. The parent cell is still NULL. The CMA balance action string is not in the current PHP tree.

Every other submodule is **INVENTORY ONLY**: no source-and-database behavior investigation, no company coverage, no identified business rules, and no target acceptance criteria written for that submodule.

Those inventory-only ids are 1, 2, 3, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 32, 35, 38, 39, 42, 43, 44, 45, 46, 47, 48, 49, 51, 55, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 72, 73, 74, 75, 76, 77, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, and 111.

Submodule totals: 8 partially analyzed, 2 unknown, 91 inventory only, 0 functionally analyzed.

Names that look client-specific, including QTEC, Atlas Report, Kantar, GPOS, MRB, PAL-D, and the additional payroll modules, stay inventory only. They are not approved client extensions.

## 6. Target requirements by area

The requirements document’s in-scope list is the target coverage set. Section 46 says core capability text and the configuration model are ready, and policy values are not. That section also says architecture and development were not started. Later Steps 20A–20O implemented the company, employee, and episode tables. Section 46’s architecture and development lines are older than that work. They are not rewritten here.

| Area | Legacy evidence | Requirement text | Approved target | Implemented | Still open | Class of the open item |
| --- | --- | --- | --- | --- | --- | --- |
| Company boundary | Three reference companies and the catalog crosswalk | Written | Tenant mapping and the no-alias product split | Three local company rows | Branch override unsigned. Other catalog companies not traced | CORE text. CONFIGURABLE values unsigned |
| Organization tree | Not loaded | Written at high level | Not approved as a loaded tree | Tables exist empty for branches and nodes | Screen and data audit | CORE text. Evidence partial |
| Employee identity | Dump census, three companies | Written | ULID identity, company-scoped code, no merge, no silent recode | Empty `employees` table | D1, D2, D3, D4 dispositions, D6, `dis` | BUSINESS DECISION |
| Lifecycle | Flag pairs counted. No episode table in legacy | Written | Open, separated, and the empty-table constraints | Empty `employment_episodes` | Overlap, date order, inactive pay, both-flags, `dis` | BUSINESS DECISION |
| Shift, roster, holiday | Three studied months | Written | One shift per date is specified. Winner is not chosen | Not built | BD-D03 | CONFIGURABLE value unsigned |
| Punch ingestion | Shared log samples | Written | Immutability and company-from-card are specified | Not built | Writer, `area`, `balid`, withdrawal authority | CORE text. UNKNOWN writer |
| Attendance day | Three months | Written | Separate day result is specified | Not built | BD-D02, BD-D01, BD-D04, BD-D05, BD-D07 | BUSINESS DECISION |
| Leave day mark | Day effects in those months | Written | Leave may mark the day | Not built | BD-D06, hourly leave, full approval chain | BUSINESS DECISION |
| Correction and approval | Samples in the studied months | Written | Four stored values. Punch not edited | Not built | BD-D16, BD-D17. A Proedge correction that changed the day was not seen | BUSINESS DECISION |
| Overtime | Hours and money separated | Written | Calculated, payable, and paid stay apart | Not built | BD-D08 through BD-D12 | BUSINESS DECISION |
| Payroll snapshot and payslip | Salary rows have no attendance snapshot. Payslip reads salary | Written | Snapshot does not follow a later recalculation | Not built | BD-D18, joiner and leaver pay | BUSINESS DECISION |
| Period close | Lock flags did not close the studied months | Written | A lock flag is not proof of close | Not built | BD-D13, BD-D14. BD-D15 deferred | BUSINESS DECISION. DEFERRED for reopen authority |
| Security | Requirement text from `moduleAccess()` behavior | Written | Server-side checks are specified | Not built | `appadmin` role screens not audited | CORE text. Evidence partial |
| ESS and reports | Requirements and a mobile assessment exist | Written | Specified | Not built | 24 ESS controllers and the reports directory were not page-audited | CORE text. Evidence partial |
| Notifications | Named as a gap | Written at high level | Not approved as a delivery rule | Not built | Missing-punch email unconfirmed. Catalog module 10 absent | UNKNOWN |
| Migration | Reconciliation contract written | Written | Contract approved. Execution not approved | Not run | D1–D6 and the exception register | BUSINESS DECISION |
| Recruitment, PMS, PF, GF, assessments, bills, lunch, documents, archive, hourly leave, personal files | Catalog rows | Not in the in-scope list as universal core | Not approved | Not built | Behavior unread | UNKNOWN until a later scope decision |
| NMS and Atlas Bills | Named slots | Extension framework written | Off by default in the workbook | Not built | Workflows unread | CLIENT EXTENSION slot. LEGACY ONLY as a copied engine |

Approved items that stay approved: no merge and no silent recode, tenant mapping, architecture separations AP-01 through AP-05, raw-punch immutability as a design rule, and the three company seed rows as rows. None of those approvals selects a policy value or authorizes an employee load.

Acceptance tests for the unsigned policy values do not exist, because the values are not chosen. Tests that only check “a row can be stored” would not close those decisions.

## 7. What still blocks a complete requirements analysis

Analysis is complete only when the in-scope behavior is evidenced, the target `shall` is written, the policy values that change that `shall` are signed, and the acceptance checks are stated. The `shall` text for the in-scope list exists. The policy values and most catalog modules do not.

### 7.1 Already answered in existing documents

| Gap | Already recorded | Remaining use |
| --- | --- | --- |
| G-01 | Requirements section 46 says architecture and development were not started | Read Steps 20A–20O for the current build state. Do not treat section 46 as the program clock |
| G-02 | 99 submodules and “all 416 links valid” | Superseded by Steps 21A and 21B. Do not revive them |
| G-03 | Parent `docs` has no copy of the requirements file | The file used is `legacy-reference\hrms\docs\UNIVERSAL_HRMS_REQUIREMENTS.md` |

### 7.2 Read-only source inspection, not started here

| ID | What is known | What is missing | Why it matters | Next step | Priority | Blocks |
| --- | --- | --- | --- | --- | --- | --- |
| G-10 | Day leave was sampled | Hourly-leave workflow | Could add a leave policy or show a separate product | Read the hourly-leave path before calling leave analysis complete | P1 | Requirements approval for leave |
| G-11 | `moduleAccess()` is described | Role, scope, and button-versus-server checks | Security `shall` text is ahead of the screen audit | Audit `appadmin` when security implementation is in scope | P1 | Test design for authorization |
| G-12 | Three engines were month-traced | Atlas and CEVA operated months | Could add a policy or an extension | One closed month each, only if the result can change design | P2 | Requirements approval only if a new rule appears |
| G-13 | Catalog holds recruitment, PMS, PF, GF, bills, lunch, documents, archive, assessments, personal files | Behavior | They are outside the written in-scope list | Leave them out of the first release unless an owner adds them | P3 | Does not block the current in-scope text |

### 7.3 Local database comparison

| ID | What is known | What is missing | Next step | Priority | Blocks |
| --- | --- | --- | --- | --- | --- |
| G-20 | Dump baseline in Steps 21A and 21B. Step 21C found MySQL stopped | Live `legacy_master` rows for the 19 controller ids, submodule ids 36 and 71, and module ids 7, 10, 11, 14, 15, 17, and 26 | Repeat Step 21C when the server is already running. Do not start MySQL in order to close this gap from this assessment | P1 | Confidence in the catalog. Does not by itself approve a policy |
| G-21 | Module 10 and module 26 are dangling in the dump | Whether the live database still has those references | Same read-only comparison | P1 | Catalog integrity |

### 7.4 Business-owner decisions

These remain unsigned in Step 20R. This assessment does not sign them.

| ID | Decision | Priority | Blocks |
| --- | --- | --- | --- |
| G-30 | D1 display name | P0 | Employee load |
| G-31 | D3 both `active` and `disconrinue` | P0 | Episode status |
| G-32 | D4 duplicate-code dispositions. No merge and no silent recode stay approved | P0 | Those VTL rows |
| G-33 | D2 zero-date handling | P1 | Date load |
| G-34 | D6 evidence for creating an episode | P1 | Episode load |
| G-35 | Meaning of `dis` | P1 | Any use of that column |
| G-36 | Episode overlap, date order, and inactive pay | P1 | Lifecycle rules beyond the built checks |
| G-37 | BD-D02 incomplete-punch outcome | P0 | Attendance status |
| G-38 | BD-D03 shift winner | P0 | Shift resolution |
| G-39 | BD-D09 overtime formula, with BD-D08 and BD-D10 through BD-D12 | P0 for the formula | Overtime calculation |
| G-40 | BD-D16 correction fields | P0 | Correction form |
| G-41 | BD-D18 snapshot lines | P0 | Optional snapshot content |
| G-42 | BD-D01, BD-D04, BD-D05, BD-D06, BD-D07, BD-D13, BD-D14, BD-D17 | P0 or P1 as already listed in Step 20R | The matching policy |
| G-43 | BD-D15 who may reopen | Deferred | Reopen, after close exists |

### 7.5 Evidence gaps already listed, still open

| ID | Gap | Next step | Priority | Blocks |
| --- | --- | --- | --- | --- |
| G-50 | Punch writer, `area`, `balid` | Read-only trace of who inserts `all_att_log` | P1 | Vendor and extension scope |
| G-51 | Joiner and leaver pay | A month that is not a full month | P1 | Payable-day rule |
| G-52 | A Proedge correction that changed the stored day | Use a month where that happened, if one is identified | P2 | Correction acceptance example |
| G-53 | VTL missing-punch email | Confirm send or leave it unconfirmed | P2 | Notification requirement |
| G-54 | `EB9091` September attendance and missing September salary | Re-read only if a disposition uses those facts | P2 | That employee’s exception, not the universal rule |

### 7.6 Safe to leave outside the first release

Recruitment, PMS, provident-fund workflows, gratuity-fund workflows, assessments, bills, lunch, documents, archive, personal files, hourly leave until G-10 is read, NMS, Atlas Bills, Avery, Tonic, Proedge Data, and WPPF. This is a scope observation from the current in-scope list. It is not a decision to drop the catalog rows.

## 8. Conclusion

Requirements analysis is **partially complete**.

The in-scope `shall` text is written. The active total is 173 IDs: 116 CORE, 34 CONFIGURABLE, 4 CLIENT EXTENSION, 17 BUSINESS DECISION, and 2 DEFERRED. The 166 figure was 111 CORE and 32 CONFIGURABLE. FY-CONF-001 made 167. LEAVE-CORE-005, LEAVE-CORE-006, LEAVE-CORE-007, LEAVE-CORE-008, MIG-CORE-011, and LEAVE-CONF-003 make 173. LEAVE-CORE-008 stays proposed. Five of 25 catalog modules, and 8 of 101 submodules, have partial behavior evidence from Proedge, VTL, and Atlas. No module or submodule is functionally analyzed. Policy values D01–D18 and migration decisions D1, D2, D3, D4 dispositions, and D6 are unsigned. `dis`, overlap, date order, and inactive pay are unresolved. The live catalog comparison is pending. No company is migration-approved.

Declaring analysis complete would require signed policy values for the in-scope list, acceptance checks for those values, and an explicit decision that the inventory-only modules stay out of that list. Those signatures are not in the current documents.

## 9. Statement

No database was connected. MySQL was not started. No schema, application code, or environment file was changed. Historical audit reports were not overwritten. Completed attendance, payroll, and employee investigations were not repeated.

REQUIREMENTS ANALYSIS PARTIALLY COMPLETE
