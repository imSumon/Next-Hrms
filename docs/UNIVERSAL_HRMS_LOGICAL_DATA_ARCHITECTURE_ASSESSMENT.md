# Universal HRMS Logical Data Architecture Assessment

Phase 1: logical domain and data model only.

Status: assessment. No physical schema, SQL, index, column type, API, framework, vendor, queue, or deployment is specified. Step 9 remains frozen. This document does not amend `docs/UNIVERSAL_HRMS_ARCHITECTURE_ASSESSMENT.md` or `docs/UNIVERSAL_HRMS_REQUIREMENTS.md`.

Proedge behavior is evidence already absorbed by those two documents. Viyellatex behavior is evidence already absorbed by those two documents. Neither pattern is the logical model.

```
UNIVERSAL HRMS CORE
  + COMPANY-CONFIGURABLE POLICIES
  + OPTIONAL CLIENT EXTENSIONS
```

Classifications: CORE, CONFIGURABLE, CLIENT EXTENSION, LEGACY ONLY, BUSINESS DECISION, UNKNOWN.

---

## 1. Purpose

This assessment asks whether the approved architecture can be represented as a clean, normalized, auditable logical model.

The answer is yes, provided the model keeps separate the facts the architecture already separated: punch event, `work_date`, day-result version, correction, calculated overtime, payable overtime, paid overtime, and finalized payroll snapshot. Policy stays data. A company boundary stays on every operational fact.

The model is logical. Names below are responsibilities, not tables.

## 2. Source documents

| Document | Role |
|---|---|
| `docs/UNIVERSAL_HRMS_REQUIREMENTS.md` | What the system shall do. 166 requirements. D01–D18 unresolved. |
| `docs/UNIVERSAL_HRMS_ARCHITECTURE_ASSESSMENT.md` | Frozen architectural principles AP-01–AP-10, boundaries, and ADR-001–ADR-017. |

Closed-month Proedge and Viyellatex findings are used only where those documents already cite them, and only as evidence of what must not be copied.

Requirement SHIFT-CORE-002 says one effective shift for each employee on each date. The frozen architecture refines “date” to `work_date`. This logical model uses that refinement: one effective shift assignment shall exist for each employee/`work_date` after roster/default resolution.

## 3. Logical domain map

| # | Domain | Class | Owns the fact that… |
|---|---|---|---|
| 1 | Company / tenant | CORE | A company is the isolation and policy boundary |
| 2 | Organization: branch, department, designation, group | CORE. Depth and branch overrides are CONFIGURABLE | Structure exists under one company |
| 3 | Employee | CORE | A person belongs to exactly one company |
| 4 | Employment / effective-dated facts | CORE | Job and status facts have a validity interval |
| 5 | Reporting relationship | CORE. Length is CONFIGURABLE | One effective chain exists for an employee on a date |
| 6 | Shift definition | CORE | A shift has a start, an end, and a punch window, and may cross midnight |
| 7 | Shift assignment / roster | CORE. Winner is CONFIGURABLE | Candidates exist. Resolution yields one effective assignment per employee/`work_date` |
| 8 | Holiday / holiday calendar | CORE. Who it covers is CONFIGURABLE | A date is a weekly off or a public holiday for a defined audience |
| 9 | Punch source | CORE. Which sources are accepted is CONFIGURABLE | An event names its source |
| 10 | Punch identity | CORE. How many active identities is CONFIGURABLE | A punch identity maps to one employee inside one company |
| 11 | Raw punch event | CORE | An accepted event keeps its calendar timestamp |
| 12 | Attendance calculation | CORE | A calculation version records the inputs and policy it used |
| 13 | Attendance day result | CORE. Vocabulary is CONFIGURABLE | A stored result exists for an employee and a `work_date` |
| 14 | Leave type | CORE. Pay effect is CONFIGURABLE | A company defines types |
| 15 | Leave request | CORE | A person requests a date range, and an outcome may mark days |
| 16 | Attendance correction | CORE. Allowed fields are CONFIGURABLE | Original, requested, approved, and final values stay distinct from the punch |
| 17 | Approval workflow / instance | CORE. The step list is CONFIGURABLE | Approval is an ordered workflow with an acting user |
| 18 | Overtime calculation | CORE. Formula is CONFIGURABLE | Calculated OT is its own figure |
| 19 | Payable overtime | CORE. Eligibility, rounding, approval, and rate are CONFIGURABLE | Payable OT is not calculated OT and not money |
| 20 | Salary structure | CORE | An employee has an effective-dated structure |
| 21 | Salary head | CORE. The list is CONFIGURABLE | A company defines heads |
| 22 | Payroll run | CORE | A company calculates a period from declared inputs |
| 23 | Payroll result / snapshot | CORE. Optional lines are CONFIGURABLE | Finalized pay is a retained copy |
| 24 | Payslip | CORE. Legal layout is CLIENT EXTENSION | The payslip reads a finalized snapshot |
| 25 | Attendance period control | CORE capability. Use is CONFIGURABLE | Recalculation can be stopped for a period |
| 26 | Payroll period control | CORE capability. Use is CONFIGURABLE. Reopen roles are DEFERRED | Finalization, close, and reopen are different controls |
| 27 | Policy / configuration | CORE | One applicable value wins for an employee/`work_date` |
| 28 | Audit history | CORE | Who, when, old, and new remain knowable |
| 29 | Client extension boundary | CORE switch. Each extension is CLIENT EXTENSION | An extension is off until one company enables it |

## 4. Domain and entity responsibilities

### 4.1 Company / tenant

Purpose: isolate people, time, leave, and pay (ORG-CORE-001, ORG-CORE-002).

Owns: company identity and the scope of every operational fact.

May reference: nothing outside itself for operational data.

Must not own: another company’s employees, punches, results, or policy. Must not own behavior through a database alias (ORG-CORE-003).

Lifecycle: created, active, and able to hold history after people leave. Withdrawal is a status, not a hard delete (DI-CORE-001).

Class: CORE.

### 4.2 Organization

Purpose: branch or site, department, designation, and employee group (ORG-CORE-004 through ORG-CORE-007).

Owns: the structure nodes of one company, and whether a branch may override a selected policy (ORG-CONF-002, off unless enabled). Location depth is a company choice (ORG-CONF-001).

May reference: the company. A department is defined under the company. It is not required to sit under a branch. A group is the preferred scope for shared eligibility and rate.

