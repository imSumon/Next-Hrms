# Universal HRMS — First-Release Functional Design

Date: 2026-10-17

Status: **FUNCTIONAL DESIGN.** This document organizes the first release from `UNIVERSAL_HRMS_FIRST_RELEASE_DESIGN_BOUNDARY.md` and the active requirements. The approved boundary is Step 20R section 6D, dated 2026-10-17. This document does not expand that boundary and does not authorize implementation. It does not approve a policy, approve a migration, or declare the legacy catalog audited.

A signed invariant may be restated. A proposed rule stays proposed. An empty slot has no default and no fallback. Proedge, Viyellatex, and Atlas are evidence, not product values.

## 1. Capability map

| Group | Class | Why |
| --- | --- | --- |
| Platform and company context | Designable now | ORG-CORE-001, ORG-CORE-002, ORG-CORE-003, and AUTH-ARCH fix the active company as the boundary. |
| Identity, authentication, and membership | Designable with conditions | Session login and company membership exist. The user-to-employee link is unsigned. Membership is not employee access and is not ESS access. |
| Authorization and role administration | Designable with conditions | SEC-CORE-004 approves the four-category list. The role-matrix build is blocked. The two flags are not mapped to those categories. |
| Organization structure and masters | Designable with conditions | Departments and designations are company-scoped masters. Branch writes, employee assignment, and group eligibility rates are not approved actions. |
| Employee directory, profile, and lifecycle | Designable with conditions | Identity, code uniqueness, display-name history, and LIFE-CORE-008 are in force. Status mapping, overlap, date order, and assignment columns are unsigned. |
| Attendance ingestion and day results | Designable with conditions | The punch, the day result, and the correction stay separate. Calculation values are empty. |
| Leave requests, approvals, and balances | Designable with conditions | A proposed position may be described. It is not an approved balance. Requests and approval steps are blocked. |
| Payroll inputs, calculation, and finalization | Designable with conditions | The snapshot stays apart from a later attendance change. Formulas, heads in use, and extra lines are empty. |
| Audit trail and operational reporting | Designable with conditions | AUDIT-CORE-001 applies to a sensitive change. Financial-year reports wait on FY-BOUNDARY. A refused access is not a required audit row. |
| Migration, reconciliation, and cutover | Designable with conditions | RECON-APPROVED and MIG-CORE-003 may be stated. Execution is not approved. |
| SCOPE-01 | Excluded | Recruitment, PMS, provident fund, gratuity, and the inventory-only payroll extras. The 91 inventory-only submodules stay unaudited. |

## 2. Dependency sequence

A later group may be specified only inside the boundary of the groups above it. A blocked gate stops activation. It does not erase the signed shape.

1. Platform and company context.
2. Authentication and membership, still on the two existing flags.
3. Employee identity, directory, profile, and episodes.
4. Department and designation masters, without assigning an employee.
5. Audit of a sensitive change on those facts.
6. Attendance shape, leave-position shape, and payroll-snapshot shape. Each stays inactive until its own gate is signed.
7. Role-matrix administration. The list is approved. The build is not.
8. Migration and cutover, after D1, D2, D3, D4 dispositions, D6, the exception register, reconciliation, and a named company approval.

SCOPE-01 does not sit on this sequence.

## 3. Functional specifications

### 3.1 Platform and company context

Purpose: one product, with company policy stored as data. Users: any signed-in membership of the active company. The membership does not by itself grant employee or ESS access.

Responsibilities: select the active company on the server. Isolate that company’s employees, attendance, leave, and payroll. Reject another company’s data. Attach policy to the company, not to a database alias.

Inputs: the authenticated user and that user’s memberships. Outputs: one active company. A client-supplied company id does not select it.

Workflow: sign in, receive the server-selected company, end the session on logout. No other transition is specified here.

Authorization: AUTH-ARCH. The enforced boundary is the active company. SEC-CORE-003 would limit a user to a branch, department, or employee. That requirement is unsigned, so no narrower scope is specified.

