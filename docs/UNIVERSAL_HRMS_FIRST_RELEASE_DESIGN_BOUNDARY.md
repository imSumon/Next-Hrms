# Universal HRMS — First-Release Design Boundary

Date: 2026-10-17

Status: **DESIGN BOUNDARY.** This document states what the first release may specify. The approved boundary is Step 20R section 6D, dated 2026-10-17. This document does not expand that boundary and does not authorize implementation. It does not approve a policy, approve a migration, or declare the legacy catalog audited.

Audit closure used for this boundary: Outcome 2, ready for product design with conditions. Proedge, Viyellatex, and Atlas are evidence. None of them is the product default.

The decision register is `UNIVERSAL_HRMS_STEP20R_MANAGEMENT_DECISION_REGISTER.md`. Gate status is section 4 of `UNIVERSAL_HRMS_INCREMENTAL_DELIVERY_AND_DECISION_GATES.md`. The checkpoint is `UNIVERSAL_HRMS_PROJECT_CONTROL_CENTER.md` section 2.

## 1. Status conflict

The documentation authority, section 3, still says SEC-CORE-004 remains unsigned. That sentence is dated with the 2026-10-16 authority note.

Step 20R section 6C, the control center section 2, and the gates SEC-CORE-004 row record the initial role list as **APPROVED** on 2026-10-17 by Anisur Rahman, Owner — Universal HRMS Project, subject to the section 6C conditions. Those records also say the approval does not authorize implementation.

This boundary follows Step 20R section 6C for that item, because the authority itself says Step 20R is the register. The authority sentence was not rewritten. AUTH-ARCH section 6B says that entry does not satisfy SEC-CORE-004. Section 6C is the later entry that approves the list and still blocks the build. Those two register sentences are sequential, not two competing approvals.

A second list gap is recorded, not resolved. Gates section 4 marks BD-D01, BD-D04, BD-D05, and BD-D06 unsigned. Step 20R section 6 says D01–D18 are open and no value is selected. Section 8’s awaiting-approval list does not name BD-D01, BD-D04, BD-D05, or BD-D06. Section 8 already notes the same kind of omission for BD-D07. This boundary treats all four as unsigned empty slots.

## 2. How to read a row

| Label | Meaning |
| --- | --- |
| Approved invariant | A named approver and date, or a control-center item already in force. Design may restate it. Design may not extend it. |
| Proposed rule | Written and not approved. Design may cite it as proposed. Design may not activate it. |
| Unsigned owner decision | The register field is blank, or the gates row says unsigned. The slot has no default and no fallback. |
| Blocked verification | A test that was not run. It is not a pass. |
| Excluded | SCOPE-01, or a register exclusion. Not part of this first release. |
| Empty configuration slot | The mechanism may be named. The value stays empty until the named decision is signed. |

Seeded company rows for catalog ids 24, 20, and 10 are tenant records. They are not a migration approval.

## 3. Signed invariants the design may specify

