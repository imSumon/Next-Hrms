# Universal HRMS — Step 21 Requirements Traceability Matrix

Requirements analysis only. No feature was implemented. No decision in Step 20R was signed by writing this matrix.

A proposed target behavior is not an approved requirement. Policy values in D01–D18 remain unsigned.

No company is approved for employee migration. The three local company rows do not authorize a data load.

## 1. Sources

Read for this matrix:

- `docs/UNIVERSAL_HRMS_REQUIREMENTS.md` (in `legacy-reference\hrms\docs`; the parent `docs` folder does not contain this file)
- `docs/UNIVERSAL_HRMS_ARCHITECTURE_ASSESSMENT.md`
- `docs/UNIVERSAL_HRMS_LOGICAL_DATA_ARCHITECTURE_ASSESSMENT.md`
- `docs/UNIVERSAL_HRMS_PHYSICAL_DATA_ARCHITECTURE_ASSESSMENT.md`
- `docs/UNIVERSAL_HRMS_TARGET_DATABASE_SCHEMA_SPECIFICATION.md`
- `docs/UNIVERSAL_HRMS_MIGRATION_DATA_CONTINUITY_ARCHITECTURE.md`
- `docs/UNIVERSAL_HRMS_MULTI_COMPANY_MIGRATION_MAPPING_WORKBOOK.md`
- `docs/UNIVERSAL_HRMS_PROEDGE_AUGUST_2026_DRY_RUN_REPORT.md`
- `docs/UNIVERSAL_HRMS_THIRD_REFERENCE_COMPANY_CLOSED_MONTH_AUDIT.md`
- `docs/UNIVERSAL_HRMS_MANAGEMENT_FINDINGS_PRE_DEVELOPMENT_REPORT.md`
- `docs/UNIVERSAL_HRMS_STEP20R_MANAGEMENT_DECISION_REGISTER.md`
- `docs/UNIVERSAL_HRMS_STEP20R_1_MANAGEMENT_REVIEW_AGENDA_AND_DECISION_CAPTURE.md`
- Employee steps 20B through 20O, as already summarized in Steps 20Q and 20R

The requirements appendix counts 166 IDs: 111 CORE, 32 CONFIGURABLE, 4 CLIENT EXTENSION, 17 BUSINESS DECISION, 2 DEFERRED. That count is the document’s own total. This step did not re-audit each sentence.

Step 21A later documented the master-catalog inventory from `proedgea_payrollmaster.sql`: 25 `modules` rows, 101 `sub_modules` rows, and 416 `controllers` rows. The 99-submodule figure was not in that dump. This matrix’s module table below remains the filesystem count from this step: 25 directories and 346 PHP controllers. A catalog row and a PHP file are different evidence. Neither count is a completed behavior audit. Per-submodule behavior status is in `docs/UNIVERSAL_HRMS_STEP21D_REQUIREMENTS_COVERAGE_GAP_ASSESSMENT.md`.

## 2. How to read a row

| Column | Meaning |
| --- | --- |
| Current behavior | What an audit or the requirements notes already recorded |
| Business requirement | A `shall` already written in the requirements document |
| Planned behavior | The target split already written in architecture or schema. Unsigned where it depends on D01–D18 |
| Approval | APPROVED only where an earlier document already says so. Otherwise PROPOSED or UNSIGNED |
| Implemented | Schema or rows that exist in local `proedge_new_hrms`. Empty design is not implementation |

## 3. Legacy module coverage

Companies verified for behavior means a closed-month or identity audit, not a visit to every page.