Company isolation: ORG-CORE-001, ORG-CORE-002, SEC-CORE-002, D7-APPROVED. Catalog ids 24, 20, and 10 are the tenant records. Local ids 5 and 26 are not.

Audit: a change of company policy is a sensitive fact under AUDIT-CORE-001. No policy value is chosen in this design.

Depends on: nothing inside the product. Blocks: every later group.

Requirements: ORG-CORE-001, ORG-CORE-002, ORG-CORE-003, SEC-CORE-002, AUTH-ARCH, D7-APPROVED.

Unresolved: SEC-CORE-003. Activation of a narrower scope waits on that signature.

Acceptance, without a new rule: a request that names another company does not return that company’s data. The active company shown to the user is the server-selected company.

### 3.2 Identity, authentication, and membership

Purpose: know who is signed in and which company memberships they hold. Users: the account holder. A membership administrator, once the role-matrix gate allows that category, would add or remove a membership. That build is not authorized.

Responsibilities: authenticate the account, keep memberships, and refuse to treat membership as P-EMP-01 or P-ESS-01. The implemented grants remain `can_manage_employees` and `can_manage_organization_masters`. This design does not map those flags onto the four categories.

Inputs: credentials and an existing membership. Outputs: a session bound to one active company. The user-to-employee link is an input this design does not define.

Workflow: login and logout, as already in the application. No password-recovery or account-lock transition is specified. Those procedures are open in Step 20R section 6C.

Authorization: SEC-CORE-001 requires the server to authorize a state-changing action. The current implementation rule remains the two flags. P-MEM-01, P-MEM-02, P-ROLE-01, and P-ROLE-02 are approved names. They are not an implementation instruction.

Company isolation: a membership belongs to one company.

Audit: adding or removing a membership, or changing a flag, is a sensitive change. The actor, time, old value, and new value are the AUDIT-CORE-001 record. This design does not require a row for a refused access.

Depends on: section 3.1. Blocks: ESS reads that need a user-to-employee link.

Requirements: SEC-CORE-001, SEC-CORE-004, AUTH-ARCH.

Unresolved: the user-to-employee link, whether another person may set a password, whether account locks exist, and the exceptional-access list. Gate: the role-matrix build stays blocked.

Acceptance: a member without `can_manage_employees` is not treated as an employee-records manager by mapping. A member without a signed user-to-employee link is not treated as having P-ESS-01.

### 3.3 Authorization and role administration

Purpose: name who may do what, without building the matrix in this design. Users, when a later authorized build exists: the membership and role administrator for category assignment. Not a company administrator. Stage 5D’s rule remains: there is no implemented company-administrator role.

Responsibilities: hold the approved list and stop there.

Approved list, restated from Step 20R section 6C:

- Self-service employee holds P-ESS-01 only: read that user’s own profile when a user-to-employee link already exists for the active company.
- Employee records manager holds P-EMP-01 through P-EMP-04: list and read another employee in the active company, create an employee and the optional first episode, change `display_name`, and create or correct episodes under the rules already in force.
- Organization master manager holds P-ORG-02: create, rename, or set Active or withdrawn on a department or designation. No employee write, no branch write, and no assignment.
- Membership and role administrator holds P-MEM-01, P-MEM-02, P-ROLE-01, and P-ROLE-02.

No category includes payroll, attendance approval or correction, leave, overtime, period close, migration, branch writes, or employee assignment. An authenticated member may read departments and designations. That read is P-ORG-01 and is not a grant from the organization-master category.

Inputs: a company membership and a confirmed category. Outputs: the permissions named above. This design does not output a permission screen or a flag replacement.

Workflow: none is activated. Assigning or removing a category waits on the implementation gate.

Company isolation: a category is a bundle on one company membership. One membership may hold more than one category. One category does not grant another.

Audit: a future category change would be a sensitive change. No such write is authorized here.

Depends on: sections 3.1 and 3.2. Does not unblock attendance, leave, payroll, or migration.