Must not own: attendance formulas, pay amounts, or a universal factory tree of unit, floor, and line.

Lifecycle: structure changes are effective-dated where a later result must show which node applied.

Class: CORE structure. Depth and branch override are CONFIGURABLE.

### 4.3 Employee

Purpose: one person master inside one company (EMP-CORE-001, EMP-CORE-002).

Owns: identity within that company, and status of at least active, inactive, and separated (EMP-CORE-003). Additional attributes exist only when the company enables them (EMP-CONF-001).

May reference: the company.

Must not own: punch timestamps, day results, salary amounts, or weekly off stored in an unrelated attribute.

Lifecycle: the master survives separation. Status changes are dated.

Class: CORE. Extra attributes are CONFIGURABLE.

### 4.4 Employment and effective-dated employee facts

Purpose: keep the facts that change over time as intervals (EMP-CORE-004, LIFE-CORE-001 through LIFE-CORE-008).

Owns: joining date, last working date, employment episode, and effective-dated department, designation, location, group, and shift default. Rejoining is a new episode. The previous episode remains.

May reference: employee, organization nodes, and a shift definition as a default candidate.

Must not own: the resolved shift for a `work_date`. That resolution belongs to shift assignment. Must not invent days before joining or after the last working date.

Lifecycle: episodes append. An interval closes when the next interval starts. Suspension stops processing. The pay effect of suspension is not chosen here.

Class: CORE.

### 4.5 Reporting relationship

Purpose: a chain an approval step may use (ORG-CORE-008, APP-CORE-002).

Owns: the ordered links in force for an employee on a date.

May reference: employees in the same company.

Must not own: a fixed two-step boss and grand-boss pair. Length is a company configuration. Must not own the approval outcome.

Lifecycle: effective-dated. A later chain does not rewrite an approval already taken.

Class: CORE. Length is CONFIGURABLE.

### 4.6 Shift definition

Purpose: the window used to interpret punches (SHIFT-CORE-001, SHIFT-CORE-004).

Owns: start, end, punch window, and whether that window crosses midnight. A change to the window is a new version of the definition.

May reference: the company.

Must not own: grace minutes, who is assigned, or which punches were selected. Grace is ATT-CONF-003 and BD-D01.

Lifecycle: versions. A calculation cites the version it used.

Class: CORE.

### 4.7 Shift assignment and roster

Purpose: supply candidates and, after resolution, exactly one effective assignment (SHIFT-CORE-002, SHIFT-CORE-003, SHIFT-CONF-001).

Owns: a dated default and a dated roster entry. Owns the resolved assignment for one employee and one `work_date`.

May reference: employee, shift definition version, and the company winner rule.

Must not own: two effective shifts for the same employee and `work_date`. Must not decide the winner. That value is BD-D03.

Lifecycle: assignments are effective-dated. Resolution is recorded with the attendance calculation.

Class: CORE. Winner rule is CONFIGURABLE.

### 4.8 Holiday and holiday calendar

Purpose: mark weekly off and public holidays on the day result (HOL-CORE-001, HOL-CORE-002).

Owns: the weekly-off rule and the public-holiday dates. Weekly off may vary by group, and by person only as an exception (HOL-CONF-001). A public holiday may be limited to selected shifts (HOL-CONF-002).

May reference: company, group, person exception, and shift.

Must not own: the day-result status vocabulary, and must not use an employee photo attribute as the weekend.

Lifecycle: dated rules. A past `work_date` keeps the holiday fact that applied.

Class: CORE. Audience is CONFIGURABLE.

### 4.9 Punch source

Purpose: name where an event came from (PUNCH-CORE-001, PUNCH-CORE-003).

Owns: the source name a company accepts (PUNCH-CONF-001).

May reference: the company. A future device protocol, once identified, is an extension slot (EXT-CLIENT-004), not a source designed here.

Must not own: vendor identity, device protocol, or the legacy `area` and `balid` meanings. Those remain UNKNOWN.

Lifecycle: a source can be enabled or disabled for a company. Events already stored keep the name they were given.

Class: CORE. Acceptance list is CONFIGURABLE. A named device protocol is CLIENT EXTENSION and currently empty.

### 4.10 Punch identity

Purpose: map a punch identity to an employee inside one company (PUNCH-CORE-008). The identity may differ from the employee code.

Owns: the identity, the employee, and the active interval. An active identity maps to one employee unless the company records an explicit overlap (PUNCH-CORE-009). Multiple active identities for one employee are a company choice (PUNCH-CONF-004).

May reference: employee and company.

Must not own: the event time, and must not treat “the card text equals the employee code” as proof they are the same concept.

Lifecycle: intervals. Duplicate active use in one company is rejected unless the overlap is explicit.

Class: CORE. Multiplicity is CONFIGURABLE.

### 4.11 Raw punch event

Purpose: retain each accepted event until a processing rule selects among them (PUNCH-CORE-002, PUNCH-CORE-004).

Owns: event time, ingestion time (PUNCH-CORE-006), punch identity, and named source. Duplicate detection is CORE (PUNCH-CORE-005). Duplicate treatment is company policy (PUNCH-CONF-002).

May reference: punch identity and source. Company attribution happens only through that identity’s mapping (PUNCH-CORE-007).

Must not own: `work_date`, attendance status, late, overtime, or pay. Must not be updated by a correction.

Lifecycle: accepted and retained. Withdrawal is a status. Hard delete is excluded (DI-CORE-001). A failed import is rejected or quarantined without a partial silent write (NFR-CORE-006).

Class: CORE.

### 4.12 Attendance calculation

Purpose: record one calculation of one employee and one `work_date` (ATT-CORE-001, DI-CORE-002).

Owns: calculation identity, the `work_date`, the effective shift assignment, references to the punches selected, holiday and leave inputs used, correction input if any, the policy versions applied, and the result version produced.

May reference: shift assignment, punch events, holiday, leave outcome, approved correction, and policy.

Must not own: the raw events themselves, and must not write pay.

Lifecycle: each permitted recalculation appends a calculation. An attendance cutoff, when the company has enabled it, refuses a new calculation for that period (PER-CORE-001). Whether cutoff is on is BD-D13.

Class: CORE.

### 4.13 Attendance day result

Purpose: the stored outcome reports read (ATT-CORE-002, ATT-CORE-003, ATT-CORE-008).

Owns: employee, `work_date`, effective shift, selected punches, status, incomplete-punch condition when present, and measured late or early duration when present. Status vocabulary is company-defined (ATT-CONF-001).