| Module | Controllers | Coverage | Companies with behavior evidence | Unverified |
| --- | ---: | --- | --- | --- |
| appadmin | 23 | Source authorization audited in AUD-03 | Not a company-month audit. No alias branch in these controllers | Live grant-row counts. Non-appadmin action sites were sampled, not exhaustively read |
| assessment360degree | 10 | Not analyzed | None | Whole module |
| atlasbills | 5 | Inventoried as an extension slot | Atlas module id 16 is named in the workbook as optional and off by default | Bill workflows |
| attendance | 14 | Partially analyzed | Proedge August 2026, VTL September 2026, Atlas September 2026. AUD-05 compared the Atlas and CEVA process actions in source | Which action produced Atlas September. CEVA has no local operated month |
| attendancealllog | 1 | Source writers audited in AUD-04 | Shared log used by the three month audits. In-repo writers do not set `area` | External writer. Meaning of `area`. `balid` on rows the transfer writer did not create |
| billmanagement | 8 | Not analyzed | None | Whole module |
| dataarchive | 4 | Not analyzed | None | Whole module |
| documents | 4 | Not analyzed | None | Whole module |
| gfmanagement | 24 | Not analyzed | None | Whole module |
| hourlyleave | 7 | Not analyzed | None | Relation to day-result leave |
| leave | 10 | Partially analyzed | Leave-day effects in the three studied months | Request workflow end to end |
| lunchmanagement | 5 | Not analyzed | None | Whole module |
| mobilebillmanagement | 2 | Not analyzed | None | Whole module |
| multiraterassessment | 13 | Not analyzed | None | Whole module |
| nmsdata | 2 | Named as a client extension | Payslip views other than companies 20 and 24 | NMS calculation |
| organizationsettings | 25 | Partially analyzed | Company catalog and local company rows | Full org tree per company |
| payroll | 69 | Partially analyzed | Salary rows for the three studied months | The other 60-plus controllers |
| personalfiles | 11 | Not analyzed | None | Whole module |
| pfmanagement | 40 | Not analyzed | Atlas September recorded a PF amount match. It did not audit this module | Fund workflows |
| pims | 7 | Partially analyzed | Employee identity dumps for three companies | Screens beyond the employee row |
| pms | 45 | Not analyzed | None | Whole module |
| proedgedata | 5 | Not analyzed | None | Whole module |
| recruitment | 8 | Not analyzed | None | Possible scope gap against the requirements in-scope list, which does not name recruitment |
| reports | 1 | Not analyzed as a module | Timecard and payslip behavior is described inside the attendance and payroll audits | Report catalog |
| tonicdata | 3 | Not analyzed | None | Whole module |

Frontend `ess` has 24 controllers. ESS requirements exist. A separate NREE mobile assessment exists. ESS page behavior was not re-audited here.

Folders whose names start with `00` were excluded as backup or unused copies. They are not a second product inventory.

The controller counts in the table above are PHP files. The database catalog is in `docs/UNIVERSAL_HRMS_STEP21A_LEGACY_MODULE_SUBMODULE_INVENTORY.md`. Catalog attendance has 29 controller rows. This table’s attendance row has 14 PHP controllers. Those numbers describe different sets. The catalog also includes `averydata` and `wppfmanagement`, which are not in this directory table. This directory table includes `appadmin` and `reports`, which are not catalog modules.

## 4. Capability matrix

### 4.1 Company and organization

| Field | Content |
| --- | --- |
| Requirement IDs | ORG-CORE-001 through ORG-CORE-008, ORG-CONF-001, ORG-CONF-002 |
| Workflow | Company boundary, branch, department, designation, group, reporting line |
| Current behavior | Separate databases. `db_alias` selects behavior. `previous_db_id` is not the tenant |
| Evidence | Requirements section 7. Architecture assessment. Workbook. Steps 20I, 20N, 20O |
| Companies | Catalog 24, 20, and 10 identified. Many other catalog rows exist and were not studied |
| Business requirement | Isolate each company. Policy is configuration, not an alias |
| Planned behavior | `companies.id` is the tenant ULID. Catalog id is `source_key`. **APPROVED** as the mapping rule |
| Gap | Branch, department, and location rows are not loaded. Location depth is CONFIGURABLE and not chosen per company |
| Class | CORE for the boundary. CONFIGURABLE for tree depth and branch override |
| Priority | P0 for the boundary. P1–P2 for structure |
| Acceptance, not executed | A read of another company’s employees or attendance fails. Salary key 5 or 26 is not stored as a tenant |
| Dependencies | None for the boundary. ORG-CONF-002 stays off until a company needs it |
| Blocker | None for the three company rows. Migration of people is blocked elsewhere |
| Owner and approval | Boundary APPROVED. Depth and branch override UNSIGNED |

Implemented: three `Active` company rows. `branches`, `location_nodes`, and `organization_nodes` exist and are empty.