Requirements: SEC-CORE-004, AUTH-ARCH. SEC-CORE-003 stays unsigned.

Unresolved: every open item listed in Step 20R section 6C. Gate: role-matrix implementation is not authorized.

Acceptance: the document’s category list matches section 6C. It does not add a permission. A test that requires the matrix to be enforced is not an acceptance test of this design.

### 3.4 Organization structure and masters

Purpose: company-scoped departments and designations. Users: a member may read them. P-ORG-02 is the approved write permission and is not built as a role.

Responsibilities: keep department and designation names and status per company. Do not assign an employee. Do not write a branch from this capability. ORG-CORE-004 requires that a branch can exist under a company. The approved role list does not include a branch write. ORG-CORE-007 requires employee groups for shared policy. No eligibility or rate is placed on a group here.

Inputs: name and status for a department or designation in the active company. Outputs: those masters. Not an employee assignment.

Workflow: create, rename, and set Active or withdrawn. Those are the P-ORG-02 actions. No assignment transition exists.

Authorization: read is available to an authenticated member of the active company. Write stays behind `can_manage_organization_masters` until a tested mapping is separately approved. That flag does not grant employee changes.

Company isolation: a master belongs to one company. ORG-CORE-005 and ORG-CORE-006.

Audit: a name or status change stores the actor, time, old value, and new value. That behavior is already in the Stage 5D slice. This design does not extend it.

Depends on: section 3.1. Employee assignment would depend on EMP-CORE-004 and is not activated.

Requirements: ORG-CORE-004, ORG-CORE-005, ORG-CORE-006, ORG-CORE-007, ORG-CORE-008, EMP-CORE-004.

Unresolved: assignment columns. Which legacy reporting store is operational. The length of a reporting line is configurable. A fixed boss and grand-boss sequence is not the design. Gate: BD-D17 for approval steps. No category authorizes an assignment write.

Acceptance: a department or designation written in one company is not readable as another company’s master. Changing a master does not change an employee’s department or designation.

### 3.5 Employee directory, profile, and lifecycle

Purpose: one employee master per person per company, with episodes that survive separation and rejoining. Users: the employee-records actions are P-EMP-01 through P-EMP-04. Until the role matrix is built, the implemented gate remains `can_manage_employees`. Own-profile read is P-ESS-01 and requires a user-to-employee link that is not defined.

Responsibilities: create the identity and the optional first episode. Keep the employee code unique inside the company, including after separation. Change `display_name` and keep the previous name. Rejoin by inserting a new episode and leaving the previous episode in place. Store a last working date on separation and do not invent days after it. Store processing attributes as effective-dated facts when those facts are activated. Do not choose the assignment columns.

Inputs: identity fields, the employee code, display name, and episode fields the approved rules already use. Outputs: the employee, the episode history, and the display-name history. Not a payroll status and not a punch map.

State transitions that are supported:

- A new employee may have an optional first episode.
- Rejoin adds an episode. The earlier episode remains.
- Setting an episode that is not Open back to Open is refused. That is the implemented statement of LIFE-CORE-008, not a new rule.

Transitions that are not supported here: mapping `disconrinue` to active, inactive, or separated. Inferring status from a date. Deciding overlap or date order. Choosing a winner among the seven Viyellatex duplicate codes. Writing a branch or an assignment.

Authorization: company membership does not grant these actions. P-ESS-01 does not include a self-service write. The open question of which profile fields an employee may change stays open.

Company isolation: EMP-CORE-002. Exactly one company. CODE-APPROVED applies inside that company.

Audit: display-name history stores the previous name, the new name, the actor, and the time. Other sensitive employee changes follow AUDIT-CORE-001 when they are built. This design does not add a field list.

Depends on: sections 3.1 and 3.2. Department and designation masters are readable context. They are not assigned by this capability.