May reference: the calculation that produced it, and the correction that caused a later version.

Must not own: the raw punch, the payroll amount, or a second report formula (RPT-CORE-001).

Lifecycle: an original result remains. A later permitted calculation adds a new version and marks which version is current. The current result after a correction states that a correction was applied.

Class: CORE. Vocabulary and incomplete-punch outcome are CONFIGURABLE.

### 4.14 Leave type

Purpose: company-defined types (LEAVE-CORE-001).

Owns: the type identity. Paid, unpaid, or partial is a property the company sets (LEAVE-CONF-002, BD-D06).

May reference: the company.

Must not own: the day result or the salary deduction.

Lifecycle: effective-dated so a historical day shows the type meaning that applied.

Class: CORE. Pay effect is CONFIGURABLE.

### 4.15 Leave request

Purpose: a dated request with a reason (LEAVE-CORE-002), approved through the workflow (LEAVE-CORE-003).

Owns: employee, date range, type, reason, and outcome.

May reference: leave type, approval instance, and the day results an approved outcome is allowed to mark (LEAVE-CORE-004). Which outcome writes the day is LEAVE-CONF-001.

Must not own: punch events or pay.

Lifecycle: requested, in approval, approved or rejected. The request remains after the day is marked.

Class: CORE. The writing outcome and the pay effect are CONFIGURABLE.

### 4.16 Attendance correction

Purpose: a request distinct from the punch and from the day result (CORR-CORE-001).

Owns: original value, requested value, approved value, and final calculated value (CORR-CORE-002), plus actor, time, and reason (CORR-CORE-003).

May reference: employee, `work_date`, the original result version, the approval instance, and the new result version.

Must not own: a rewritten punch. Must not erase the original result (CORR-CORE-004). Allowed fields are BD-D16.

Lifecycle: requested, approved or rejected, then, when approved, an attendance recalculation. History remains after the new result exists (AUDIT-CORE-002).

Class: CORE. Field list is CONFIGURABLE.

### 4.17 Approval workflow and approval instance

Purpose: ordered steps (APP-CORE-001). A step may be a role, a reporting-line manager, an organizational approver, or a named fallback (APP-CORE-002).

Owns: the definition (the ordered list and whether a later step may bypass earlier steps) and the instance (each action’s acting user, time, and outcome) (APP-CORE-003).

May reference: the subject, which may be leave, correction, or payable overtime when that approval is enabled.

Must not own: a universal Employee → Manager → HR → Payroll chain. That chain is one legal configuration (APP-CONF-001, BD-D17).

Lifecycle: the definition is effective-dated. The instance stores the steps that bound that request, so a later edit of the definition does not rewrite the instance.

Class: CORE. The list is CONFIGURABLE.

### 4.18 Calculated overtime

Purpose: the figure the day formula produced (OT-CORE-001).

Owns: the calculated figure for an employee and `work_date`, tied to a day-result version and a shift.

May reference: the day result and the formula policy version. The formula is BD-D09.

Must not own: eligibility, money, or the payable figure.

Lifecycle: recalculated with the day result. Prior calculated figures remain with their result versions.

Class: CORE. Formula and rounding are CONFIGURABLE.

### 4.19 Payable overtime

Purpose: the figure that may feed payroll, separate from calculated OT (OT-CORE-002).

Owns: the payable figure, the eligibility outcome, and the approval outcome when the company requires approval (OT-CONF-004, BD-D11).

May reference: calculated OT for the same employee and `work_date`, the group eligibility rule, and an approval instance.

Must not own: the paid amount. A punch correction is not an overtime approval.

Lifecycle: produced from calculated OT by eligibility, rounding, limits, and approval when those policies are on. It may differ from calculated OT. It remains even if payroll later pays a different amount.

Class: CORE. Eligibility, rounding, approval, and rate inputs are CONFIGURABLE.

### 4.20 Salary structure

Purpose: the employee’s pay structure (PAY-CORE-001).

Owns: which heads apply to the employee over an effective interval, and the structure identity a snapshot can cite.

May reference: employee and salary heads.

Must not own: a finalized amount. Amounts of a run live on the snapshot.

Lifecycle: effective-dated. A past snapshot keeps the structure identity it used.

Class: CORE.

### 4.21 Salary head

Purpose: company-defined earning and deduction identities (PAY-CORE-002).

Owns: the head identity and its classification as earning or deduction. The list differs by company.

May reference: the company.

Must not own: a universal head catalogue, an absenteeism formula, or an overtime rate.

Lifecycle: effective-dated so a historical snapshot names the heads that existed.

Class: CORE. Membership of the list is CONFIGURABLE.

### 4.22 Payroll run

Purpose: calculate one company and one period (PAY-CORE-003, PAY-CORE-008).

Owns: period, declared inputs (PAY-CORE-007), calculation version, and draft state. Declared inputs name which attendance, leave, and overtime facts the run may use. A run that ignores attendance says so.

May reference: current day results and payable OT only while the run is draft.

Must not own: the live punch log, and must not rewrite a finalized snapshot.

Lifecycle: draft, then finalized. A draft may be recalculated. After finalization, regeneration stops when close is enabled (PER-CORE-002). Whether close is enabled is BD-D14.

Class: CORE.

### 4.23 Payroll result and snapshot

Purpose: the retained pay result (PAY-CORE-004, PAY-CORE-005).

Owns: employee, period, structure identity, head amounts, net pay, calculation version, actor, time, and finalization state. Also owns the copy of the inputs the run used.

May reference: the run, the structure, and the input facts as they were at calculation, copied onto the snapshot.

Must not own: a live link that changes when attendance is recalculated (ATT-CORE-007, DI-CORE-003).

Lifecycle: draft result, then finalized. A finalized result changes only through an authorized reopen or reversal. Who may reopen is BD-D15 and is DEFERRED.

Class: CORE. Which optional lines are stored is CONFIGURABLE (PAY-CONF-004, BD-D18).

### 4.24 Payslip

Purpose: present finalized pay (PAY-CORE-006, RPT-CORE-002).

Owns: the choice to render a finalized snapshot. It does not own a second set of amounts.

May reference: one employee’s finalized snapshot.

Must not own: live attendance, an external day-count feed, or a client legal layout. Those layouts and an external day-count feed are extensions (EXT-CLIENT-001, EXT-CLIENT-002) and are off by default.