| Capability | Boundary | ID | Status | Design may specify now | Stays undecided | Implementation gate |
| --- | --- | --- | --- | --- | --- | --- |
| Company isolation | One product. Each company’s employees, attendance, leave, and payroll stay apart. The enforced boundary is the active company. | ORG-CORE-001, ORG-CORE-002, AUTH-ARCH, D7-APPROVED | Approved | The active company is the boundary. Catalog ids 24, 20, and 10 are the tenants. Local ids 5 and 26 are not. | A scope narrower than the company. | SEC-CORE-003 unsigned. AUTH-ARCH does not authorize a narrower scope. |
| Employee identity | A new ULID is the identity. The employee code is not the identity. Inside one company the code is unique on every row, including after separation. | ID-APPROVED, CODE-APPROVED, EMP-CORE-001, EMP-CORE-002 | Approved | Those identity rules. | Which stored row is valid when a legacy code is duplicated. | D4 dispositions unsigned. NOMERGE-APPROVED forbids a merge or a silent recode. |
| Employment episode | Rejoining keeps the same employee and code, inserts a new episode, and leaves the previous episode in place. | LIFE-CORE-008, Step 20B section 5 | Approved and in force | That rejoin path only. | Overlap, date order, and whether an inactive episode may store a last working date. | LIFE-OVERLAP and LIFE-ORDER unresolved. |
| Separation record | Separation stores a last working date and does not invent days after it. | LIFE-CORE-007 | Requirement in force as text. Pay effect unsigned. | The date is stored. Days after it are not invented. | Final pay, forfeiture, and which legacy label is used. | BD-D07 unsigned. D3 and D6 unsigned. |
| Effective-dated facts | Attributes that affect processing are effective-dated facts, including department, designation, location, and shift default. | EMP-CORE-004, LIFE-CORE-002, LIFE-CORE-003, LIFE-CORE-004, LIFE-CORE-005 | Requirements recorded. Assignment columns are not a signed decision. | That a change is an effective-dated fact. | The column set. Which legacy reporting store is operational. | No category in SEC-CORE-004 includes employee assignment or branch writes. Those writes stay unauthorized. |
| Status set | At least active, inactive, and separated. | EMP-CORE-003 | Requirement recorded. Legacy mapping unsigned. | The three names exist. | How `disconrinue` or both-flags rows map. The pay effect of inactive. | D3 unsigned. LIFE-INACTIVE-PAY unresolved. |
| Raw punch | An accepted event is stored and is not edited by a correction. Recalculation writes a new day-result version. `area` is not company identity. | PUNCH-APPROVED, PUNCH-CORE-001, PUNCH-CORE-002, PUNCH-CORE-004, PUNCH-CORE-007 | Approved architecture. Vendor unknown. | Ingestion is separate from calculation. Company comes from an explicit map. | Who wrote the traced closed-month rows. Withdrawal procedure. | PUNCH-WITHDRAW unsigned. The external writer stays UNKNOWN. |
| Punch identity | One or more identities may map to one employee in one company. An identity active for two employees at the same time is rejected unless that company records an overlap. | PUNCH-CORE-008, PUNCH-CORE-009, DI-CORE-005 | Requirements recorded. No overlap signed. | The reject rule, with an empty overlap list. | Cards `126384` and `132058`. Any other shared card. | MIG-CORE-003 flags duplicates. It does not choose a winner. |
| Product shape | Punch, day result, correction, calculated overtime, payable overtime, paid overtime, and the finalized payroll snapshot stay separate. A finalized snapshot does not follow a later attendance change. | ARCH-APPROVED | Approved. Policy values unsigned. | That separation. | Grace, shift winner, overtime formula, cutoff, and extra snapshot lines. | BD-D01 through BD-D14, BD-D16, BD-D17, BD-D18 unsigned. BD-D15 deferred. |
| Leave position | A proposed position holds amount, period, source evidence, and stated basis. Each movement and reversal is its own entry. Without confirmation it is not an approved balance. | LEAVE-CORE-005, LEAVE-CORE-006, LEAVE-CORE-007 | Requirements recorded. Not a signed opening. | The proposed, excepted, and confirmed states. Variance has no numerical tolerance. | Which figure is the opening. Which period is operational. Who confirms. | LEAVE-OPENING unsigned. |
| Leave period slot | The leave period is configured apart from the payroll month and the financial year. | LEAVE-CONF-003 | Configurable requirement. Months unsigned. | That the three calendars are separate fields. | The months. July–June and January–December are stored evidence, not defaults. | LEAVE-OPENING and FY-BOUNDARY unsigned. |
| Sensitive-change record | A change to a sensitive fact stores actor, time, old value, and new value. | AUDIT-CORE-001 | Requirement recorded. | That record. | A record of a refused access. The register does not require one. | Not an implementation authorization. |
| Failed import | A failed import is rejected or quarantined. Partial silent writes are not the rule. | NFR-CORE-006 | Requirement recorded. | That outcome. | The legacy writer of the stored duplicate codes. | Does not approve a load. |
| Initial role list | Four company-scoped categories and the permission split in Step 20R section 6C. | SEC-CORE-004 | Approved 2026-10-17, subject to section 6C. Build not authorized. | The four names and the permissions listed there. | Flag replacement. User-to-employee link. The open items in section 6C. | The gates row blocks the role-matrix build. The two flags are not mapped to these categories. |
| Reconciliation contract | For a real load, source count equals loaded plus approved exceptions. Employees and episodes are counted separately. | RECON-APPROVED | Approved. Not executed. | The equation. | The result. | No company is migration-approved. |