Requirements: EMP-CORE-001, EMP-CORE-002, EMP-CORE-003, EMP-CORE-004, LIFE-CORE-002 through LIFE-CORE-008, ID-APPROVED, CODE-APPROVED, NOMERGE-APPROVED.

Unresolved: D1, D2, D3, D4 dispositions, D6, LIFE-OVERLAP, LIFE-ORDER, LIFE-INACTIVE-PAY, assignment columns, and the seven duplicate-code groups in Step 20G section 11. Gate: those items block a legacy load and block status or assignment behavior. They do not rewrite the approved rejoin rule.

Acceptance: the same company cannot store two rows with the same employee code. Rejoin leaves the previous episode readable. A display-name change keeps the previous value and the actor. No test in this capability selects a duplicate-code winner.

### 3.6 Attendance ingestion and day results

Purpose: accept punch events, map them to one company and one employee, and keep the day result as a separate versioned record. Users: none of the four categories includes attendance. No attendance user is named.

Responsibilities: ingest an event before calculation. Store event time, ingest time, punch identity, and a named source. Keep the event. Detect a duplicate event. Map one or more identities to one employee in one company. Reject an identity that is active for two employees in that company at the same time unless a documented overlap exists. The overlap list is empty. A correction does not edit the raw event. Recalculation writes a new day-result version. `area` is not the company and is not the named source.

Inputs: an event and an explicit company map. Outputs: the stored event, and, only after the calculation gates are signed, a day-result version. This design does not output a status, a late minute, or a cutoff.

Workflow that is supported as shape: accepted event, then a later versioned day result. Withdrawal is not a workflow. PUNCH-WITHDRAW is unsigned. Shift resolution must end as one effective shift on a date. Which candidate wins is empty, so both an employee default and a dated roster may be stored and neither is selected.

Authorization: no approved category. SEC-CORE-001 still requires a server check before any later state-changing attendance action.

Company isolation: PUNCH-CORE-007. An empty company field does not assign the company.

Audit: a correction’s original, requested, approved, and final values are the approved mechanism. The field list is empty. The raw punch is not one of those editable fields.

Depends on: sections 3.1 and 3.5 for the employee. Does not depend on a legacy card owner.

Requirements: PUNCH-CORE-001 through PUNCH-CORE-009, PUNCH-APPROVED, DI-CORE-005, ATT-CORE-001, ATT-CORE-002, SHIFT-CORE-002, SHIFT-CORE-003, SHIFT-CONF-001, BD-D16.

Unresolved: BD-D01 through BD-D05, BD-D13, BD-D16’s field list, the external writer, cards `126384` and `132058`. Gate: calculation, grace, shift selection, cutoff, and correction editing stay blocked. MIG-CORE-003 flags duplicate maps. It does not choose a card owner.

Acceptance: an event remains readable after a day result is versioned. A day-result write does not change the event. An identity presented for two employees, with no recorded overlap, is not both active. No test asserts a grace minute or a day status.

### 3.7 Leave requests, approvals, and balances

Purpose: record a proposed leave position and keep movements readable. Users: none of the four categories includes leave. No approver sequence is named.

Responsibilities: company-defined leave types may exist as a configurable list. No type is declared paid. A proposed position holds the amount, the period, the source evidence, and the stated basis. Each movement and each reversal is its own entry. A reversal leaves the original movement readable. States are proposed, excepted, and confirmed. Without confirmation the position is not available for leave approval. Variance is a recorded difference. No numerical tolerance is set. The leave period is a company setting separate from the payroll month and the financial year. The months are empty.

LEAVE-CORE-008 is **proposed**. If a later signed rule includes encashment in pay, the payroll result would reference the encashment record and keep the paid amount distinct from the ledger day count. This design does not activate that sentence.

Inputs: a proposed amount, a period label or start and end, source evidence, and a stated basis. Outputs: the position and its movements. Not an approved balance. Not a payroll amount.

Workflow: proposed, then excepted or confirmed. Who confirms is unsigned, so the confirmed state has no actor rule. Request, approve, and deduct are not workflows of this design. APP-CORE-001 requires an ordered step list. BD-D17 leaves that list empty.