Lifecycle: available once the snapshot is finalized. It follows reopen only because the snapshot itself was reopened.

Class: CORE. Layout and external day-count are CLIENT EXTENSION.

### 4.25 Attendance period control

Purpose: be able to stop recalculation for a company period (PER-CORE-001).

Owns: the period, whether cutoff is enabled, and the cutoff boundary when it is enabled (PER-CONF-001, BD-D13).

May reference: company and period.

Must not own: proof of close merely because a legacy flag exists. A company that leaves cutoff off is valid.

Lifecycle: open, then cutoff when the company enables it.

Class: CORE capability. Use is CONFIGURABLE.

### 4.26 Payroll period control

Purpose: separate finalization, close, and reopen (PER-CORE-002, PER-CORE-003).

Owns: finalization state on the snapshot, an optional close that stops regeneration, and an audited reopen that applies only to a closed period.

May reference: the payroll run and snapshot.

Must not own: a reopen path with no close, and must not name the reopen roles. That choice is DEFERRED (PER-CONF-003, BD-D15).

Lifecycle: draft, finalized, optionally closed, optionally reopened by an authorized audited action.

Class: CORE capability. Close is CONFIGURABLE. Reopen roles are DEFERRED.

### 4.27 Policy and configuration

Purpose: store company policy as data (CFG-CORE-001, CFG-CORE-002).

Owns: policy identity, scope, effective interval, precedence, and value. The value is unresolved for D01–D18.

May reference: company, and a lower scope only when that policy allows it.

Must not own: a code branch, a universal numeric default, or two active values at the same precedence.

Lifecycle: effective-dated. A calculation cites the policy version it used.

Class: CORE mechanism. Each policy value is CONFIGURABLE or BUSINESS DECISION.

### 4.28 Audit history

Purpose: who changed a sensitive fact, when, and from what to what (AUDIT-CORE-001, AUDIT-CORE-002).

Owns: actor, timestamp, old value, new value, and reason where the business object requires one. Migrated actors that the source did not store are marked unknown (MIG-CORE-010).

May reference: the business object.

Must not own: a silent hard delete.

Lifecycle: append-only for the objects in section 14.

Class: CORE. Retention duration is not chosen.

### 4.29 Client extension boundary

Purpose: optional behavior for one company (EXT-CORE-001).

Owns: an enablement switch per company, default off.

May reference: a named extension. The approved slots are a legal payslip layout (EXT-CLIENT-001), an external day-count feed (EXT-CLIENT-002), mobile location as a named punch source (EXT-CLIENT-003), and a device protocol after it is identified (EXT-CLIENT-004).

Must not own: core day results, core snapshots, or a vendor protocol.

Lifecycle: off, then enabled for one company. Disabling it does not rewrite core history.

Class: the switch is CORE. Each slot is CLIENT EXTENSION.

## 5. Relationship model

Cardinality is conceptual. It is not a foreign key.

### 5.1 Organization chain

| From | To | Cardinality | Note |
|---|---|---|---|
| Company | Branch | One-to-many | Depth of the location tree is CONFIGURABLE |
| Company | Department | One-to-many | Department is under the company |
| Company | Designation | One-to-many | |
| Company | Employee group | One-to-many | Preferred scope for shared policy |
| Company | Employee | One-to-many | An employee belongs to exactly one company |

### 5.2 Employee chain

| From | To | Cardinality | Note |
|---|---|---|---|
| Employee | Employment episode | One-to-many | Rejoining adds an episode |
| Employment episode | Effective-dated fact | One-to-many | On one date, one value per fact type |
| Employee | Reporting chain | One-to-many over time | On one date, one chain. Chain length is CONFIGURABLE |
| Employee | Shift default | One-to-many over time | A candidate, not the resolved `work_date` shift |
| Employee | Punch identity | One-to-many | Whether more than one may be active is CONFIGURABLE |
| Punch identity | Employee | Many-to-one while active | Two employees in one company cannot share an active identity unless an explicit overlap is recorded |

### 5.3 Time and punch chain

| From | To | Cardinality | Note |
|---|---|---|---|
| Company | Shift definition | One-to-many | A definition may cross midnight |
| Shift definition | Shift definition version | One-to-many | |
| Employee | Roster assignment | One-to-many | Dated |
| Employee + `work_date` | Effective shift assignment | One-to-one after resolution | Candidates may be many. The winner rule is CONFIGURABLE (BD-D03) |
| Company | Punch source | One-to-many | Which sources are accepted is CONFIGURABLE |
| Punch source | Raw punch event | One-to-many | |
| Punch identity | Raw punch event | One-to-many | The event does not point at `work_date` |
| Company | Holiday date | One-to-many | |
| Holiday date | Shift | Many-to-many | Only when the company limits a public holiday to shifts. Otherwise the holiday is company-scoped |

### 5.4 Attendance chain

| From | To | Cardinality | Note |
|---|---|---|---|
| Employee + `work_date` | Attendance calculation | One-to-many | One current calculation, prior calculations retained |
| Attendance calculation | Selected punch events | One-to-many | The selection rule is CONFIGURABLE (PUNCH-CONF-003). An empty selection is a recorded fact |
| Attendance calculation | Attendance day result version | One-to-one | The result cites that calculation |
| Employee + `work_date` | Current day result | One-to-one | Prior versions remain addressable |
| Day result version | Calculated OT | One-to-one when the calculation produces it | The formula is CONFIGURABLE |
| Calculated OT | Payable OT | One-to-one candidate | Payable may differ. Approval of payable OT is zero or one instance, and that link exists only when the company turns approval on (BD-D11) |

### 5.5 Leave, correction, and approval

| From | To | Cardinality | Note |
|---|---|---|---|
| Company | Leave type | One-to-many | |
| Employee | Leave request | One-to-many | A request covers a date range, which expands to zero or more `work_date` impacts |
| Leave request | Approval instance | One-to-one | |
| Approved leave outcome | Day result | One-to-many `work_date`s | Only the outcome the company configured to write the day does so |
| Employee + `work_date` | Correction request | One-to-many | Several requests may exist over time. Each keeps its own original and final values |
| Correction request | Approval instance | One-to-one | |
| Approved correction | New day-result version | One-to-one | The prior version remains |
| Approval definition | Approval instance | One-to-many | The instance keeps the step list that bound it |
| Approval instance | Approval action | One-to-many | One action has one acting user |