SEC-CORE-004 categories, as approved, and not as a build:

- Self-service employee: P-ESS-01 only.
- Employee records manager: P-EMP-01 through P-EMP-04.
- Organization master manager: P-ORG-02. No branch write and no employee assignment.
- Membership and role administrator: P-MEM-01, P-MEM-02, P-ROLE-01, P-ROLE-02. Not a company administrator.

Company membership does not grant P-EMP-01 or P-ESS-01. No category includes payroll, attendance, leave, overtime, period close, or migration.

## 4. Empty configuration slots

Each slot has no default and no implied fallback. A legacy value in the evidence column is not a product value.

| Slot | ID | Status | Evidence that is not a default | What activates it |
| --- | --- | --- | --- | --- |
| Late and early grace | BD-D01 | Unsigned | Atlas 31 minutes. Proedge 16-minute late grace. | A signed value before grace is built. |
| Missing in or out outcome | BD-D02 | Unsigned. Proposed text says detect the incomplete day and leave the outcome empty. | Proedge and Atlas stored Present plus missing-punch text. Viyellatex stored a missing out as Absent. | A signed outcome. The proposal is not that signature. |
| Shift winner | BD-D03, SHIFT-CONF-001 | Unsigned. SHIFT-CORE-002 requires exactly one effective shift. | Proedge August used the employee shift. Viyellatex September used the calendar. | A signed winner. Until then both candidates may be stored and neither wins. |
| Start of late minutes | BD-D04 | Unsigned | No second threshold is approved. | A signed rule before late counting is built. |
| Early-out definition and pay | BD-D05 | Unsigned | No deduction formula is approved. | A signed rule before that pay effect is built. |
| Which leave types are paid | BD-D06 | Unsigned | No rule that all leave is paid. | A signed list before leave pay is built. |
| Opening amount and confirmation | LEAVE-OPENING | Unsigned. No formula is proposed. | Dump balances are stored amounts. | Named confirmation of the figure, the period, the reconciliation, and the confirmer. |
| Encashment inside pay | LEAVE-CORE-008 | **PROPOSED.** Not approved. | F-38 does not link Atlas head 398 to the encashment ledger. | A signed rule. The proposed sentence is not that signature. |
| Absence and partial-month pay | BD-D07 | Unsigned | AUD-06 describes a 30-day or calendar divisor. Those figures are not approved. | A signed formula before payroll calculation. |
| Overtime eligibility | BD-D08 | Unsigned | `eligible_for_ot` is not frozen. | A signed rule. |
| Overtime calculation | BD-D09 | Unsigned | Neither studied formula is approved. | A signed rule. |
| Overtime rounding | BD-D10 | Unsigned | The 0.75 fraction is not approved. | A signed rule. |
| Overtime approval | BD-D11 | Unsigned | Punch approval is not overtime approval. | A signed rule. |
| Overtime pay | BD-D12 | Unsigned | `basic/208×2` and the studied amounts are not approved. | A signed rate, minimum, and maximum. |
| Attendance cutoff | BD-D13 | Unsigned | A Yes flag is not a cutoff. | A signed cutoff before an attendance freeze is built. |
| Freeze after generation | BD-D14 | The snapshot rule is approved. Any further regeneration block is unsigned. | A studied month was not a signed close. | A signed block beyond the approved snapshot rule. |
| Who may reopen | BD-D15, PER-CONF-003 | Deferred | Reopen was not observed. | An owner owns a reopen rule. Until then there is no reopen action. |
| Correction field list | BD-D16 | Mechanism approved. Field list empty. | Original, requested, approved, and final. The raw punch is not edited. | A signed field list. |
| Approval step list | BD-D17, APP-CORE-001 | The ordered-list mechanism is a requirement. The step list is unsigned. | Boss and grand-boss is not the product sequence. | A signed sequence. |
| Optional snapshot lines | BD-D18 | Snapshot separation is approved. Extra lines are unsigned. | No invented line. | A signed list of extra facts. |
| Financial-year months | FY-BOUNDARY | Unsigned | January–December, June–May, and March–February are not a company’s financial year. | Evidenced months and a signature. |
| Documented punch overlap | PUNCH-CORE-009 | The reject rule is recorded. The overlap list is empty. | Cards `126384` and `132058` are unresolved. | A named owner decision for each card. |
| Duplicate employee codes | NOMERGE-APPROVED. D4 group hold proposed. | No-merge is approved. The group hold is not. | Step 20G section 11. Seven codes, fourteen rows, all unresolved. | A disposition per group. No merge, delete, or recode from this document. |