Authorization: no approved category. A boss or grand-boss copy is not an authorization check.

Company isolation: one company, one employee, one leave type, one leave period.

Audit: a movement stores the actor and the time. LEAVE-CORE-006.

Depends on: sections 3.1 and 3.5. Payroll use depends on section 3.8 and on a signed encashment rule that does not exist.

Requirements: LEAVE-CORE-001, LEAVE-CORE-005, LEAVE-CORE-006, LEAVE-CORE-007, LEAVE-CONF-003, LEAVE-CORE-008, APP-CORE-001.

Unresolved: LEAVE-OPENING, BD-D06, BD-D17, and the months. Gate: an approved balance, leave pay, and an approval workflow stay blocked. Recording the shape of a proposed position is the limit of this specification.

Acceptance: a position without confirmation is not readable as an approved balance. A reversal does not erase the original movement. No test selects `earned`, `prev_bal`, or a July–June default.

### 3.8 Payroll inputs, calculation, and finalization

Purpose: keep a payroll snapshot separate from live attendance and from a later day-result version. Users: none of the four categories includes payroll. No payroll clerk is named.

Responsibilities: a company may have salary heads, and an employee may have a structure. The head list and the amounts are empty. A run is for one company and one period. The snapshot stores employee, period, structure identity, head amounts, net pay, calculation version, actor, and time. A run is draft or finalized. The payslip is produced from the finalized snapshot only. A finalized snapshot does not follow a later attendance recalculation. The run declares which attendance, leave, and overtime inputs it uses. This design declares none. Calculated overtime, payable overtime, and paid overtime stay separate from each other and from the snapshot. No formula connects them.

Inputs: none are approved. Outputs: the shape of a draft, a finalized snapshot, and a payslip taken from that snapshot. Not a calculated net.

Workflow: draft, then finalized. What else freezes at finalization is BD-D14 and is unsigned beyond the approved snapshot rule. Reopen is deferred under BD-D15. There is no reopen transition.

Authorization: no approved category. Period close is not included in any category.

Company isolation: PAY-CORE-003. One company.

Audit: the snapshot stores actor and time. PAY-CORE-004.

Depends on: section 3.1. Using attendance or leave requires those gates to be signed and the inputs to be named. Neither is done.

Requirements: PAY-CORE-001 through PAY-CORE-007, ARCH-APPROVED, BD-D18.

Unresolved: BD-D07 through BD-D12, BD-D14’s further block, BD-D18’s extra lines, FY-BOUNDARY. Gate: payroll calculation stays blocked. LEAVE-CORE-008 does not create a pay line.

Acceptance: a payslip read uses the finalized snapshot and does not read a later day result as pay. No test asserts a divisor, an overtime rate, or an extra snapshot line.

### 3.9 Audit trail and operational reporting

Purpose: show who changed a sensitive fact. Users: not given a new role. Reporting that needs a financial year, a leave year, or a pay formula is not specified.

Responsibilities: store actor, time, old value, and new value for a sensitive change. Do not require a row because access was refused. Do not define a report whose columns depend on an empty slot.

Inputs: the change itself. Outputs: the history row. Display-name history and department or designation name and status history are the changes already recorded as built.

Company isolation: the history belongs to the company of the fact.

Depends on: the capability that makes the change.

Requirements: AUDIT-CORE-001. FY-BOUNDARY for any financial-year report, which stays unsigned.

Unresolved: operator name and job. Gate: FY-BOUNDARY blocks a financial-year report. It does not block the payroll month as a period label once a payroll run exists, and no payroll run is activated here.

Acceptance: a display-name change can be read back with the previous value, the new value, the actor, and the time. A refused access does not need a matching audit row for this design to pass.

### 3.10 Migration, reconciliation, and cutover

Purpose: state how a future load would be counted and how punch identities would be flagged. Users: no category includes migration. No cutover operator is named.