Step count inside a definition is CONFIGURABLE (BD-D17).

### 5.6 Payroll chain

| From | To | Cardinality | Note |
|---|---|---|---|
| Company | Salary head | One-to-many | |
| Employee | Salary structure | One-to-many over time | One structure in force on a date |
| Salary structure | Salary head | One-to-many | |
| Company + period | Payroll run | One-to-many | Recalculation of a draft adds a version |
| Payroll run | Declared input set | One-to-one per version | The set names what attendance, leave, and OT the run used |
| Payroll run | Employee snapshot | One-to-many | One snapshot per employee per run version |
| Finalized snapshot | Payslip | One-to-one as a reading | The payslip does not store a second amount |
| Payable OT | Snapshot paid OT | Many-to-one into the period amount | Only when the company declared OT as a pay input. Rate is CONFIGURABLE (BD-D12) |
| Attendance period | Day-result versions | One-to-many | Cutoff, when enabled, blocks new versions |
| Payroll period | Finalized snapshot | One-to-many employees | Close, when enabled, blocks regeneration |

## 6. Effective-dating model

Historical reproducibility needs the value that was in force, not the value that is in force now. No dates and no policy numbers are chosen.

| Concept | Why effective dating is required |
|---|---|
| Employment facts | A later department, designation, location, group, or status must not rewrite a past day or a past payslip. Joining and last working date bound which `work_date`s exist. |
| Department and designation | They are employment facts. The result and the snapshot cite the node that applied. |
| Reporting line | An approval step of type “reporting-line manager” must show the manager who was in the chain when the step was taken. |
| Shift assignment | Resolution is per `work_date`. A later roster edit must not change a stored result. The calculation cites the assignment it resolved. |
| Shift definition version | A window change, including a change to a cross-midnight window, is a new version. Old calculations keep the old window. |
| Holiday calendar | A date is a holiday or a weekly off for an audience on that date. Later calendar edits do not relabel a stored result. |
| Policy configuration | The calculation and the payroll run cite the policy version they used. A later grace, formula, or cutoff edit does not change a stored result. |
| Salary structure | The snapshot stores the structure identity that was in force for that period. |
| Salary heads | A head added or retired later must not appear on, or vanish from, a historical snapshot. |
| Approval workflow | The instance stores the ordered steps that bound the request. |
| Attendance period | The cutoff state that applied to a period is itself a historical fact. An unused cutoff stays unused. |
| Payroll period | Finalization, close, and reopen are dated events. A snapshot remembers which state it was in. |

On one calendar date, each fact type has one winning interval. Overlapping intervals of the same fact at the same precedence are rejected or otherwise resolved deterministically. Resolution must not depend on query order.

## 7. Attendance data model

These five facts stay distinct.

| Fact | What it is | What happens on correction |
|---|---|---|
| Raw punch event | Calendar timestamp, identity, source, ingestion time | Unchanged. Immutable |
| Original attendance result | First stored result version for that employee and `work_date` | Remains addressable |
| Correction request | Original, requested, actor, time, reason | Remains after approval |
| Approved correction | Approved value and acting user | Does not replace the request |
| Recalculated / final result | A new result version | Becomes current. States that a correction was applied. Cites the correction and the prior version |

Logical flow:

```
Raw Punch
→ Original Attendance Result
→ Correction Request
→ Approval
→ Approved Correction
→ Attendance Recalculation
→ New/Final Day Result
```

Rules:

- A correction never modifies the original raw punch event.
- Original values remain available for history.
- Applying an approved correction creates a new attendance result version.
- The current result identifies the correction.
- Reports read the current stored result (ATT-CORE-003). They do not recompute from punches.
- Recalculation is allowed only under the company’s permitted rules, including cutoff when cutoff is enabled.
- Incomplete attendance is its own condition (ATT-CORE-004). What it becomes, and whether it affects pay, is BD-D02. The model can store the condition and the company’s resulting status. It does not store Present-plus-text or the letter A as the universal representation.
- The result stores the effective shift, the punches selected, the status, and measured late or early duration (ATT-CORE-008). Grace and the start of late measurement are BD-D01 and BD-D04.

A day result without recorded source facts is invalid. The calculation records the shift, the selected punches or an explicit empty selection, holiday, leave, correction if any, and the policy versions. An empty punch selection is a source fact.

## 8. Cross-midnight and work_date model

`work_date` is the business date assigned by the effective shift or roster.

`work_date` must not simply equal punch date.

A shift may cross midnight. A shift from Oct 9 09:00 to Oct 10 09:00 has `work_date` = Oct 9.

Punch timestamps retain their actual calendar date and time. Ingestion time is stored separately. Neither time is rewritten to `work_date`.

One effective shift assignment shall exist for each employee/`work_date` after roster/default resolution.

Logical representation:

| Fact | Held on |
|---|---|
| Calendar event time | Raw punch event |
| Ingestion time | Raw punch event |
| Shift start, end, and punch window, including a window that passes midnight | Shift definition version |
| Resolved assignment | Employee + `work_date` |
| `work_date` | Attendance calculation and day result |
| Association of a punch to that `work_date` | The calculation’s selected-punch set, not the punch |

A punch at Oct 10 02:00 can belong to `work_date` Oct 9 when the effective shift window includes it. It can belong to `work_date` Oct 10 when that day’s shift includes it. The association is the result of the effective shift plus the company’s punch-selection rule (PUNCH-CONF-003). The punch row, conceptually, still says Oct 10 02:00.

Which candidate assignment wins remains BD-D03. The model stores both candidates and the one resolved assignment. It does not collapse them into a single employee shift pointer.

## 9. Leave, correction, and approval model

Leave:

- A type belongs to the company.
- A request has an employee, a date range, a type, and a reason.
- The request has one approval instance.
- An approved outcome may mark one or more `work_date` results (LEAVE-CORE-004).
- Which outcome is allowed to write the day is CONFIGURABLE (LEAVE-CONF-001). A legacy status number is not that outcome.
- Whether the type is paid, unpaid, or partial is CONFIGURABLE (BD-D06). Leave publishes a day classification. Payroll decides money.

Correction:

- The request cites the original result version and stores original, requested, approved, and final values.
- Approval is required before recalculation changes the current result.
- The new result version cites the correction.
- The raw punch and the original result stay in place.
- Which fields may change is BD-D16.

Approval:

- A definition is an ordered list of steps, effective-dated, scoped to the company and to a subject type (leave, correction, and payable OT only if OT approval is enabled).
- A step’s resolver is a role, a reporting-line position, an organizational approver, or a named fallback.
- An instance copies the binding step list.
- Each action stores the acting user, time, and outcome.
- Bypass of earlier steps exists only when the company configured it (BD-D17).
- The server accepts an action only when that user may take that step of that request (SEC-CORE-006).

Employee → Manager → HR → Payroll is a legal configuration. It is not the model.

## 10. OT model

Three figures, three meanings.

| Figure | Owned by | May differ from the others | Feeds payroll |
|---|---|---|---|
| Calculated OT | Attendance, per result version and `work_date` | Yes | Only if the company declares it |
| Payable OT | Overtime, per employee and `work_date` | Yes | Yes, when the company pays OT and declares this input |
| Paid OT | The payroll snapshot, as money | Yes | It is the snapshot |

The model supports calculated OT ≠ payable OT ≠ paid OT.

Payable OT references the calculated OT it considered. Eligibility, rounding, limits, and an optional approval may reduce it or leave it unchanged. Those rules are BD-D08 through BD-D11. No formula, rate, rounding method, eligibility list, or approval requirement is selected.

Paid OT is an amount on the snapshot (OT-CORE-003). It is not an attendance column. When the run declares payable OT as an input, the snapshot retains the payable figure it used. The rate that turns that figure into money is BD-D12.

A punch correction does not create payable OT. If the company requires a separate OT approval, that approval is its own instance (OT-CONF-004).

## 11. Payroll snapshot model

Attendance recalculation and payroll recalculation are different operations.

- Attendance may be recalculated according to permitted rules. That writes a new day-result version.
- A draft payroll run may be recalculated. That writes a new draft snapshot version.
- A finalized snapshot does not change because a later attendance version exists.
- A finalized snapshot changes only through an explicitly authorized reopen or reversal, and only when the period is closed. Who may do that remains BD-D15.

Logical contents that finalization always retains (PAY-CORE-004):

| Content | Why |
|---|---|
| Employee | Whose pay |
| Period | Which payroll period |
| Structure identity | Which effective structure |
| Head amounts | What was calculated |
| Net pay | The result |
| Calculation version | Which run version |
| Actor and time | Who finalized, and when |
| Finalization state | Draft or finalized |
| Declared input set | Which attendance, leave, and OT facts the run was allowed to use (PAY-CORE-007) |
| The values of those declared inputs | So the result can be reproduced without reading live attendance |

Optional facts the company may include (PAY-CONF-004, BD-D18), and only when that company’s policy uses them:

- payable days
- paid and unpaid leave
- absence
- late or early deductions
- calculated OT
- approved OT
- payable OT

No optional line is selected here. Payable-day basis, absence formula, and late or early pay effect stay open (PAY-CONF-001, PAY-CONF-002, PAY-CONF-003, BD-D07, BD-D05).

Reproducibility of a historical payroll result uses three kinds of retained fact:

- source facts the run used
- effective policy and structure that applied
- calculation and snapshot version

The payslip reads the finalized snapshot only. It does not read the current day result.

A company that never enables close may still finalize, and the finalized snapshot still does not follow attendance. Close is the additional switch that stops regeneration (BD-D14). Reopen does not exist without a close.

## 12. Policy and configuration model

Resolution order (CFG-CORE-001): product default where one exists, then company, then a lower level only when that policy allows it.

| Policy | Logical level | Open decision |
|---|---|---|
| Grace, late, early | Company default, shift override | BD-D01, BD-D04, BD-D05 |
| Incomplete punch | Company | BD-D02 |
| Shift winner | Company | BD-D03 |
| Leave pay | Company, per type | BD-D06 |
| Absence pay and payable days | Company | BD-D07, PAY-CONF-001 |
| OT eligibility and rate | Employee group, rare person exception | BD-D08, BD-D12 |
| OT formula | Company default, then shift | BD-D09 |
| OT rounding | Company | BD-D10 |
| OT approval | Company | BD-D11 |
| Attendance cutoff | Company | BD-D13 |
| Payroll close | Company | BD-D14 |
| Reopen roles | Company | BD-D15, DEFERRED |
| Correction fields | Company | BD-D16 |
| Approval sequence | Company | BD-D17 |
| Snapshot optional facts | Company | BD-D18 |
| Weekly off | Company, then employee group | — |
| Accepted punch sources, duplicate treatment, which punches count | Company | — |
| Branch override | Off unless the company enables it for a selected policy | — |

A policy record has a scope, an effective interval, a precedence, and a value. This assessment does not set the value.

For a given employee and `work_date`:

- Exactly one applicable value wins at each required policy level.
- Overlapping active configurations at the same precedence are rejected or otherwise deterministically resolved.
- Behavior does not depend on query ordering.
- The winning version is what the calculation or payroll run cites.

Employee exception is allowed only where the approved level says so: weekly off, and rarely OT eligibility or rate. It is not a general override.

## 13. Security and ownership model

No permission tables are designed. The logical scopes are:

| Scope | Rule | Source |
|---|---|---|
| Company | Every operational fact is owned by one company. A read or write is valid only when the actor’s company matches | ORG-CORE-002, DI-CORE-004, SEC-CORE-002 |
| Branch | A user may be limited to a branch | SEC-CORE-003 |
| Department | A user may be limited to a department | SEC-CORE-003 |
| Employee | A user may be limited to named employees | SEC-CORE-003 |
| ESS | The session is one employee in one company, and may submit only enabled request types | SEC-CORE-007, ESS-CORE-001 |
| Reporting line | Used to resolve an approval step. It is not a standing data scope unless a role is defined that way | APP-CORE-002 |
| Approval actor | The acting user must be allowed for that step of that request | SEC-CORE-006, APP-CORE-003 |
| Payroll | Read and write of payroll are separate from attendance view | SEC-CORE-008 |
| Correction | Limited to authorized roles and to the fields the company allows | SEC-CORE-009, BD-D16 |
| Period close and reopen | Separate from ordinary attendance and payroll edit | SEC-CORE-010 |
| Authorization point | The server checks the action. A hidden control is not the check | SEC-CORE-001, SEC-CORE-005 |

Personal and pay data stay inside the company boundary (NFR-CORE-003).

## 14. Audit and history model