## 5. Implementation that stays blocked

Design of an empty slot is not permission to build it.

| Feature | Why it stays blocked |
| --- | --- |
| Role matrix, permission screens, flag replacement, directory enforcement | SEC-CORE-004 approves the list and blocks the build. The two flags stay until a separate tested mapping. |
| Narrower than company | SEC-CORE-003 unsigned. |
| Attendance grace, status outcome, shift selection, late, early-out | BD-D01 through BD-D05. |
| Leave pay and an approved balance | BD-D06 and LEAVE-OPENING. |
| Payroll calculation, overtime, cutoff, close beyond the snapshot rule | BD-D07 through BD-D14 and BD-D18. |
| Correction editing and approval workflow | BD-D16 and BD-D17. |
| Reopen | BD-D15 deferred. |
| Punch withdrawal | PUNCH-WITHDRAW unsigned. |
| Employee assignment and branch writes | Not included in the approved categories. Assignment columns are not a signed decision. |
| Employee migration and dry-run execution | D1, D2, D3, D4 dispositions, D6, a signed exception register, an executed reconciliation, and a named company approval. |
| Next implementation milestone | Gates section 5. Not authorized. |

## 6. Excluded from this first release

SCOPE-01 stays deferred. An owner must add a module before it is audited or built for this release.

| Group | What stays out |
| --- | --- |
| Recruitment | Catalog module 8. Submodules 80, 81, 82. |
| PMS and assessments | Catalog modules 6, 27, 32 and the submodules listed in Step 21E. |
| Provident fund and gratuity | Catalog modules 12 and 13 and the submodules listed in Step 21E. |
| Bills, lunch, documents, archive, personal files | The Step 21E deferred set. |
| Other payroll extras | Appraisal, tax, loans, and the named company reports. Payroll Basic, Overtime, and OT Management are the payroll names Step 21E kept in view. Their formulas remain the empty slots in section 4. |

The catalog grain stays: 5 modules and 8 submodules partial, 91 submodules inventory only, 0 catalog rows marked functionally analyzed. Those 91 submodules are unaudited. This boundary does not close them.

## 7. Gates that are not design

| Gate | State | What this boundary does not do |
| --- | --- | --- |
| Employee migration | Blocked | It does not approve a company, a load, or a dry run. |
| F-42 Atlas department screen | Blocked on 2026-10-17 | It does not mark a cross-company read as executed. |
| VIS-01 through VIS-12 | Blocked. The evidence folder has no PNG. | It does not treat the new application as the legacy portal. |
| DB-01 live catalog | Open until MySQL is already running | It does not start MySQL or call the dump a live comparison. |
| Signed-in UAT | Checklist marked. Not verified | The evidence register holds the seven foundation rows. Tester, date, actual result, and result evidence are pending. Automated tests are not that UAT. The session is not passed. |
| Punch-card and duplicate-code decisions | Unresolved | It does not change a mapping or reprocess attendance. It does not treat a collision as proof that a stored day or pay result is wrong. |
| Production data | Required to be preserved and reconciled | Quarantine is not the loaded side of RECON-APPROVED. No row is assumed disposable. |

## 8. Statement

One file was added: this design boundary. No application code, schema, migration, configuration, database, or Git state was changed. No grace, leave opening, pay formula, card owner, or duplicate-code winner was selected. LEAVE-CORE-008 stays proposed. SCOPE-01 stays out. The 91 inventory-only submodules stay unaudited.

DESIGN BOUNDARY