### 4.2 Employee identity and lifecycle

| Field | Content |
| --- | --- |
| Requirement IDs | EMP-CORE-001 through EMP-CORE-004, EMP-CONF-001, LIFE-CORE-001 through LIFE-CORE-008 |
| Workflow | Master, name, code, joining, separation, rejoin, inactive |
| Current behavior | One `employees` row per company database. Code is `emp_id_no`. Flags `active` and `disconrinue` can both be 1. No episode table |
| Evidence | Steps 20F–20I. Requirements sections 9 and 10 |
| Companies | Proedge 160/160, Atlas 151/151, VTL 1,420 rows and 1,413 codes. Dump findings, not a live census |
| Business requirement | One person in one company. Code is not the identity. Rejoin keeps the person and adds an episode |
| Planned behavior | ULID `employees.id`. Code unique inside the company, including after separation. Episode statuses `Open`, `inactive`, `separated`. **APPROVED** as schema rules. Display name, zero dates, both-flags status, duplicate-code hold, and episode creation are **PROPOSED — NOT APPROVED** (D1, D2, D3, D4, D6) |
| Gap | EMP-CORE-003 names active, inactive, and separated. The stored active token is `Open`, not `active`. Pay effect of `inactive` is unsigned. Both-flags rows have no status |
| Class | CORE for identity and episode. BUSINESS DECISION for D1–D4 and D6. UNKNOWN for `dis` |
| Priority | P0 |
| Acceptance, not executed | Source count = loaded + approved exceptions, employees and episodes separately. Duplicate codes remain two source rows. No merge and no silent recode |
| Dependencies | D1, D2, D3, D4, D6. Overlap, date order, and inactive pay stay out of the first episode load |
| Blocker | Development blocked for a load. Migration blocked. Design of the tables is not blocked |
| Owner and approval | HR operations. UNSIGNED except the no-merge rule, which is APPROVED |

Implemented: `employees` and `employment_episodes` tables, zero rows.

### 4.3 Shift, roster, weekly off, holiday

| Field | Content |
| --- | --- |
| Requirement IDs | SHIFT-CORE and SHIFT-CONF, HOL-CORE and HOL-CONF, as indexed in the requirements traceability section |
| Workflow | One effective shift per employee and work date. Weekly off as a rule |
| Current behavior | Proedge August used the employee shift and an empty calendar. Viyellatex September used the calendar, with mismatches recorded. Atlas September matched the employee shift. `photo` is a legacy weekly-off trick at Proedge |
| Evidence | Architecture assessment. Workbook. Requirements section 45 |
| Companies | The three studied months only |
| Business requirement | One effective assignment after resolution. Which source wins is configuration |
| Planned behavior | Keep both candidates. Do not pick a winner in migration. **PROPOSED — NOT APPROVED** for BD-D03. Weekly-off photo text is LEGACY ONLY |
| Gap | BD-D03 is unsigned. Cross-midnight is allowed in the target and was not observed in Atlas September |
| Class | CORE for one assignment. CONFIGURABLE and BUSINESS DECISION for the winner |
| Priority | P0 for the winner. P1 for holiday rules |
| Acceptance, not executed | A historical day cites the shift stored on that day. A go-forward day does not silently prefer roster or default |
| Dependencies | BD-D03 |
| Blocker | Development blocked for the winner rule |
| Owner and approval | Attendance/Admin. UNSIGNED |

### 4.4 Punch ingestion and identity