| Object | What remains knowable | Actor | Time | Previous and current | Reason |
|---|---|---|---|---|---|
| Employee and employment change | Status, department, designation, location, group, joining, separation, episode | Yes | Yes | Yes | Where the company captures one |
| Shift assignment | Candidates and the resolved assignment for the `work_date` | Yes | Yes | Yes | No universal reason |
| Policy change | Scope, interval, precedence, old value, new value | Yes | Yes | Yes | No universal reason |
| Punch | The accepted event. A later duplicate decision does not erase it | Ingestion actor or named source | Event time and ingestion time | The event is not edited | Not a business reason |
| Attendance recalculation | Inputs, policy versions, result version, link to the prior version | Yes | Yes | Prior version remains current-or-not | Not required for an ordinary rebuild |
| Correction | Original, requested, approved, final | Yes, a user | Yes | Yes | Yes (CORR-CORE-003) |
| Approval action | Step, outcome, subject | Yes, a user | Yes | The instance accumulates actions | Where the step collects one |
| OT approval | Only when the company enables it. Outcome and payable figure | Yes | Yes | Yes | Where required by that workflow |
| Payroll calculation | Declared inputs, structure, heads, amounts, net, version | Yes | Yes | Draft versions remain | No |
| Payroll finalization | State becomes finalized | Yes | Yes | Draft versus finalized | No |
| Payroll reopen or reversal | That a closed period was reopened, and the snapshot version that followed | Yes | Yes | Prior finalized version remains | Yes, because reopen is an audited exception (PER-CORE-003) |

Sensitive pay, attendance results, corrections, and permission changes are included (SEC-CORE-011).

Unknown actor is permitted for migrated history and is labeled unknown. It is not permitted for a new approval action.

Retention duration is not chosen. Hard delete of punches, day results, corrections, approvals, and finalized pay is excluded.

## 15. Legacy mapping

Mapped only after the target model above. No migration procedure is specified.

| Legacy object | Target concept | Class | Treatment |
|---|---|---|---|
| `companies` and alias | Company | Maps to core tenant. Alias-as-behavior is LEGACY ONLY | Load the company. Do not load `db_alias` as a rule (MIG-CORE-001) |
| `employees` | Employee plus employment facts | Maps to core, requires transformation | Status, joining, separation, department, designation, and group become effective-dated facts (MIG-CORE-002). Do not reuse a photo attribute as weekly off |
| `employees.shift_id` | Shift default candidate | Migration source only | Record that a default existed. It is not automatically the resolved `work_date` shift (MIG-CORE-009). Winner remains BD-D03 |
| `emp_shift_calendar` | Roster candidate | Migration source only | Load dated assignments where they exist. Do not invent a roster for a company that had none |
| `att_shift` | Shift definition | Maps to core, requires transformation | Start, end, and punch window can load. A next-day window becomes a cross-midnight capability. Grace is not taken from this object |
| `employees.photo` when it holds weekday names | Weekly-off rule | LEGACY ONLY storage | Transform into a weekly-off rule where the business confirms the meaning. Do not keep the photo attribute |
| `emp_code_to_punch_card` | Punch identity | Maps to core, requires transformation | Load mappings. Flag active duplicates (MIG-CORE-003, DI-CORE-005) |
| `all_att_log` | Raw punch event | Maps to core, requires historical reconstruction | Load retained events with calendar time. Company comes from the identity mapping, not from an empty company attribute. Middle punches already dropped from day results are not invented (MIG-CORE-004) |
| `area`, `balid` | None | UNKNOWN | Carry only as uninterpreted legacy attributes. Do not promote them to source or device |
| Company `att_log` | None for the studied companies | LEGACY ONLY | Empty and unread in the approved evidence. Not a target entity |
| `attendance_detail` | Attendance day result | Maps to core, requires historical reconstruction | Load as historical results, separate from events (MIG-CORE-005). Delete-and-insert means the prior version is often gone. Do not fabricate it |
| `hrm_leave_emp_app` | Leave request | Maps to core, requires transformation | Load the request and outcome (MIG-CORE-006). A numeric status is not the universal approval outcome |
| `attendance_approval_file` | Correction request | Maps to core, requires historical reconstruction | Load what is present. Where original or approved values are missing, store unknown. Do not invent them (MIG-CORE-007). A name string is not an acting user |
| `boss_id`, `gboss_id` | Reporting chain candidate | LEGACY ONLY as the workflow | May inform a migrated chain. The target workflow is an ordered definition (BD-D17 remains open) |
| `employee_overtime` | None as the OT model | LEGACY ONLY for the studied 2026 months | Empty in those months. Do not treat it as payable OT |
| Attendance `ot_hours` | Calculated OT candidate | Requires transformation and a business decision | Units and meaning differ by client. Do not convert seconds or whole hours into a universal formula (BD-D09, BD-D10) |
| `eligible_for_ot` | None | LEGACY ONLY | Do not load it as eligibility (BD-D08) |
| `employee_salary` | Historical pay amounts | Maps to a partial snapshot, requires reconstruction | Load amounts. Do not claim attendance inputs that were not stored (MIG-CORE-008) |
| Payslip views that read `employee_salary` | Payslip reads stored pay | Evidence for PAY-CORE-006 | The view is not the snapshot structure |
| Payslip views that read `nms_data` | External day-count feed | CLIENT EXTENSION evidence | EXT-CLIENT-002. Off by default. Not the company 20 payslip |
| `attendance_month_lock` | Attendance period control | Cannot be safely migrated as “closed” without a business decision | A Yes flag is not proof of cutoff (BD-D13) |
| `salary_month_lock` | Payroll period control | Cannot be safely migrated as “closed” without a business decision | A Yes flag is not proof of close (BD-D14) |
| `moduleAccess` and action strings | None | LEGACY ONLY | Authorization is server-side. Do not migrate hidden buttons as permissions |

Actor and time load where the source has them. Otherwise the actor is unknown (MIG-CORE-010).

## 16. Data integrity rules