Responsibilities: load companies, branches, departments, designations, and groups without copying an alias rule into code. Load employees, status, and effective-dated job facts only after the identity decisions are signed. Load punch-identity mappings and flag duplicates for a person to resolve. Do not merge identities and do not silently recode. For a real load, source count equals loaded plus approved exceptions. Employees and episodes are counted separately. Quarantine is not the loaded side. A legacy leave amount loads only as a proposed position.

Inputs: a signed disposition per exception, including the seven duplicate-code groups and the two shared cards. Outputs: a reconciliation result that has not been executed. Not a migrated company.

Workflow: none is activated. No company moves to loaded.

Authorization: outside the four categories.

Company isolation: D7-APPROVED. The tenant is catalog 24, 20, or 10.

Audit: an exception disposition would be a sensitive decision. The register fields for those dispositions are blank.

Depends on: sections 3.1, 3.5, and 3.6. Cannot start from seeded company rows.

Requirements: MIG-CORE-001, MIG-CORE-002, MIG-CORE-003, MIG-CORE-009, MIG-CORE-011, RECON-APPROVED, NOMERGE-APPROVED, NFR-CORE-006.

Unresolved: D1, D2, D3, the D4 group hold, D6, the exception register, and company sign-off. Gate: migration execution stays blocked. This specification does not select a card owner or a duplicate-code winner, and it does not treat a mapping collision as proof that a stored attendance or payroll result is wrong.

Acceptance: a reconciliation statement that counts quarantine as loaded fails this specification. A load that merges two employee rows fails it. No acceptance test requires a live legacy database.

## 4. Exclusions and empty slots

SCOPE-01 stays out: recruitment, PMS and assessments, provident fund, gratuity, bills, lunch, documents, archive, personal files, and payroll extras other than the shape of Payroll Basic, Overtime, and OT Management. Their formulas remain empty. Catalog module 23, `wppfmanagement`, stays unknown.

The catalog grain stays 5 modules and 8 submodules partial, 91 submodules inventory only, and 0 catalog rows marked functionally analyzed.

Empty slots, with no default, are the slots in the design boundary section 4: BD-D01 through BD-D14, BD-D16 through BD-D18, BD-D15 deferred, LEAVE-OPENING, LEAVE-CORE-008 as proposed, FY-BOUNDARY, the punch-overlap list, and the seven duplicate-code dispositions. This design does not repeat their evidence values as options to implement.

## 5. Prerequisites and blocked dependencies

The next implementation milestone is not authorized. Gates section 5.

Before any new capability is built, these remain blocked: the role-matrix build, SEC-CORE-003, attendance calculation, an approved leave balance, payroll calculation, correction editing, approval steps, punch withdrawal, assignment and branch writes, and migration execution.

Blocked verification stays unpassed and is not a design defect to close by coding: F-42, VIS-01 through VIS-12, DB-01, and signed-in UAT. Reported automated tests are not that UAT.

Production rows are preserved. A failed import is rejected or quarantined. No row is treated as disposable.

## 6. Recommended first implementation slice

No new slice is established.

The only coherent slice already supported by approved requirements, already independent of unsigned formulas and unresolved identity dispositions, and already testable inside the active company is the employee foundation that the control center records as built:

- Server-selected active company, login, and logout.
- The two existing membership flags, unmapped to the four categories.
- Directory, profile, and display-name history.
- Episodes, including the approved rejoin and the refusal to set a non-Open episode back to Open.
- Department and designation masters, with no employee assignment.

This document does not authorize an extension of that slice. It does not authorize the role matrix, attendance, leave activation, payroll, or a load.

Acceptance for that existing slice, and not a new build:

- The active company is the server-selected company.
- A display-name change retains the previous name, the new name, the actor, and the time.
- Rejoin inserts an episode and leaves the previous episode in place.
- A department or designation change does not assign an employee.
- `can_manage_organization_masters` does not grant an employee change.
- Passing these checks is not signed-in UAT and is not a migration approval.