| Field | Content |
| --- | --- |
| Requirement IDs | PUNCH-CORE and PUNCH-CONF family. Architecture cites PUNCH-CORE-004 and PUNCH-CORE-008 |
| Workflow | Accept a punch, name the source, map the card to one employee in one company |
| Current behavior | Shared `legacy_master.all_att_log`, about 13.9 million rows in the architecture assessment. Company column null on traced days. Card map is `emp_code_to_punch_card`. Middle punches are dropped from the day. Same-second uniqueness can drop an event. In-repo writers are a file upload of `date` and `emp_code`, and a location-access transfer that also sets `update_datetime` and `balid` to `location_access_data.id`. Neither writes `area` |
| Evidence | Architecture assessment sections 3 and 9. Workbook. Third-company audit. AUD-04 |
| Companies | Traced days for Proedge, VTL, and Atlas. The vendor is UNKNOWN |
| Business requirement | Keep the event. Do not rewrite the timestamp into a work date. Map the card inside one company |
| Planned behavior | Immutable punch. Company from the map. Unmatched or double-matched cards are exceptions. Withdrawal authority is UNSIGNED |
| Gap | The external writer of the traced rows, `area`, and `balid` on rows the transfer did not write stay UNKNOWN. A withdrawal procedure is not signed |
| Class | CORE for retention and mapping. UNKNOWN for the vendor. BUSINESS DECISION for who may withdraw |
| Priority | P0 |
| Acceptance, not executed | A correction does not change the punch. A punch with no company or two companies is not guessed |
| Dependencies | Employee identity. PUNCH-WITHDRAW |
| Blocker | Development blocked for a withdrawal action. Migration of punches is not authorized |
| Owner and approval | Immutability APPROVED as architecture. Withdrawal UNSIGNED |

### 4.5 Attendance day result, incomplete punch, late, early

| Field | Content |
| --- | --- |
| Requirement IDs | ATT-CORE and ATT-CONF family. BD-D01, BD-D02, BD-D04, BD-D05 |
| Workflow | Calculate a stored day. Detect a missing punch. Measure late and early |
| Current behavior | `attendance_detail` is replaced by delete-and-insert. Proedge and Atlas: Present plus missing-punch text. VTL: missing out stored as Absent. Late thresholds differ. AUD-05: the Atlas process action uses 31 minutes late and 31 minutes early; the Proedge and CEVA process actions use 16 minutes late and compare early-out with shift end. CEVA has no operated month in the local databases |
| Evidence | Architecture assessment. Workbook. Requirements section 43. AUD-05 |
| Companies | Three studied months. Not a universal sample |
| Business requirement | Detect incomplete attendance. The outcome is configuration. Do not freeze Present or Absent |
| Planned behavior | New day-result version on recalculation. `incomplete_condition` stays separate from status. Outcome value empty until BD-D02 is signed |
| Gap | Grace, late start, early pay, and incomplete outcome are UNSIGNED |
| Class | CORE for detection and a stored result. CONFIGURABLE and BUSINESS DECISION for the values |
| Priority | P0 for BD-D02. P1 for grace, late start, and early pay |
| Acceptance, not executed | The same missing punch is not automatically Present in one company and Absent in another unless that company signed the outcome |
| Dependencies | BD-D02, BD-D03, BD-D01, BD-D04, BD-D05 |
| Blocker | Development blocked for status rules |
| Owner and approval | UNSIGNED |

### 4.6 Leave

| Field | Content |
| --- | --- |
| Requirement IDs | LEAVE-CORE and LEAVE-CONF, including LEAVE-CORE-005 through LEAVE-CORE-007 and LEAVE-CONF-003. MIG-CORE-011. BD-D06. LEAVE-OPENING |
| Workflow | A request can mark a day. A balance position stays proposed until a recorded confirmation. Pay effect is per type |
| Current behavior | Status number 3 was the studied “approved” token. Some leave days left basic unchanged. That does not prove every type is paid. The 2026-10-17 dumps show leave-year labels that match the balance rows. They do not show which figure is the accepted opening, who confirms it, or which writer produced it |
| Evidence | Architecture assessment section 10. Requirements BD-D06. Evidence register F-31 through F-34 |
| Companies | Proedge, Viyellatex, and Atlas. Staff practice is not established |
| Business requirement | Leave publishes a day classification. Payroll decides the money. An opening balance is a proposed position until LEAVE-OPENING is signed |
| Planned behavior | Status number 3 is LEGACY ONLY. Pay class stays empty until BD-D06. A legacy balance loads only as proposed |
| Gap | Hourly leave module is not analyzed. Approval chain is BD-D17. Opening figure, leave year, reconciliation, encashment meaning, and the confirmer are unsigned. F-35 through F-37: the studied salary generation does not read the encashment stores |
| Class | CORE for the request, the day mark, and an unconfirmed position. BUSINESS DECISION for pay and for the opening |
| Priority | P1. P0 for the migration hold in F-34 |
| Acceptance, not executed | An approved leave day is visible as leave. Basic pay is not assumed unchanged. A proposed balance is not available for approval |
| Dependencies | BD-D06, BD-D07, BD-D17, FY-BOUNDARY, LEAVE-OPENING |
| Blocker | Development blocked for leave pay and for an approved balance. Preserving a historical leave token, and recording a proposed position, do not require the formula |
| Owner and approval | UNSIGNED |