| Risk | Logical prevention |
|---|---|
| Duplicate employee codes | Inside one company, no two employee identities share one employee code, including after separation. Closed by `docs/UNIVERSAL_HRMS_STEP20_FINAL_EMPLOYEE_CODE_DECISION.md` |
| Duplicate punch identities | An active identity belongs to one employee in that company unless an explicit overlap is recorded (DI-CORE-005) |
| Multiple effective shifts | Candidates may be many. After resolution, exactly one effective assignment exists per employee/`work_date`. Same-precedence overlaps are rejected or deterministically resolved |
| Overlapping policies | One winning value per required level for that employee/`work_date`. Same-precedence overlap is rejected or deterministically resolved. Query order is not a rule |
| Overlapping employment facts | One value per fact type on a date. Overlap is rejected |
| Ambiguous reporting lines | One effective chain on a date. Overlap is rejected. Length stays configurable |
| Correction without approval | A request may exist. It changes the current day result only after an approval outcome the company allows to apply |
| Approval without actor | A new approval action requires an acting user. Unknown is allowed only on migrated history and is labeled unknown |
| Finalization without required inputs | Finalization retains the PAY-CORE-004 set and every input the run declared. A declared input with no retained value blocks finalization |
| Finalized payroll changing after attendance recalculation | Recalculation writes an attendance version only. The finalized snapshot is a separate fact. Change requires authorized reopen or reversal of a closed period |
| Paid OT without payable OT | When the run declares OT as an input, paid OT on the snapshot cites the payable figure used. When the company does not pay OT, there is no paid OT amount. Equality is not required |
| Payable OT without calculated OT | Payable OT cites the calculated OT it considered. A missing calculated figure is not filled by a payable figure. The two figures may still differ |
| Day result without source facts | The calculation records shift, selection (including an explicit empty selection), holiday, leave, correction if any, and policy versions |
| Missing `work_date` | A day result and a calculation require a `work_date`. A punch does not |
| Cross-midnight ambiguity | The punch keeps calendar time. `work_date` is assigned by the effective shift. The selected-punch set records the association |
| Company leakage | Company ownership is on the operational fact. Access requires a company match. A punch joins a company only through that company’s identity mapping |

Withdrawal of a punch, result, correction, approval, or finalized snapshot is a status. It is not a hard delete.

## 17. Open decisions

This assessment does not decide D01–D18.

| ID | Still open | What the logical model does instead |
|---|---|---|
| BD-D01 | Grace length | Stores a shift-level policy slot with a company default. No minutes |
| BD-D02 | Incomplete-punch outcome and pay effect | Stores the incomplete condition separately from the company’s resulting status |
| BD-D03 | Which assignment wins | Stores candidates and one resolved assignment. Does not pick the winner |
| BD-D04 | When late duration starts | Stores measured duration and the policy version. Does not pick the start |
| BD-D05 | Early-out threshold and pay effect | Stores measured duration. Pay effect stays a payroll policy slot |
| BD-D06 | Leave pay by type | Stores the type. Paid, unpaid, or partial stays empty |
| BD-D07 | Absence formula | Stores absence as a possible declared input. No formula |
| BD-D08 | OT eligibility | Group policy slot. The legacy flag is not loaded as the rule |
| BD-D09 | OT formula | Calculated OT is a figure. No formula |
| BD-D10 | OT rounding | Rounding is a company policy slot. No method |
| BD-D11 | Separate OT approval | The approval link exists only if the company enables it |
| BD-D12 | Whether OT is paid, and the rate | Paid OT is snapshot money. No rate |
| BD-D13 | Attendance cutoff on or off | The control exists. Enablement is empty |
| BD-D14 | Payroll close on or off | Finalization and close are separate. Enablement of close is empty |
| BD-D15 | Who may reopen | DEFERRED. Reopen has no role list |
| BD-D16 | Correction fields | Original, requested, approved, and final exist. The allowed field list is empty |
| BD-D17 | Approval steps and bypass | Ordered steps exist. The company’s list is empty |
| BD-D18 | Optional snapshot lines | The snapshot can hold them. None is required beyond the PAY-CORE-004 minimum and the inputs the run declared |

Payable-day basis stays open with PAY-CONF-001. No 30-day default.

UNKNOWN evidence remains unknown: the punch writer, the meaning of `balid` and `area`, Atlas and CEVA operated policy, and a joiner or leaver month. They are not business decisions and they are not entities.

## 18. Architecture readiness

| Item | Assessment |
|---|---|
| Approved requirements and frozen Step 9 architecture can be represented logically | YES |
| Domain boundaries match AP-04 through AP-08 | YES |
| `work_date` is distinct from punch timestamp, including a cross-midnight shift | YES |
| One effective shift assignment per employee/`work_date` after resolution | YES |
| Raw punch immutable, correction history retained, new result version | YES |
| Calculated, payable, and paid OT are separate, and inequality is allowed | YES |
| Finalized payroll is independent of later attendance recalculation | YES |
| Effective dating and deterministic policy precedence | YES |
| Company ownership on operational facts | YES |
| Business policy values D01–D18 | DECISION REQUIRED |
| Punch writer and `balid` / `area` | UNKNOWN |
| Physical schema, SQL, indexes, column types | NOT STARTED |
| API, UI, framework, vendor, queues | NOT STARTED |
| Development | NOT STARTED |

The logical model is ready to guide a later physical design. It is not that design. Choosing either client’s tables as the physical model would recreate the legacy problems the architecture excluded.

### Verification

| Check | Result |
|---|---|
| SQL schema created | No |
| API design created | No |
| Framework, vendor, queue, or deployment chosen | No |
| A policy value selected | No |
| Proedge or Viyellatex behavior promoted to the universal model | No. Both appear only in the legacy-mapping exclusions |
| Cross-midnight supported | Yes. Section 8 |
| `work_date` is not punch date | Yes. Section 8 |
| Raw punches immutable | Yes. Section 7 |
| Corrections preserve history | Yes. Sections 7 and 9 |
| Calculated, payable, and paid OT remain separate | Yes. Section 10 |
| Finalized payroll protected from later attendance recalculation | Yes. Section 11 |
| Effective dating represented | Yes. Section 6 |
| Deterministic configuration precedence represented | Yes. Section 12 |
| D01–D18 remain open | Yes. Section 17 |

## 19. Explicit exclusions

This phase does not design or choose:

- Tables, keys, column types, indexes, or document shapes
- SQL, migrations, or a physical database per company
- APIs, events, queues, caches, or service boundaries
- A framework, language, or host
- A vendor, device protocol, or meaning for legacy `area` and `balid`
- Grace, late, early, leave-pay, absence, overtime, cutoff, reopen, correction-field, approval-step, or snapshot-line values
- Status vocabularies copied from either client
- Boss and grand-boss as the approval structure
- A mandatory period close
- Hard delete
- Alias or company-id branches as behavior

Physical design waits on a later phase. D01–D18 still wait on the business.