A later slice is not named. The nearest unsigned gates, LEAVE-OPENING and the attendance and payroll values, would change the behavior of any slice that started those modules.

## 7. Traceability

| Capability | IDs | Approval status |
| --- | --- | --- |
| Active company | ORG-CORE-001, ORG-CORE-002, ORG-CORE-003, SEC-CORE-002, AUTH-ARCH, D7-APPROVED | Approved architecture and tenant ids. Narrower scope unsigned. |
| Membership and session | SEC-CORE-001, AUTH-ARCH | Approved architecture. Password and lock procedures unsigned. Flags unmapped. |
| Role list | SEC-CORE-004 | Approved 2026-10-17. Build not authorized. |
| Departments and designations | ORG-CORE-005, ORG-CORE-006 | Requirements recorded. Assignment writes unauthorized. |
| Branch and groups | ORG-CORE-004, ORG-CORE-007, ORG-CORE-008 | Requirements recorded. Branch write and group rates not authorized. |
| Employee identity and code | EMP-CORE-001, EMP-CORE-002, ID-APPROVED, CODE-APPROVED, NOMERGE-APPROVED | Approved. Duplicate dispositions unsigned. |
| Episodes and rejoin | LIFE-CORE-007, LIFE-CORE-008 | Rejoin approved and in force. Overlap, date order, and inactive pay unresolved. |
| Effective-dated facts | EMP-CORE-004, LIFE-CORE-002, LIFE-CORE-003, LIFE-CORE-004, LIFE-CORE-005 | Requirements recorded. Columns unsigned. |
| Punch and day result | PUNCH-CORE-001 through PUNCH-CORE-009, PUNCH-APPROVED, ATT-CORE-001, ATT-CORE-002, DI-CORE-005 | Shape approved or recorded. Values and overlaps unsigned. |
| Shift | SHIFT-CORE-002, SHIFT-CORE-003, SHIFT-CONF-001, BD-D03 | One shift is required. The winner is unsigned. |
| Leave position | LEAVE-CORE-001, LEAVE-CORE-005, LEAVE-CORE-006, LEAVE-CORE-007, LEAVE-CONF-003 | Requirements recorded. LEAVE-OPENING unsigned. |
| Encashment in pay | LEAVE-CORE-008 | Proposed. Not approved. |
| Payroll snapshot | PAY-CORE-001 through PAY-CORE-007, ARCH-APPROVED | Shape recorded and snapshot rule approved. Formulas unsigned. |
| Approval steps | APP-CORE-001, BD-D17 | Mechanism recorded. Step list unsigned. |
| Audit | AUDIT-CORE-001 | Requirement recorded. Refused-access row not required. |
| Migration | MIG-CORE-001, MIG-CORE-002, MIG-CORE-003, MIG-CORE-009, MIG-CORE-011, RECON-APPROVED, NFR-CORE-006 | Contract recorded. Execution not approved. |
| Excluded modules | SCOPE-01 | Deferred. |

## 8. Conflicts that remain

The documentation authority, section 3, still says SEC-CORE-004 remains unsigned. Step 20R section 6C, the control center, the gates row, and the design boundary follow the 2026-10-17 approval and still block the build. This design uses Step 20R. The authority sentence was not rewritten.

AUTH-ARCH section 6B says that entry does not satisfy SEC-CORE-004. Section 6C is the later approval of the list. Those sentences are sequential.

Gates section 4 marks BD-D01, BD-D04, BD-D05, and BD-D06 unsigned. Step 20R section 6 says D01–D18 are open. Section 8’s awaiting-approval list omits those four. They stay unsigned empty slots.

## 9. Statement

One functional-design file was added. The control center was given a pointer to it and to the design boundary. No application code, schema, migration, configuration, database, or Git state was changed. No grace, leave opening, pay formula, approval sequence, card owner, or duplicate-code winner was selected.

FUNCTIONAL DESIGN