### 4.7 Correction and approval

| Field | Content |
| --- | --- |
| Requirement IDs | CORR-CORE, CORR-CONF, APP-CORE, APP-CONF. BD-D16, BD-D17 |
| Workflow | Request, steps, original and final values |
| Current behavior | `attendance_approval_file`. VTL samples rewrote displayed times toward the shift boundary. A Proedge August sample left the day Absent. Approver is often a name or code. Boss and grand-boss are columns |
| Evidence | Architecture assessment sections 11 and 12. Workbook section 12 |
| Companies | Proedge August and VTL September. Atlas September had no correction rows. August Atlas examples exist and the writer of some shift-boundary punches was not proven |
| Business requirement | Four values: original, requested, approved, final. The punch is not edited |
| Planned behavior | Mechanism APPROVED as architecture. Field list and step list UNSIGNED |
| Gap | A before-image is often missing and must not be invented |
| Class | CORE for history. BUSINESS DECISION for fields and steps |
| Priority | P0 for BD-D16. P1 for BD-D17 |
| Acceptance, not executed | Applying a correction adds a version. The prior version and the punch remain |
| Dependencies | BD-D16, BD-D17 |
| Blocker | Development blocked for the form and the route |
| Owner and approval | Mechanism APPROVED. Values UNSIGNED |

### 4.8 Overtime

| Field | Content |
| --- | --- |
| Requirement IDs | OT-CORE-001, OT-CORE-002, OT-CORE-003, and the OT-CONF family. BD-D08 through BD-D12 |
| Workflow | Calculate, optionally approve, then pay |
| Current behavior | Three stores disagree. Proedge paid 6,158.25, which is not the attendance seconds. VTL stored hours on 173 correction days and paid 0 in the studied month. Atlas September has no calculated overtime and no overtime pay head |
| Evidence | Workbook section 13. Requirements BD-D08 through BD-D12 |
| Companies | Three studied months |
| Business requirement | Calculated, payable, and paid overtime stay separate |
| Planned behavior | Separation APPROVED as architecture. Formula, rounding, eligibility, approval, and rate UNSIGNED. `basic/208×2` is LEGACY ONLY |
| Gap | No signed formula |
| Class | CORE for the split. BUSINESS DECISION for the values |
| Priority | P0 for the formula (BD-D09). P1 for eligibility, approval, and rate. P2 for rounding |
| Acceptance, not executed | A payslip overtime amount can be traced to a snapshot line, not to a fresh reading of punches |
| Dependencies | BD-D03, BD-D08 through BD-D12 |
| Blocker | Development blocked for calculation and payment |
| Owner and approval | UNSIGNED |

### 4.9 Payroll, snapshot, payslip, period close

| Field | Content |
| --- | --- |
| Requirement IDs | PAY-CORE and PAY-CONF, PER-CORE and PER-CONF. BD-D07, BD-D13, BD-D14, BD-D15, BD-D18 |
| Workflow | Declared inputs, run, snapshot, payslip, optional close, reopen |
| Current behavior | `employee_salary` amounts only. No attendance snapshot. Lock tables did not close the studied months |
| Evidence | Workbook section 14. Architecture AP-05. Requirements section 43. AUD-06 |
| Companies | Proedge August, VTL September, Atlas September |
| Business requirement | Payslip reads the snapshot. Later attendance recalculation does not change a finalized snapshot |
| Planned behavior | Snapshot separation APPROVED. Close, reopen, absence formula, and optional lines UNSIGNED. A legacy Yes flag is not a close |
| Gap | Payable-day basis has no universal default. AUD-06: the salary generate prorates `partial=1` heads from `joining_date` through month end and omits `disconrinue=1`. It does not prorate `discontinue_date`. No operated partial month was read |
| Class | CORE for snapshot and payslip-from-snapshot. BUSINESS DECISION for D07, D13, D14, D18. DEFERRED for D15 |
| Priority | P0 for BD-D18 in the requirements appendix. P1 for absence and close |
| Acceptance, not executed | Regenerating attendance does not change a finalized result. Historical amounts load unchanged |
| Dependencies | BD-D14, BD-D15, BD-D18, BD-D07 |
| Blocker | Development blocked for close, reopen, and optional lines. Migration of historical amounts is a later authorized load, not approved now |
| Owner and approval | Snapshot rule APPROVED. Policy values UNSIGNED |

### 4.10 Security, ESS, reports, notifications, audit

| Field | Content |
| --- | --- |
| Requirement IDs | SEC-CORE family, ESS, RPT, NOTIF, AUDIT, as grouped in the requirements index |
| Workflow | Server authorization, ESS bound to one employee, reports read stored results, notifications, change history |
| Current behavior | `moduleAccess()` checks the module and is not called from appadmin. Eight appadmin controllers have no level test. `UserController::actionUpdate` saves with the level test commented out. Grid buttons read `user_companies_module_controllers`. A Viyellatex missing-punch email was not confirmed as sent |
| Evidence | AUD-03. Requirements sections 11, 33–36, and 44 |
| Companies | Not a three-company page audit |
| Business requirement | The server authorizes the action. ESS is one employee in one company. Reports do not calculate a second result |
| Planned behavior | Stated as CORE requirements. Role tables were not part of the implemented schema batches |
| Gap | AUD-03 found no action-level server check. The role list and tenant scope remain unsigned. Notification delivery is UNKNOWN |
| Class | CORE for server authorization. UNKNOWN for notification delivery |
| Priority | P0 for server authorization. P1 for scope limits and ESS binding |
| Acceptance, not executed | A hidden button cannot change state. A user cannot read another company |
| Dependencies | SEC-CORE-001. CORR-CONF-001 for correction permissions |
| Blocker | Development of those screens has not started. Not a migration blocker by itself |
| Owner and approval | Requirements written. Not implemented. Not re-approved in Step 20R |

### 4.11 Migration and reconciliation

| Field | Content |
| --- | --- |
| Requirement IDs | MIG family in the requirements document. Continuity architecture |
| Workflow | Preserve values. Do not reinterpret status or money. Reconcile by company |
| Current behavior | Not a legacy feature. It is the migration contract |
| Evidence | Steps 20E, 20J, 20R. Continuity architecture |
| Companies | None loaded |
| Business requirement | Source count = loaded + approved exceptions. Quarantine is not the right-hand side |
| Planned behavior | Contract APPROVED. Not executed. D1–D6 gates remain proposed |
| Gap | Exception register is a template. Reconciliation has not been run |
| Class | CORE |
| Priority | P0 |
| Acceptance, not executed | Every source row appears once. Duplicate codes count as two. Unexplained rows are zero before approval |
| Dependencies | D1, D2, D3, D4, D6, and a signed exception register |
| Blocker | Migration blocked |
| Owner and approval | Contract APPROVED. Execution UNSIGNED. No company migration-approved |

## 5. Cross-company rule

Proedge, VTL, and Atlas are evidence. They are not the product standard. Other catalog companies, and the Atlas and CEVA process actions, can still change a policy value or show another extension. They do not reopen the decision to use one product with company configuration.

Three-company coverage does not prove that every variation has been captured.

## 6. Implemented versus specified

| Item | State |
| --- | --- |
| Requirements text, 166 IDs | Specified. Policy values not frozen |
| Company rows for catalog 24, 20, 10 | Implemented locally |
| Employee and episode tables | Implemented empty |
| Attendance, leave, overtime, and payroll tables | Specified in the schema document. Not created by the migrations run through Step 20 |
| D1–D6 and D01–D18 values | Unsigned, except architecture separations and the no-merge rule |
| Employee migration | Not approved |

## 7. Statement

This matrix connects the audited legacy behavior to requirement IDs that already exist. It does not approve a policy value, load a row, or change schema or application code.

TRACEABILITY MATRIX PREPARED
